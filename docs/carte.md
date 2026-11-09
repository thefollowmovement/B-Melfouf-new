# Carte, catégories et fiches produit

Étape 5 du brief. Les trois pages reprennent `prototype/carte.html`, `categorie.html` et `produit.html` : même balisage, même CSS, même script de filtre. Tout leur contenu se gère dans l'admin.

## Les pages

| Adresse | Ce qui s'affiche | Où le client le modifie |
|---|---|---|
| `/carte/` | Barre de catégories, titre et texte, bouton Commander, bannières, vignettes des catégories, bandeau des allergènes | **Réglages Big M → La carte** (titre, texte, bannières, bandeau) ; **Produits → Catégories** (vignettes) |
| `/carte/burgers/` | Barre (Burgers allumé), titre de page et texte de la catégorie, ses bannières, produits regroupés par sous-catégorie, filtres | **Produits → Catégories** (titre de la page, description, bannières) ; **Produits** (viande, allergènes) |
| `/produit/smash-naan/` | Bandeau orange avec la photo et « Revenir à nos burgers », nom, description, Commander, Trouver un restaurant, ingrédients, allergènes, valeurs nutritionnelles, « Ça va bien avec » | **Produits** (fiche du produit) ; **Réglages Big M** (lien Commander général, page Restaurants, note sous les allergènes) |

Les gabarits du thème (`archive-bigm_produit.php`, `taxonomy-bigm_categorie.php`, `single-bigm_produit.php`) affichent ces pages sans rien régler. Leur contenu vient de `bigm-core` (`inc/pages.php`).

## Comportements

**Filtres d'une catégorie.** Ils se comportent comme dans le prototype, côté navigateur :

- **Viande.** Les boutons Tous / Bœuf / Poulet n'apparaissent que si la catégorie a au moins deux viandes (champ « Viande » des produits).
- **Allergènes.** Le panneau masque les produits qui **contiennent** les allergènes cochés. Les traces ne comptent pas : elles sont indiquées sur la fiche.
- **Aucun résultat.** Quand plus rien ne correspond, un message s'affiche avec un bouton pour réinitialiser les filtres.
- **Lien direct.** `/carte/burgers/#allergenes` ouvre le panneau, comme le lien « Allergènes » du pied de page.

**Groupes.** Les produits sont regroupés par sous-catégorie (Bœuf, Poulet), dans l'ordre d'affichage des catégories. Les produits rangés directement dans la catégorie forment un premier groupe, sans titre.

**Catégories vides.** Une catégorie sans produit publié n'apparaît ni dans la barre ni dans la grille. Sa page reste accessible et affiche « Aucun produit dans cette catégorie pour le moment ».

**Fiche produit.**

- **Sections vides.** Une section sans donnée (ingrédients, allergènes, valeurs nutritionnelles) n'est pas affichée.
- **Tableau nutritionnel.** Il montre seulement les lignes remplies. Il indique le poids de la portion s'il est saisi, et un tiret quand l'une des deux colonnes est vide.

**Lien « Revenir à… ».** Il mène à la catégorie de premier niveau du produit et reprend son titre de page : « Nos burgers » donne « Revenir à nos burgers ». Pour un produit rangé dans plusieurs catégories :

- la sous-catégorie l'emporte (Bœuf plutôt que Nouveautés) ;
- entre deux catégories de même niveau, c'est la dernière dans l'ordre de la carte.

Si la catégorie n'a pas de titre de page, ou si ce titre est son nom (« Petite faim »), le lien devient « Revenir à la carte ». Un titre comme « Nos petites faims » donne « Revenir à nos petites faims ».

**Bannières.** Celles d'une sous-catégorie sont celles de sa catégorie parente, si elle n'en a pas. Celles de `/carte/` se choisissent dans Réglages Big M. Le comportement est celui du slider de l'accueil (`docs/accueil.md`), au format bannière.

**Chargement.**

- La photo de la fiche produit et la première bannière sont chargées tout de suite. Les vignettes sont chargées en différé.
- Les vidéos des vignettes se lancent quand elles deviennent visibles.
- Chaque page ne charge que ses styles et ses scripts.

## Composer ces pages dans Elementor (facultatif)

Chaque bloc existe aussi en widget, dans la catégorie **Big M** du panneau :

| Widget | Bloc | Réglages |
|---|---|---|
| Big M — Barre de catégories | Capsule collante sous l'en-tête | Premier lien, catégories vides masquées |
| Big M — En-tête de page | Titre h1, texte, bouton | Selon la page (Réglages Big M ou catégorie affichée) ou saisi ; bouton et lien |
| Big M Slider | Bannières | Source « Bannières de la page affichée » ; « Espace sous les bannières » à couper au-dessus des filtres |
| Big M — Grille de catégories | Vignettes de `/carte/` | Toutes les catégories, ou choisies une par une avec un libellé propre (« Burgers doublés ») |
| Big M — Bandeau des allergènes | Bandeau brun | Titre, texte, bouton, lien |
| Big M — Grille de produits | Produits, filtres, groupes | Catégorie de la page ou choisie ; regroupement ; filtres ; textes des filtres |
| Big M — Présentation du produit | Bandeau orange, nom, description, boutons | Lien de retour, libellés des boutons |
| Big M — Fiche produit | Sections dépliables | Titres, note sous les allergènes, sections ouvertes à l'arrivée |
| Big M — Ça va bien avec | Produits associés | Titre |

