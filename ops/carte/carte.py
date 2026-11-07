#!/usr/bin/env python3
"""Fabrique la carte Big M à importer (wp-content/plugins/bigm-core/data/carte.json).

    python3 -I ops/carte/carte.py chemin/Allergenes_BigM.xlsx [chemin/uber-eats.html]

Sources :
- le tableau des allergènes (onglet « Liste consolidée ») : produits, catégories, allergènes
  (X = contient, O = peut contenir / traces). L'onglet « Recettes » sert seulement à savoir
  quelle viande contient chaque produit ; rien d'autre n'en est repris (document confidentiel) ;
- facultatif : la page Uber Eats d'un restaurant, enregistrée depuis le navigateur
  (Fichier → Enregistrer sous… → Page web, HTML uniquement) : descriptions, photos, menus et
  boissons. Les prix ne sont pas repris (brief : pas de prix).

Le fichier xlsx n'est pas mis dans Git. Le JSON produit ne contient que des données publiques
(noms, catégories, viandes, allergènes, descriptions, adresses des photos).
"""

import datetime
import html
import json
import re
import sys
import unicodedata
from pathlib import Path

import openpyxl

RACINE = Path(__file__).resolve().parents[2]
SORTIE = RACINE / "wp-content/plugins/bigm-core/data/carte.json"

ALLERGENES = {  # colonne du tableau → identifiant de bigm-core
    "Gluten": "gluten", "Œufs": "oeufs", "Lait": "lait", "Arachides": "arachides",
    "Fruits à coque": "fruitsacoque", "Soja": "soja", "Sésame": "sesame", "Lupin": "lupin",
    "Sulfites": "sulfites", "Moutarde": "moutarde", "Céleri": "celeri",
    "Mollusques": "mollusques", "Crustacés": "crustaces", "Poissons": "poissons",
}

# Catégories du site (taxonomie bigm_categorie). Celles qui existent déjà (même adresse) sont
# gardées telles quelles ; les autres sont créées avec ce nom, ce titre et cet ordre.
CATEGORIES = [
    {"slug": "nouveautes", "nom": "Nouveautés", "titre": "Nos nouveautés", "ordre": 1},
    {"slug": "menus", "nom": "Menus", "titre": "Nos menus", "ordre": 2},
    {"slug": "burgers", "nom": "Burgers", "titre": "Nos burgers", "ordre": 3},
    {"slug": "boeuf", "nom": "Bœuf", "parent": "burgers", "ordre": 1},
    {"slug": "burgers-poulet", "nom": "Poulet", "parent": "burgers", "ordre": 2},
    {"slug": "poulet", "nom": "Poulet", "titre": "Notre poulet", "ordre": 4},
    {"slug": "wraps", "nom": "Wraps", "titre": "Nos wraps", "ordre": 5,
     "trace": "M4 7c5-3 11-3 16 0l-2 12c-4 2-8 2-12 0z M7 9.5c3 1.4 7 1.4 10 0"},
    {"slug": "petite-faim", "nom": "Petite faim", "titre": "Petite faim", "ordre": 7},
    {"slug": "desserts", "nom": "Desserts", "titre": "Nos desserts", "ordre": 8},
    {"slug": "sauces", "nom": "Sauces", "titre": "Nos sauces", "ordre": 9,
     "trace": "M10 3h4v3l2 3v11a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V9l2-3z M8 13h8"},
    {"slug": "boissons", "nom": "Boissons", "titre": "Nos boissons", "ordre": 10},
]

# Groupe du tableau → catégories des produits. Les burgers vont dans Bœuf ou Poulet selon leur
# viande (voir plus bas).
GROUPES = {
    "Burgers": ["burgers"], "Burgers Little": ["burgers"], "Burgers enflammés": ["burgers"],
    "Burgers Naan": ["burgers"], "Burgers Bao": ["burgers"],
    "Burgers doublés (codes M / W)": ["burgers"],
    "Croque & boîtes": ["petite-faim"], "Wraps": ["wraps"], "Chick'n Box": ["poulet"],
    "Snacking": ["petite-faim"], "Frites": ["petite-faim"], "Desserts": ["desserts"],
    "Sauces": ["sauces"],
}

