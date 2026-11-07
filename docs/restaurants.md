# Restaurants (plugin StoreLocatore)

Les restaurants utilisent le plugin **StoreLocatore** de l'agence (dépôt `thefollowmovement/storelocatore-wordpress`, version 2.5 ou plus). On l'intègre sans le modifier : `bigm-core` (`inc/restaurants.php`) passe par les filtres et réglages de WordPress.

Depuis sa version 2.2, le plugin fait lui-même ce que Big M lui demandait :

- il ne charge ses fichiers (jQuery, Leaflet, styles) que sur les pages restaurants ;
- il lit la recherche `?q=` venue de l'accueil ;
- sa recherche ignore les accents, les majuscules et les tirets ;
- son habillage « Aux couleurs du site » reprend les couleurs et polices du kit Elementor, donc la charte.

L'ancien habillage de bigm-core (`restaurants.css`, `restaurants.js`) et le gabarit `archive-tfmsl_store.php` du thème ont donc été retirés.

## Installer ou mettre à jour

1. Fabriquer le zip, avec le dépôt du plugin cloné à côté de celui-ci (`../storelocatore-wordpress`, ou la variable `STORELOCATOR_DIR`) :

   ```bash
   ops/build-zips.sh
   ```

   Il produit `dist/storelocatore.zip`, sans les notes de développement du plugin. On peut aussi prendre le zip directement dans le dépôt du plugin.
2. Dans l'admin : **Extensions → Ajouter → Téléverser**, puis **Remplacer la version actuelle** si le plugin est déjà là.
3. Ne jamais téléverser une version plus ancienne que celle installée.

Si la démo est installée, elle crée une fois la page **Nos restaurants** (`/restaurants/`) et trois restaurants d'exemple, marqués « [Démo] ». Une page qui existe déjà à cette adresse n'est pas touchée.

Les restaurants réels se saisissent dans le menu **Restaurants**, ou s'importent en CSV (**Restaurants → Outils** du plugin, horaires compris). Les données sont à fournir par le client : adresse, horaires, services, liens de livraison.

## Ce que fait l'intégration

| Sujet | Réglage |
|---|---|
| Adresses | Fiches sous `/restaurants/{restaurant}/` (au lieu de `/boutiques/`). Pas de liste du plugin : `/restaurants/` est la page Elementor qui contient le widget **Store – Locator**. Le plugin la retrouve seul (fil d'Ariane, lien « retour à la liste »). |
| Libellés | « Restaurants », « Restaurant » dans l'admin ; textes du site (« Voir le restaurant », « Aucun restaurant ne correspond… »). Les surcharges de la page **Traductions** du plugin restent prioritaires. |
| Style des fiches | « Clair, aux couleurs du site » par défaut (réglage du plugin, tant qu'il n'a pas été enregistré) |
| Barre d'actions mobile du plugin | Coupée par défaut : la barre d'onglets Big M occupe le bas de l'écran. Si on l'active, elle se place au-dessus. |
| Recherche de l'accueil | Le bloc « Tous nos restaurants » envoie vers `/restaurants/?q=bondy`. Le plugin remplit la recherche et filtre à l'arrivée. |
| Données structurées | Celles du plugin : `Restaurant` quand le « Type de cuisine » est rempli, `LocalBusiness` sinon. Sans saisie, bigm-core lui donne « Burgers halal », donc toujours `Restaurant`. |
| Référencement | Titre, description et Open Graph des fiches viennent du plugin ; ceux de bigm-core s'effacent sur ces pages (`docs/seo.md`). |

Le widget Store – Locator de la page Nos restaurants est en habillage « Aux couleurs du site ». Ses réglages (hauteur, textes, mode mobile « Carte d'abord ») se modifient dans Elementor.

## Points à savoir

- **Carte.** Leaflet vient de unpkg.com et les fonds de carte d'OpenStreetMap : deux services externes, à citer dans la politique de confidentialité.
- **Recherche.** Elle filtre sur le nom et l'adresse, sans géocodage : « 93 » trouve les codes postaux qui contiennent 93. Le bouton de géolocalisation trie par distance.
- **Description générée.** Le plugin écrit une description automatique quand la fiche n'en a pas (« Découvrez Big M Bondy… »). Il vaut mieux remplir la meta description de chaque fiche (onglet SEO de la fiche).
- **Promos.** Le module Promos du plugin (bouton flottant, page Promos) est indépendant. Sur mobile, régler le décalage du bouton flottant (**Promos → Réglages**, ~100 px) pour qu'il passe au-dessus de la barre d'onglets.
- **Anciennes adresses.** `/nos-restaurants/`, `/boutique/` et `/boutiques/…` de l'ancien site sont redirigées vers `/restaurants/…` (`docs/seo.md`).

## Tester

```bash
wp eval-file tests/wp/test-bigm-contenus.php --user=IDENTIFIANT_ADMIN
```

Les vérifications « Restaurants » ne tournent que si le plugin est actif.