Pour remplacer un gabarit du thème par un modèle Elementor Pro (**Modèles → Theme Builder**) :

1. Créer les modèles et leur condition d'affichage :
   - un modèle **Archive** pour `/carte/`, avec la condition de l'archive des produits ;
   - un modèle **Archive** pour les catégories, avec la condition « Catégories de la carte » (taxonomie `bigm_categorie`) ;
   - un modèle **Publication unique** pour les fiches, avec la condition des produits.
2. Y placer un conteneur réglé comme pour l'accueil : pleine largeur, marge intérieure 0, espacement 0.
3. Y ajouter les widgets dans l'ordre du tableau ci-dessus. La barre de catégories colle sous l'en-tête tant que son conteneur est à l'écran.

Attention : un modèle **Archive** avec la condition « Toutes les archives » (pour les actualités, par exemple) s'appliquerait aussi à `/carte/` et aux catégories. Il faut alors exclure l'archive des produits et les catégories de la carte, ou viser seulement les articles.

Dans l'éditeur, les widgets de la fiche montrent le dernier produit publié, et ceux d'une catégorie la première catégorie qui a des produits. Sur le site, ils affichent le produit ou la catégorie de la page.

## Écarts voulus avec le prototype

- **Libellé du lien de retour.** « Revenir à nos burgers » au lieu de « Revenir aux burgers » : la règle doit marcher pour toutes les catégories (« Revenir à notre poulet »).
- **Taille du lien de retour.** Il fait 44 px de haut au lieu de 42, pour respecter la zone tactile minimale du brief.
- **Texte de remplacement des photos.** Les photos des vignettes produit ont un texte de remplacement vide : le nom est déjà écrit dans le lien et serait lu deux fois.
- **Ordre des traces.** Elles suivent l'ordre de la liste réglementaire (Œufs, Sésame, Soja), comme partout sur le site.
- **Mentions de maquette retirées.** La phrase « Données d'exemple pour la maquette » et les valeurs nutritionnelles `[—]` ne sont pas reprises : le tableau n'apparaît que lorsque des valeurs sont saisies.
- **Vignettes de la carte.** La grille montre les catégories réelles. Les vignettes du prototype (« Burgers enflammés », « Sandwichs », « Baos ») s'obtiennent avec la grille « choisies une par une », si ces catégories sont créées.

## Importer la carte

**Produits → Importer la carte** crée les produits et les catégories à partir de `data/carte.json` (dans bigm-core), par lots de 10.

