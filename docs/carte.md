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

Le fichier est fabriqué par `ops/carte/carte.py` à partir de deux sources :
- le tableau des allergènes du client (onglet « Liste consolidée ») ;
- si on l'a, la page Uber Eats d'un restaurant, enregistrée depuis le navigateur : descriptions, photos, menus, boissons.

Le fichier xlsx n'est pas dans Git. L'onglet « Recettes » (confidentiel) sert seulement à savoir quelle viande contient chaque produit.

```bash
python3 -I ops/carte/carte.py chemin/Allergenes_BigM.xlsx [chemin/page-uber-eats.html]
```

| Sujet | Règle |
|---|---|
| Catégories | Créées si leur adresse n'existe pas (Wraps, Sauces) ; les autres ne changent pas. Burgers → Bœuf / Poulet selon la viande (brief, § 5.1). Nuggets, Tenders et Boîte Mix sont aussi dans Poulet. |
| Allergènes | X du tableau → « Contient », O → « Peut contenir (traces) » |
| Viandes | Plusieurs par produit (Beef Chicken et Monster : bœuf et poulet). D'après les recettes : steak → bœuf ; chicken, escalope, tenders, nuggets, poulet tandoori ou curry → poulet. Fritures et fromages panés : végétarien. Desserts et sauces : aucune. |
| Codes M1 à M17, W1 à W3 | Créés en brouillon « (nom à compléter) » : le tableau ne donne pas leur nom |
| Produit déjà présent (même adresse) | Il garde son nom, ses catégories, son statut, sa description et sa photo ; il reçoit les viandes et les allergènes du fichier, qui font foi. Sa description et sa photo ne sont remplies que si elles sont vides. |
| Démonstration | Option, décochée par défaut : les produits de la démo vont à la corbeille (récupérables). Avant, remplacer ceux choisis un par un (carrousel « Nos classiques » de l'accueil, « Ça va bien avec »), sinon ces blocs restent vides. |

La page de l'import liste les produits **à vérifier** (viande inconnue, nom manquant), avec un lien vers chacun.

Le tableau des allergènes est une **version de travail** (onglet « Points clés ») : soja des burgers au poulet, sauces Delka, fiches manquantes. Une fois validé, relancer le script et l'import : seuls les allergènes et les viandes changent.

## Tester

```bash
wp eval-file tests/wp/test-bigm-carte.php --user=IDENTIFIANT_ADMIN
```

Il fait 46 vérifications :

- widgets enregistrés ;
- lien de retour et catégorie principale ;
- sections de la fiche et tableau nutritionnel ;
- groupes, vignettes et filtres d'une catégorie ;
- barre et grille de catégories ;
- styles et scripts chargés par page, titres d'onglet ;
- réglages de la carte ;
- import de la carte (catégories, produits, nouvel import qui garde les retouches).