# Noms du tableau → noms affichés (fautes de frappe, casse, mentions internes).
NOMS = {
    "Nuggets (petite faim)": "Nuggets", "Tenders (petite faim)": "Tenders",
    "CHICK'N BOX CURRY": "Chick'n Box Curry", "CHICK'N BOX TANDOORI": "Chick'n Box Tandoori",
    "Frite": "Frites", "Frite cheedar cripsy": "Frites cheddar crispy",
    "Frite cheddar": "Frites cheddar", "Frite cheddar cripsy bacon": "Frites cheddar crispy bacon",
    "Mozza-stick": "Mozza sticks", "Bouchée camembert": "Bouchées camembert",
    "Panko Onion rings": "Onion rings panko", "Chilli cheese nuggets": "Chili cheese nuggets",
    "Long bacon M": "Long Bacon M", "Tarte Choco coco": "Tarte choco-coco",
    "Tarte au daim": "Tarte au Daim", "My Chou Dubai (Pistache)": "My Chou Dubaï pistache",
    "BIG ICE spéculos": "Big Ice Spéculoos", "BIG ICE daim": "Big Ice Daim",
    "BIG ICE m&ms": "Big Ice M&M's", "BIG ICE oréo": "Big Ice Oreo",
    "Mayonnaise aux œufs": "Mayonnaise", "Sauce géant": "Sauce Géant", "Sauce bao": "Sauce Bao",
}

# Viande d'après l'onglet « Recettes » (steak → bœuf ; chicken, escalope, tenders, nuggets,
# poulet tandoori / curry → poulet). Sans recette, ou viande inconnue (kebab, bacon seul) :
# rien, à compléter dans l'admin (voir A_VERIFIER).
VIANDE_RECETTE = [
    (re.compile(r"^steak", re.I), "boeuf"),
    (re.compile(r"chicken burger|escalope|tenders|nuggets|poulet", re.I), "poulet"),
]
# Produits sans viande (fritures, fromages panés) : « Végétarien » dans le filtre.
VEGETARIENS = {"Frites", "Frites cheddar", "Frites cheddar crispy", "Mozza sticks",
               "Bouchées camembert", "Onion rings panko"}
SANS_VIANDE = {"Desserts", "Sauces"}  # ni viande ni « Végétarien » : le filtre n'y sert pas
CATEGORIES_EN_PLUS = {"Nuggets": ["petite-faim", "poulet"], "Tenders": ["petite-faim", "poulet"],
                      "Boîte Mix": ["petite-faim", "poulet"], "Menu kids": ["menus"]}
A_VERIFIER = {
    "Croq Monsieur": "viande du bacon (bœuf ? dinde ?)",
    "Wrap Kebab": "viande kebab (veau, dinde, bœuf ?)",
    "Long Bacon M": "pas de recette : viande à confirmer",
    "Frites cheddar crispy bacon": "viande du bacon",
    "Mini smoky cheese": "pas de recette : viande ou végétarien ?",
    "Chili cheese nuggets": "pas de recette : végétarien ?",
    "Menu kids": "contenu du menu",
}


def slug(texte):
    t = unicodedata.normalize("NFKD", texte).encode("ascii", "ignore").decode().lower()
    t = t.replace("&", " ").replace("'", "")
    return re.sub(r"[^a-z0-9]+", "-", t).strip("-")


def cle(texte):
    """Clé de rapprochement entre le tableau et Uber Eats."""
    return re.sub(r"\b(le|la|les|m)\b", " ", slug(texte).replace("-", " ")).replace(" ", "")


def viandes_des_recettes(wb):
    ws = wb["Recettes"]
    lignes = list(ws.iter_rows(min_row=4, values_only=True))
    entete, ingredients = lignes[0], [l for l in lignes[1:] if l[0]]
    resultat = {}
    for j, produit in enumerate(entete[1:], start=1):
        if not produit:
            continue
        trouvees = []
        for l in ingredients:
            if l[j] in (None, "", 0):
                continue
            for motif, viande in VIANDE_RECETTE:
                if motif.search(str(l[0])) and viande not in trouvees:
                    trouvees.append(viande)
        resultat[str(produit).strip()] = trouvees
    return resultat


