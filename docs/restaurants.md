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
| Google Place ID | Champ du plugin (avis Google, horaires, lien « Laisser un avis »), aussi modifiable par l'API REST : `POST /wp-json/wp/v2/tfmsl_store/{id}` avec `{"meta": {"tfmsl_gmb_place_id": "ChIJ…"}}` (comptes qui modifient le restaurant), pour une saisie en lot. Coordonnées GPS (`tfmsl_lat`, `tfmsl_lng`) modifiables de la même façon. `GET /wp-json/bigm/v1/restaurants/{id}/place-id` propose les Place ID possibles, avec leurs coordonnées (recherche Google « Big M » + adresse, près de la fiche) avec la clé Google des réglages de StoreLocatore, qui reste sur le serveur ; rien n'est enregistré |
| Référencement | Titre, description et Open Graph des fiches viennent du plugin ; ceux de bigm-core s'effacent sur ces pages (`docs/seo.md`). |

Le widget Store – Locator de la page Nos restaurants est en habillage « Aux couleurs du site ». Ses réglages (hauteur, textes, mode mobile « Carte d'abord ») se modifient dans Elementor.

## Horaires importés de Google (pause de midi)

StoreLocatore 2.5 importe les horaires de Google (bouton « Synchroniser » de la fiche, import quotidien des fiches en synchronisation automatique), mais garde une seule plage par jour : un restaurant ouvert 11:15–14:00 puis 18:30–23:00 n'avait plus que 18:30–23:00. bigm-core (`inc/horaires.php`) corrige juste après chaque import, sans modifier le plugin : il relit les plages reçues de Google et réécrit les horaires de la fiche (première ouverture, dernière fermeture, et la plus longue coupure comme pause, que la fiche affiche et compte pour « Ouvert / Fermé »). StoreLocatore 2.5.1 et suivants gardent eux-mêmes la pause (et lisent un lieu ouvert 24 h sur 24) : la correction de bigm-core s'efface alors. `POST /wp-json/bigm/v1/restaurants/{id}/horaires-google` relance l'import d'un restaurant (tous d'un coup : la boucler sur les restaurants). Import quotidien automatique : case « Synchronisation automatique » de la fiche (onglet Intégrations), aussi modifiable par l'API REST (meta `tfmsl_gmb_sync` = `1`).

## Food courts (Big Canteen)

Certains Big M sont dans un **Big Canteen**, le food court du groupe : plusieurs enseignes (Big M, Fatburger, Buffalo's Café, Original Riz Crousty, ADS…) partagent la même salle. bigm-core (`inc/food-court.php`) :

| Où | Quoi |
|---|---|
| Restaurants → Food courts | Un food court : nom, présentation (description), logo carré, marques par défaut (une par ligne), site web (bouton « Découvrir le … ») |
| Fiche d'un restaurant | Case du food court (bloc « Food courts ») ; bloc « Food court : marques sur place » si ses enseignes diffèrent de celles du food court |
| Site | Bloc « Ce Big M est au Big Canteen » sous le bandeau de la fiche (crochet `tfmsl_after_store_hero` du gabarit de StoreLocatore), avec la présentation et les marques ; shortcode `[bigm_food_court]` pour un modèle Elementor de fiche. CSS (`assets/css/food-court.css`) chargé seulement sur ces fiches |

Le Place ID d'un restaurant de food court est celui du food court sur Google (fiche « Big Canteen »). `GET /wp-json/bigm/v1/restaurants/{id}/place-id?q=Big Canteen Mérignac` cherche un nom libre. Food courts, champs et marques sont aussi dans l'API REST (`/wp/v2/bigm_food_court`, meta `_bigm_fc_logo`, `_bigm_fc_marques`, `_bigm_fc_site` ; meta `_bigm_fc_marques` du restaurant).

## Fenêtre Commander

Les boutons Commander du site (en-tête, bouton central mobile, fiches produit sans lien propre, widgets) ouvrent une fenêtre, gérée par bigm-core (`inc/commander.php`, `assets/js/commander.js`) :

1. **Choix du restaurant.** « Me localiser » (le plus proche est choisi) ou recherche par ville, code postal ou nom (accents et tirets ignorés).
2. **Façons de commander de ce restaurant**, dans l'ordre des plateformes :
   - un bouton par plateforme pour laquelle il a un lien (Uber Eats, Deliveroo…) ;
   - « Commander en ligne » si son lien Commander StoreLocatore n'est d'aucune plateforme connue ;
   - « Appeler pour commander » si la case Téléphone est cochée (restaurant qui livre lui-même).
   - Sans rien de tout cela : le lien Commander par défaut (« Tous les restaurants Big M »).
3. **Mémoire.** Le restaurant reste choisi dans le navigateur (stockage local, rien n'est envoyé) : à la visite suivante, la fenêtre s'ouvre sur lui (bouton « Changer »). Il devient le restaurant choisi quand on le choisit, par la position, ou quand on commande chez lui.

**Promos.** La fenêtre et les promos de StoreLocatore partagent la même mémoire (clé `tfmsl_promo_store`) : la page Promos et le bouton flottant montrent directement les offres du restaurant choisi pour commander, et inversement.

| Où | Quoi |
|---|---|
| Réglages Big M → Commande | Fenêtre active ou non, lien Commander par défaut, titre et texte, **plateformes** (nom, texte, couleur, logo carré, ordre par glisser-déposer ; vider le nom la retire), textes du bouton d'appel |
| Restaurants → Liens de commande | Tous les restaurants sur une page : un lien par plateforme, case Téléphone. En gris : le lien Commander de la fiche StoreLocatore, utilisé tant que le champ est vide (rangé sous Uber Eats s'il vise ubereats.com…) |
| Fiche d'un restaurant, bloc « Commander » | Les mêmes champs, plus le numéro à appeler s'il diffère du téléphone de la fiche |

- Un bouton d'Elementor ouvre la fenêtre avec le lien `#commander` ; une adresse `…/#commander` l'ouvre à l'arrivée. Les boutons Commander de StoreLocatore (liste, fiche) l'ouvrent sur leur restaurant.
- Les liens saisis sont nettoyés : paramètres de suivi retirés (`?srsltid=…` ajouté par Google, `utm_…`), adresse et heure de livraison de la personne qui a copié le lien retirées (`pl=` d'Uber Eats, `geohash=` de Deliveroo), page française d'Uber Eats (`/fr/` au lieu de `/fr-en/`).
- Saisie en lot par l'API (comptes qui modifient les restaurants, mot de passe d'application) : `GET /wp-json/bigm/v1/commander/restaurants` lit les liens saisis ; `POST` avec `{"restaurants": {"123": {"liens": {"ubereats": "…", "deliveroo": "…"}, "tel": "1"}}}` les enregistre (seules les clés envoyées changent, un lien vide retire le bouton).
- Données : `GET /wp-json/bigm/v1/commander` (plateformes et restaurants publiés), lu à la première ouverture, en cache 5 minutes. Le script (19 Ko, 6 Ko compressé, différé) est sur toutes les pages ; son CSS n'est chargé qu'à l'ouverture.
- Sans JavaScript, ou fenêtre désactivée, les boutons mènent au lien Commander par défaut.

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

Les vérifications « Restaurants » et « Fenêtre Commander » ne tournent que si le plugin est actif.
