#!/usr/bin/env python3
"""Fabrique la carte Big M à importer (wp-content/plugins/bigm-core/data/carte.json).

    python3 -I ops/carte/carte.py chemin/Allergenes_BigM.xlsx [page-uber-eats.html …]

Sources :
- le tableau des allergènes (onglet « Liste consolidée ») : produits, catégories, allergènes
  (X = contient, O = peut contenir / traces). L'onglet « Recettes » sert seulement à savoir
  quelle viande contient chaque produit ; rien d'autre n'en est repris (document confidentiel) ;
- ops/carte/uber-eats-carte.json, recopié des cartes Uber Eats de Big M (Bondy, Argenteuil) : noms
  des menus (dont les codes M1… W4 du tableau), menus, boissons, sauces à part, descriptions.
  Les allergènes et les viandes d'un menu sont ceux de ses produits réunis (boisson non comprise),
  plus ceux de sa ligne dans le tableau s'il en a une. Les prix ne sont pas repris (brief) ;
- facultatif : des pages Uber Eats de restaurants Big M, enregistrées depuis le navigateur (HTML).
  Leurs articles (nom, description, adresse de la photo ; pas les prix) sont gardés dans
  ops/carte/uber-eats/{restaurant}.json, qui servent ensuite à chaque passage : photos (dans l'ordre
  de PRIORITE_PHOTOS) et descriptions (restaurants de DESCRIPTIONS_DE) des produits qui n'en ont pas.

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
    (re.compile(r"^bacon", re.I), "boeuf"),  # bacon de bœuf (confirmé par l'agence ; Uber Eats écrit « dinde »)
    (re.compile(r"chicken burger|escalope|tenders|nuggets|poulet", re.I), "poulet"),
]
# Produits sans viande (fritures, fromages panés) : « Végétarien » dans le filtre.
VEGETARIENS = {"Frites", "Frites cheddar", "Frites cheddar crispy", "Mozza sticks",
               "Bouchées camembert", "Onion rings panko"}
SANS_VIANDE = {"Desserts", "Sauces"}  # ni viande ni « Végétarien » : le filtre n'y sert pas
CATEGORIES_EN_PLUS = {"Nuggets": ["petite-faim", "poulet"], "Tenders": ["petite-faim", "poulet"],
                      "Boîte Mix": ["petite-faim", "poulet"], "Menu kids": ["menus"]}
A_VERIFIER = {
    "Wrap Kebab": "viande kebab (veau, dinde, bœuf ?)",
    "Mini smoky cheese": "pas de recette : viande ou végétarien ?",
    "Chili cheese nuggets": "pas de recette : végétarien ?",
}
UBER = Path(__file__).with_name("uber-eats-carte.json")
ORDRE_ALLERGENES = list(ALLERGENES.values())
ORDRE_VIANDES = ["boeuf", "poulet", "poisson", "vegetarien"]
ARTICLES = Path(__file__).with_name("uber-eats")
# Photos : celles d'Argenteuil (produit seul, 350 px) d'abord, sinon celles de Bondy (512 px).
PRIORITE_PHOTOS = ["big-m-argenteuil", "big-m-bondy"]
# Descriptions : celles de Bondy (composition). Celles d'Argenteuil sont des textes commerciaux,
# parfois faux (Wrap Steak décrit comme le Wrap Curry, fromage dans le M Burger).
DESCRIPTIONS_DE = ["big-m-bondy"]
# Photos d'Uber Eats qui ne montrent pas l'article (même photo que la sauce burger).
PHOTOS_EXCLUES = {"Sauce Barbecue"}
# Articles Uber Eats → produits du site, quand les noms diffèrent (les codes M1… W4 sont reconnus seuls).
ALIAS_UBER = {
    "CheeseBurger M": "Cheeseburger M", "Double CheeseBurger M": "Double Cheeseburger M",
    "Triple CheeseBurger M": "Triple Cheeseburger M", "L Bacon M": "Long Bacon M",
    "Burger Jumbo": "Jumbo", "Burger Monster": "Monster", "Chicken Bao Burger": "Mbao Chicken",
    "Croque classic": "Croq Monsieur", "Boite mix": "Boîte Mix",
    "Mozza Sticks x4": "Mozza sticks", "Mozza Sticks x8": "Mozza sticks",
    "Bouchees Camembert x4": "Bouchées camembert", "Bouchees Camembert x8": "Bouchées camembert",
    "Nugget's x4": "Nuggets", "Nugget's x8": "Nuggets",
    "Menu CheeseBurger M": "Menu Cheeseburger M", "Menu Double CheeseBurger": "Menu Double Cheeseburger M",
    "Menu BIG": "Menu Big", "Menu L Bacon M": "Menu Long Bacon M",
    "Menu Triple Cheeseburger": "Menu Triple Cheeseburger M", "Menu Bao Chicken": "Menu Mbao Chicken",
    "Menu Bao Beef Burger + Frites": "Menu Mbao Beef", "Menu Burger Jumbo": "Menu Jumbo",
    "Little Kids Nugget's x4": "Little Kids Nuggets",
    "Sauce Algérienne": "Sauce algérienne", "Sauce Pita": "Sauce pita", "Sauce Barbecue": "Sauce barbecue",
    "Sauce Blanche": "Sauce blanche", "Sauce Ketchup": "Ketchup", "Sauce Mayo": "Mayonnaise",
    "Sauce Samourai": "Sauce samouraï", "Hawai 33cl": "Hawaï 33 cl",
    # Argenteuil
    "Long Beef": "Long Beef M", "Croque Monsieur": "Croq Monsieur", "Tenders à l'unité": "Tenders",
    "MOZZA  STICK 4 pièces": "Mozza sticks", "MOZZA  STICK 8 pièces": "Mozza sticks",
    "BOUCHEES DE CAMEMBERT 4 pièces": "Bouchées camembert", "BOUCHEES DE CAMEMBERT 8 pièces": "Bouchées camembert",
    "Panko Onion 4Pc": "Onion rings panko", "Panko Onion 8Pc": "Onion rings panko",
    "Chili Cheese 4PC": "Chili cheese nuggets", "Chili Cheese  8PC": "Chili cheese nuggets",
    "Frites Cheddar Bacon Crispy": "Frites cheddar crispy bacon",
    "Pistachoise": "My Can Pistachoise", "Tropical Island": "My Can Tropical Island",
    "Magic Red": "My Can Magic Red", "Paris Brest": "My Can Paris Best",
    "My Chou Dubai": "My Chou Dubaï pistache", "Chic'N Box Curry": "Chick'n Box Curry",
    "Fanta Fruit du Dragon 33cl": "Fanta Fruits du Dragon 33 cl", "Hawaii 33cl": "Hawaï 33 cl",
    "Schwepps Agrumes 33cl": "Schweppes Agrum' 33 cl",
    "Oasis Pomme Cassis Framboise 33cl": "Oasis Pomme Cassis 33 cl",
    "Sauce Mayonnaise": "Mayonnaise", "Sauce Giant": "Sauce Géant",
    "Menu Little Kids": "Little Kids Burger", "Menu Little Double": "Little Doublé",
    "Menu Double Cheeseburger": "Menu Double Cheeseburger M", "Menu Bao Beef Burger": "Menu Mbao Beef",
    "Menu Bao Chicken Burger": "Menu Mbao Chicken", "Menu Long Beef": "Menu Long Beef M",
    "Menu Long Smoky Bacon M": "Menu Long Smoky Bacon", "Fuze Tea Pêche 33cl": "Fuze Tea pêche 33 cl",
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


def extraire_uber(chemin):
    """Restaurant et articles d'une page Uber Eats enregistrée (HTML) : (slug, [{section, titre, description, image}]).

    Les données de la carte sont dans le script __REACT_QUERY_STATE__ (JSON dont les guillemets
    sont écrits \\u0022 ; l'enregistrement par le navigateur remplace aussi les barres obliques
    inverses par %5C). Les prix ne sont pas gardés.
    """
    texte = Path(chemin).read_text(encoding="utf-8", errors="replace")
    m = re.search(r'<script type="application/json" id="__REACT_QUERY_STATE__">(.*?)</script>', texte, re.S)
    if not m:
        sys.exit("Page Uber Eats sans __REACT_QUERY_STATE__ : enregistrer la page complète (HTML).")
    brut = m.group(1).strip()
    etat = json.loads(json.loads('"' + brut.replace('"', '\\"') + '"').replace("%5C", "\\"))
    articles, vus, restaurant = [], set(), ""
    for requete in etat["queries"]:
        donnees = requete.get("state", {}).get("data")
        if not isinstance(donnees, dict) or "catalogSectionsMap" not in donnees:
            continue
        restaurant = slug(donnees.get("title", "")) or restaurant
        for sections in donnees["catalogSectionsMap"].values():
            for section in sections:
                charge = section.get("payload", {}).get("standardItemsPayload") or {}
                titre_section = re.sub(r"[^\w' ]+", "", charge.get("title", {}).get("text", "")).strip()
                for a in charge.get("catalogItems", []):
                    if a["title"] in vus:
                        continue
                    vus.add(a["title"])
                    articles.append({"section": titre_section, "titre": a["title"].strip(),
                                     "description": (a.get("itemDescription") or "").strip(),
                                     "image": a.get("imageUrl") or ""})
    return restaurant, articles


def nettoyer(texte):
    """Description Uber Eats mise au propre : unités, accents, point final."""
    t = re.sub(r"(\d+)\s*[gG][rR]?\b", r"\1 g", texte.strip())
    t = re.sub(r"\bboeuf\b", "bœuf", t).replace("Nann", "Naan").replace(" dune ", " d'une ")
    if t and t[-1] not in ".!?":
        t += "."
    return t[:1].upper() + t[1:]


def cle_uber(titre):
    """Clé de rapprochement des noms Uber Eats : sans accents, casse, quantités ni espaces."""
    t = slug(titre).replace("cheese-burger", "cheeseburger")
    t = re.sub(r"-(\d+)-?(pieces|pcs?)$|-x\d+$", "", t)
    t = re.sub(r"-(\d+)-?cl$", r"-\1cl", t)
    return t.replace("-", "")


def photos_uber(produits, pages):
    """Photos et descriptions manquantes, d'après les articles Uber Eats de chaque restaurant."""
    par_cle = {cle_uber(p["titre"]): p for p in produits}
    codes = {re.match(r"^(\S+) –", p["titre"]).group(1): p for p in produits if re.match(r"^(M\d+|W\d+|MBAO) –", p["titre"])}
    codes.update({re.match(r"^(\S+) \(", p["titre"]).group(1): p for p in produits if re.match(r"^(M\d+|W\d+) \(nom", p["titre"])})
    codes["MBAO"] = next((p for p in produits if p["slug"] == "mbao"), None)
    trouves = set()
    for restaurant in sorted(pages, key=lambda r: PRIORITE_PHOTOS.index(r) if r in PRIORITE_PHOTOS else 99):
        for a in pages[restaurant]:
            # Codes de menus : « M1 - … » (Bondy), « Menu M1 » ou « Menu Mbao » (Argenteuil).
            code = re.match(r"^(M\d+|W\d+|MBAO) - |^Menu (M\d+|W\d+|Mbao)$", a["titre"])
            if code:
                p = codes.get((code.group(1) or code.group(2)).upper())
            else:
                p = par_cle.get(cle_uber(ALIAS_UBER.get(a["titre"], a["titre"])))
            if not p:
                continue
            trouves.add(p["slug"])
            if a["image"] and not p.get("image") and a["titre"] not in PHOTOS_EXCLUES:
                p["image"] = a["image"]
            if (restaurant in DESCRIPTIONS_DE and a["description"] and not p["description"]
                    and "dinde" not in a["description"]):
                p["description"] = nettoyer(a["description"])
    return trouves


