# Big M Slider et page d'accueil

L'accueil est une page Elementor construite avec les widgets de la catégorie **Big M** du panneau. Chaque widget reprend le balisage et le CSS du prototype. Son contenu se modifie dans ses réglages, sans code.

## Les widgets

| Widget | Section du prototype | Ce qui se règle |
|---|---|---|
| **Big M Slider** | Slider « à la une », bannières | Slider (menu Sliders) ou bannières de la catégorie affichée ; format hero 2:1 (photo 6 s) ou bannière 3:1, 2:1 sur mobile (photo 5 s) ; nom du carrousel ; titre h1 caché |
| **Big M — Bande appli** | Bande orange | Image du téléphone, titre, texte, avantages (un par ligne) ; badges des stores : Réglages Big M |
| **Big M — Restaurants** | « Tous nos restaurants » | Textes, deux liens, photo et légende ; la recherche ouvre `/restaurants/?q=…` (page Restaurants des Réglages Big M) |
| **Big M — Services** | Sur place, click & collect, livraison | Cartes : photo, cadrage, titre, texte, lien |
| **Big M — Classiques** | « Découvrez nos classiques » | Produits choisis un par un (dans l'ordre) ou d'une catégorie ; bouton « Voir la carte » |
| **Big M — Affiches** | « Big M les a réunis pour vous » | Affiches : année, image 9:16, légende |
| **Big M — Actualités** | « Actualités et presse » | Nombre d'articles, catégories ; lien vers la page des articles |
| **Big M — Nous rejoindre** | Franchise et recrutement | Photos, textes, boutons ; chiffres clés tirés de Réglages Big M |

Les ancres (`appli`, `restaurants`, `legendes`, `actus`, `franchise`, `recrutement`) servent aux liens des menus, comme `/#appli`.

Le CSS et le JS de ces widgets ne sont chargés que sur les pages qui les utilisent (`bigm-slider`, `bigm-accueil`, `bigm-tuiles`, `bigm-carrousel`, `bigm-tuiles-video`).

### Construire une page avec ces widgets

Les widgets rendent une section complète, avec sa marge et sa largeur. Il faut donc les placer dans un conteneur Elementor réglé ainsi :

- Largeur du contenu : **pleine largeur** ;
- Marge intérieure : **0** ;
- Espacement entre éléments : **0**.

Modèle de page : **Elementor pleine largeur** (en-tête et pied de page du thème, sans titre de page).

## Big M Slider

Le comportement est celui de `prototype/assets/site.js`, sans changement :

- slide centrée avec les voisines visibles, défilement en boucle ;
- glisser au doigt ou à la souris avec l'élan, ressort sans rebond ;
- navigation par flèches, points et clavier ;
- pause au survol, hors de l'écran et avec le bouton pause ;
- une vidéo passe à la suivante quand elle se termine.

Côté chargement :

- la première slide est chargée tout de suite (`fetchpriority="high"`), les autres en différé ;
- l'image mobile passe par `<picture>` ;
- les vidéos ne se chargent qu'au moment de les lire (`preload="none"`) ;
- le script choisit entre MP4 et WebM (`window.bigmVideoSrc(src, webm)`, l'URL WebM étant un média à part dans WordPress).

Les dates de début et de fin des slides sont appliquées au moment de l'affichage. Avec un cache de pages (LiteSpeed, étape 7), il faudra vider le cache aux dates de changement.

## Contenu de démonstration (extension temporaire)

L'extension **Big M — contenu de démonstration** (`wp-content/plugins/bigm-demo/`, zip `bigm-demo.zip`) remplit la préprod avec les visuels du prototype. Depuis Outils → Démo Big M, elle crée :

- les 40 visuels et vidéos du prototype dans la médiathèque, marqués « [Démo] » ;
- 3 sliders : accueil, offres de la carte, bannières Burgers ;
- 10 produits avec photos, vidéos de vignette, viande et allergènes du prototype ;
- les vignettes des catégories ;
- 3 articles (News et Presse) ;
- la page **Accueil** avec les 8 widgets, définie comme page d'accueil, et la page **Actualités**, définie comme page des articles.

Avant la mise en ligne, il faut faire deux choses :

1. Remplacer les images de la page d'accueil par celles du client.
2. Cliquer sur « Supprimer le contenu de démonstration », puis désactiver et supprimer l'extension.

La suppression retire les produits, sliders, articles et médias de démo, et garde les pages Accueil et Actualités. Les vidéos de démonstration, simples zooms générés à partir des photos, disparaissent avec elle, comme le demande le brief.

Les visuels ne sont pas en double dans Git : `ops/build-zips.sh` les copie depuis `prototype/assets/` dans le zip.

## Points Elementor à connaître

- `.elementor a { box-shadow: none }` et `.elementor img { height: auto; border-radius: 0 }` sont plus spécifiques que certaines classes du prototype. Des règles de compatibilité les corrigent, juste après la règle d'origine : bouton à contour dans `base.css`, images dans `slider.css`, `tuiles.css` et `accueil.css`.
- L'« optimisation du chargement des images » d'Elementor ajoute `loading` et `fetchpriority` à toutes les images, même quand ils existent déjà. Les widgets laissent donc WordPress et Elementor poser `loading`. Seule la première slide force `fetchpriority="high"`, pour ne pas laisser cette priorité au logo de l'en-tête. Le doublon d'attribut qui en résulte est sans effet.
- Dans l'éditeur, un widget modifié est ré-affiché sans recharger la page. Le slider, le carrousel et les vidéos se ré-initialisent alors seuls (`frontend/element_ready/widget`).

## Tester

```bash
wp eval-file tests/wp/test-bigm-widgets.php --user=IDENTIFIANT_ADMIN
```

Il fait 18 vérifications :

- formats et intervalles du slider ;
- dates des slides ;
- chargement de la première slide ;
- recherche de restaurants ;
- ordre des classiques ;
- chiffres de la franchise ;
- date des actualités.