Le fichier est fabriqué par `ops/carte/carte.py` à partir de :
- le tableau des allergènes du client (onglet « Liste consolidée ») ;
- `ops/carte/uber-eats-carte.json`, recopié des cartes Uber Eats de Big M Bondy et Argenteuil (Uber Eats bloque la lecture automatique : pages enregistrées par l'agence) : noms des menus, menus, boissons, sauces vendues à part, descriptions corrigées, catégories en plus (Tex-Mex) ;
- `ops/carte/uber-eats/{restaurant}.json`, extraits des pages Uber Eats enregistrées en HTML (nom, description, adresse de la photo, sans les prix). Photos : Argenteuil d'abord (produit seul, 350 px), sinon Bondy (512 px) ; la photo barbecue d'Argenteuil est écartée (c'est celle de la sauce burger). Descriptions : Bondy seulement (composition) ; celles d'Argenteuil sont des textes commerciaux, parfois faux. Pour ajouter un restaurant, donner sa page enregistrée en argument du script.

Le fichier xlsx n'est pas dans Git. L'onglet « Recettes » (confidentiel) sert seulement à savoir quelle viande contient chaque produit ; l'onglet « Articles » donne les allergènes des sauces vendues à part.

```bash
python3 -I ops/carte/carte.py chemin/Allergenes_BigM.xlsx [chemin/page-uber-eats.html]
```

Les photos sont téléchargées par l'import, sur le serveur, depuis `tb-static.uber.com` : ce sont celles d'Uber Eats (125 produits), en attendant les photos du client.

Non repris, faute d'allergènes dans le tableau : Toast M, Fish M, Big Kroosty (et Spicy), Kids Burger, petit déjeuner (Egg Muffin, Big Egg, Croque Nutella), Mini Donuts, Mini Dooti, Beignet Dubai, Pom'potes, Kinder Surprise, sauces Yoop's et Creamy, Frites cheddar bacon (sans crispy), Grandes frites.

| Sujet | Règle |
|---|---|
| Catégories | Créées si leur adresse n'existe pas (Wraps, Sauces) ; les autres ne changent pas. Burgers → Bœuf / Poulet selon la viande (brief, § 5.1). Nuggets, Tenders et Boîte Mix sont aussi dans Poulet. |
| Allergènes | X du tableau → « Contient », O → « Peut contenir (traces) » |
| Viandes | Plusieurs par produit (Beef Chicken et Monster : bœuf et poulet). D'après les recettes : steak → bœuf ; chicken, escalope, tenders, nuggets, poulet tandoori ou curry → poulet. Fritures et fromages panés : végétarien. Desserts et sauces : aucune. |
| Tex-Mex | Mozza sticks, bouchées camembert, onion rings et chili cheese, comme sur Uber Eats Argenteuil (aussi dans Petite faim) |
| Menus | Sous-catégories de Menus : Burgers doublés (codes M1… W4 du tableau, avec leur nom Uber Eats), Menus simples, Menus wraps, Menus burgers grill, Menus enfant. W1 à W4 : badge « Nouveau » (retirés des Nouveautés le 9 novembre 2026, place aux Box). Allergènes et viandes : ceux des produits du menu réunis (boisson au choix non comprise), plus ceux de la ligne du tableau s'il y en a une. |
| Box | Catégorie Box (ordre 3, entre Menus et Burgers, icône de boîte) : Big Box, M Box, Box Noxx, d'après les affiches des Box (`source` dans `uber-eats-carte.json`). Aussi dans Nouveautés, badge « Nouveau ». Allergènes et viandes calculés comme pour les menus, à partir des produits de la Box (la canette NOXX n'est pas comprise). Pas de photo : à ajouter dans l'admin. |
| Mamamia | Mamamia Spicy, Chicken et Original (pain puccia), en Bœuf ou Poulet et en Nouveautés, et leurs menus en Menus simples, badge « Nouveau ». Ingrédients donnés par l'agence (liste `ingredients` du fichier). Pas dans le tableau du client : allergènes réunis à partir des composants connus (sauce, emmental → lait, pain → gluten, Tenders pour le poulet frit), notés « à vérifier » jusqu'aux fiches fournisseurs (pain puccia, galette de pomme de terre, steak, poulet frit). |
| Codes absents d'Uber Eats (M8, M12, M17) | En brouillon « (nom à compléter) » |
| Bacon | Bacon de bœuf (confirmé par l'agence), même là où Uber Eats écrit « dinde » : produits au bacon en Bœuf, descriptions corrigées |
| Produit déjà présent (même adresse) | Il reçoit les viandes et les allergènes du fichier, qui font foi. Si son nom n'a pas été retouché depuis l'import (ou s'il est provisoire, « nom à compléter »), il suit aussi le fichier pour le nom, les catégories, le statut, l'ordre et le badge ; s'il a été renommé dans l'admin, ils ne changent pas. La description et la liste d'ingrédients suivent le fichier si elles sont vides ou encore celles d'un import précédent (pas retouchées). La photo est ajoutée s'il n'y en a pas, ou remplacée si elle vient d'un import précédent (adresse d'origine différente ; l'ancienne est supprimée si plus rien ne s'en sert). Une photo choisie dans l'admin n'est jamais remplacée. |
| Démonstration | Option, décochée par défaut : les produits de la démo vont à la corbeille (récupérables). Avant, remplacer ceux choisis un par un (carrousel « Nos classiques » de l'accueil, « Ça va bien avec »), sinon ces blocs restent vides. |

**Ajouter quelques produits sans repasser toute la carte :** les mettre dans `uber-eats-carte.json` (et `data/carte.json`), puis `POST /wp-json/bigm/v1/carte/importer` avec `{"produits": ["big-box", "m-box"]}` (administrateur). Seuls ces produits sont importés, avec les catégories qui manquent ; la réponse donne l'état de chacun (`cree` ou `maj`), son lien et les adresses absentes du fichier.

La page de l'import liste les produits **à vérifier** (viande inconnue, nom manquant), avec un lien vers chacun.

Le tableau des allergènes est une **version de travail** (onglet « Points clés ») : soja des burgers au poulet, sauces Delka, fiches manquantes. Une fois validé, relancer le script et l'import : seuls les allergènes et les viandes changent.

## Tester

```bash
wp eval-file tests/wp/test-bigm-carte.php --user=IDENTIFIANT_ADMIN
```

Il fait 55 vérifications :

- widgets enregistrés ;
- lien de retour et catégorie principale ;
- sections de la fiche et tableau nutritionnel ;
- groupes, vignettes et filtres d'une catégorie ;
- barre et grille de catégories ;
- styles et scripts chargés par page, titres d'onglet ;
- réglages de la carte ;
- import de la carte (catégories, produits, nouvel import qui garde les retouches).