def fusion_uber(produits, chemin):
    """Ajoute la carte Uber Eats recopiée : noms des menus, menus, boissons, sauces, descriptions."""
    uber = json.loads(Path(chemin).read_text(encoding="utf-8"))
    par_source = {p["source"]: p for p in produits}
    par_titre = {p["titre"]: p for p in produits}
    trouve = lambda nom: par_source.get(nom) or par_titre.get(nom)

    for nom, texte in uber.get("descriptions", {}).items():
        if trouve(nom):
            trouve(nom)["description"] = texte
    for nom, viandes in uber.get("viandes", {}).items():
        if trouve(nom):
            trouve(nom)["viandes"] = viandes
            trouve(nom)["a_verifier"] = ""
    for nom, note in uber.get("a_verifier", {}).items():
        if trouve(nom):
            trouve(nom)["a_verifier"] = note
    for nom, categories in uber.get("categories_produits", {}).items():
        if trouve(nom):
            trouve(nom)["categories"] = categories

    def profil(noms):
        contient, traces, viandes = set(), set(), []
        for n in noms:
            c = trouve(n)
            if not c:
                sys.exit(f"Produit inconnu dans un menu : {n}")
            contient |= set(c["contient"])
            traces |= set(c["traces"])
            viandes += [v for v in c["viandes"] if v != "vegetarien" and v not in viandes]
        trie = lambda e: [a for a in ORDRE_ALLERGENES if a in e]
        return trie(contient), trie(traces - contient), [v for v in ORDRE_VIANDES if v in viandes]

    ordre = {}
    for p in produits:
        ordre[p["categories"][0]] = max(ordre.get(p["categories"][0], 0), p["ordre"])
    for u in uber["produits"]:
        composants = u.get("composants", [])
        contient, traces, viandes = profil(composants) if composants else (u.get("contient", []), u.get("traces", []), list(u.get("viandes", [])))
        if not composants and "remplace" not in u:
            contient, traces = list(u.get("contient", [])), list(u.get("traces", []))
        description = u.get("description", "")
        if not description and composants and u["categories"][0] in ("menus-simples", "menus-wraps", "menus-grill"):
            description = f"1 {trouve(composants[0])['titre']}, servi avec des frites et une boisson 33 cl au choix."
        note = u.get("a_verifier", "")
        if composants and not note:
            note = "allergènes calculés à partir des produits du menu (boisson non comprise) : " + ", ".join(trouve(n)["titre"] for n in composants)
        cat = u["categories"][0]
        ordre[cat] = ordre.get(cat, 0) + 1
        if "remplace" in u:
            p = par_source[u["remplace"]]
            p["contient"] = [a for a in ORDRE_ALLERGENES if a in set(p["contient"]) | set(contient)]
            p["traces"] = [a for a in ORDRE_ALLERGENES if a in (set(p["traces"]) | set(traces)) - set(p["contient"])]
            p["viandes"] = viandes or p["viandes"]
        else:
            p = {"slug": u.get("slug", slug(u["titre"])), "source": u.get("source", "Uber Eats : " + u["titre"]),
                 "contient": contient, "traces": traces, "viandes": viandes, "image": ""}
            produits.append(p)
            par_titre[u["titre"]] = p  # un menu plus loin peut le contenir
        p.update({"titre": u["titre"], "categories": u["categories"], "description": description,
                  "statut": "publish", "ordre": ordre[cat], "a_verifier": note, "badge": u.get("badge", "")})
        if "ingredients" in u:
            p["ingredients"] = u["ingredients"]
    return uber


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    produits = produits_du_tableau(sys.argv[1])
    sources = ["tableau des allergènes (Liste consolidée, version de travail)"]
    categories = list(CATEGORIES)
    if UBER.exists():
        uber = fusion_uber(produits, UBER)
        categories += uber.get("categories", [])
        sources.append(uber["source"])
    ARTICLES.mkdir(exist_ok=True)
    for page in sys.argv[2:]:
        restaurant, articles = extraire_uber(page)
        (ARTICLES / f"{restaurant}.json").write_text(json.dumps(articles, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    pages = {f.stem: json.loads(f.read_text(encoding="utf-8")) for f in sorted(ARTICLES.glob("*.json"))}
    if pages:
        photos_uber(produits, pages)
        sources.append("pages Uber Eats " + ", ".join(pages) + " (photos et descriptions)")
    # Descriptions déjà livrées par un fichier précédent : l'import peut les remplacer tant qu'elles
    # n'ont pas été retouchées dans l'admin (ex. « bacon de dinde » corrigé en « bacon de bœuf »).
    if SORTIE.exists():
        avant = {p["slug"]: p for p in json.loads(SORTIE.read_text(encoding="utf-8"))["produits"]}
        for p in produits:
            a = avant.get(p["slug"], {})
            anciennes = [d for d in a.get("anciennes_descriptions", []) + [a.get("description", "")] if d and d != p["description"]]
            if anciennes:
                p["anciennes_descriptions"] = list(dict.fromkeys(anciennes))
    donnees = {
        "version": datetime.date.today().isoformat(),
        "sources": sources,
        "categories": categories,
        "produits": produits,
    }
    SORTIE.parent.mkdir(parents=True, exist_ok=True)
    SORTIE.write_text(json.dumps(donnees, ensure_ascii=False, indent=1) + "\n", encoding="utf-8")
    publies = sum(1 for p in produits if p["statut"] == "publish")
    print(f"{SORTIE.relative_to(RACINE)} : {len(produits)} produits ({publies} publiés), {len(categories)} catégories")
    for p in produits:
        if p["a_verifier"] and not p["a_verifier"].startswith("allergènes calculés"):
            print(f"  à vérifier : {p['titre']} — {p['a_verifier']}")


if __name__ == "__main__":
    main()