def produits_du_tableau(chemin):
    wb = openpyxl.load_workbook(chemin, data_only=True)
    viandes = viandes_des_recettes(wb)
    ws = wb["Liste consolidée"]
    lignes = list(ws.iter_rows(min_row=5, values_only=True))
    entete = lignes[0]
    colonnes = {i: ALLERGENES[str(c).strip()] for i, c in enumerate(entete) if c and str(c).strip() in ALLERGENES}
    produits, ordre = [], {}
    for l in lignes[1:]:
        groupe, brut = (l[0] or "").strip(), (l[1] or "").strip()
        if not brut or groupe not in GROUPES:
            continue
        nom = NOMS.get(brut, brut)
        code = bool(re.fullmatch(r"[MW]\d+", brut))
        contient = [a for i, a in colonnes.items() if str(l[i] or "").strip().upper() == "X"]
        traces = [a for i, a in colonnes.items() if str(l[i] or "").strip().upper() == "O"]
        v = list(viandes.get(brut, []))
        if nom in VEGETARIENS:
            v = ["vegetarien"]
        if groupe in SANS_VIANDE or nom in A_VERIFIER:
            v = []
        cats = list(CATEGORIES_EN_PLUS.get(nom, GROUPES[groupe]))
        if cats == ["burgers"] and not code:
            if "boeuf" in v:
                cats = ["boeuf"]
            elif "poulet" in v:
                cats = ["burgers-poulet"]
            else:
                cats = ["boeuf"]  # famille des burgers au bœuf (Long Bacon M) ; viande à confirmer
        for c in cats:
            ordre[c] = ordre.get(c, 0) + 1
        produits.append({
            "titre": f"{nom} (nom à compléter)" if code else nom,
            "slug": slug(nom),
            "source": brut,
            "categories": cats,
            "viandes": v,
            "contient": contient,
            "traces": traces,
            "description": "",
            "image": "",
            "ordre": ordre[cats[0]],
            "statut": "draft" if code else "publish",
            "a_verifier": A_VERIFIER.get(nom, "") or ("code interne : nom, photo et contenu à compléter" if code else ""),
        })
    return produits


def uber_eats(chemin):
    """Articles d'une page Uber Eats enregistrée : [{section, titre, description, image}]."""
    texte = Path(chemin).read_text(encoding="utf-8", errors="replace")
    articles = []
    # Les données de la page sont dans du JSON (échappé ou non) : on cherche les objets
    # d'article, qui ont un titre, une description et une adresse d'image.
    brut = html.unescape(texte).replace('\\u002F', '/').replace('\\"', '"')
    for m in re.finditer(r'"title":"(?P<t>[^"]{2,80})"(?P<reste>.{0,1500}?)"imageUrl":"(?P<i>https://tb-static\.uber\.com/[^"]+)"', brut, re.S):
        desc = re.search(r'"(?:itemDescription|description)":"(?P<d>[^"]*)"', m.group("reste"))
        articles.append({"titre": m.group("t").strip(), "description": (desc.group("d") if desc else "").strip(), "image": m.group("i")})
    vus, uniques = set(), []
    for a in articles:
        if a["titre"] not in vus:
            vus.add(a["titre"])
            uniques.append(a)
    return uniques


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    produits = produits_du_tableau(sys.argv[1])
    sources = ["tableau des allergènes (Liste consolidée, version de travail)"]
    if len(sys.argv) > 2:
        index = {cle(p["titre"]): p for p in produits}
        for a in uber_eats(sys.argv[2]):
            p = index.get(cle(a["titre"]))
            if p:
                p["description"] = p["description"] or a["description"]
                p["image"] = p["image"] or a["image"]
        sources.append("page Uber Eats Big M Bondy (descriptions et photos)")
    donnees = {
        "version": datetime.date.today().isoformat(),
        "sources": sources,
        "categories": CATEGORIES,
        "produits": produits,
    }
    SORTIE.parent.mkdir(parents=True, exist_ok=True)
    SORTIE.write_text(json.dumps(donnees, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    publies = sum(1 for p in produits if p["statut"] == "publish")
    print(f"{SORTIE.relative_to(RACINE)} : {len(produits)} produits ({publies} publiés), {len(CATEGORIES)} catégories")
    for p in produits:
        if p["a_verifier"]:
            print(f"  à vérifier : {p['titre']} — {p['a_verifier']}")


if __name__ == "__main__":
    main()
