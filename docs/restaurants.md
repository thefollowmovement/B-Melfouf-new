# Restaurants (plugin StoreLocatore)

Les restaurants utilisent le plugin **StoreLocatore** de l'agence (dépôt `thefollowmovement/storelocatore-wordpress`, version 2.1.0). On l'intègre sans le modifier : `bigm-core` (`inc/restaurants.php`) et le thème passent par les filtres et réglages de WordPress.

## Installer

1. Fabriquer le zip, avec le dépôt du plugin cloné à côté de celui-ci (`../storelocatore-wordpress`, ou la variable `STORELOCATOR_DIR`) :

   ```bash
   ops/build-zips.sh
   ```

   Il produit `dist/storelocatore.zip`, sans les notes de développement du plugin.
2. Dans l'admin : **Extensions → Ajouter → Téléverser** `storelocatore.zip`, puis **Activer**.
3. Ouvrir une page de l'admin. Si la démo est installée, elle crée alors la page **Nos restaurants** (`/restaurants/`) et trois restaurants d'exemple, marqués « [Démo] ».

Les restaurants réels se saisissent dans le menu **Restaurants**. On peut aussi les importer en CSV : **Restaurants → Outils** du plugin. Les données sont à fournir par le client : adresse, horaires, services, liens de livraison.

## Ce que fait l'intégration

| Sujet | Réglage |
|---|---|
| Adresses | Fiches sous `/restaurants/{restaurant}/`, liste sous `/restaurants/` (au lieu de `/boutiques/`) |
| Libellés | « Restaurants », « Restaurant » dans l'admin ; textes du site (« Voir le restaurant », « Aucun restaurant ne correspond… ») |
| Liste `/restaurants/` | La page **Nos restaurants** (adresse `restaurants`), composée dans Elementor : en-tête Big M et widget **Store – Locator** du plugin. Sans cette page, la liste du plugin s'affiche. |
| Recherche de l'accueil | Le bloc « Tous nos restaurants » envoie vers `/restaurants/?q=bondy`. La recherche du store locator est remplie et lancée à l'arrivée. |
| Recherche | Elle trouve aussi « saint ouen » pour « Saint-Ouen », et « creteil » pour « Créteil ». |
| Couleurs | Couleurs et police par défaut du plugin = charte Big M (tant que ses réglages n'ont pas été enregistrés) |
| Habillage | `assets/css/restaurants.css` : cartes blanches arrondies, boutons `--btn`, épingles orange, Fredoka et Figtree, fiche d'un restaurant sur fond clair |
| Barre mobile du plugin | Coupée par défaut : la barre d'onglets Big M occupe le bas de l'écran. Si on l'active, elle se place au-dessus. |
| Chargement | Le plugin chargeait jQuery, Leaflet et ses styles sur toutes les pages. Ils ne sont plus chargés que sur les pages restaurants (liste, fiches, page avec un widget du plugin, collecte d'avis). |
| Données structurées | Celles du plugin : `Restaurant` quand le champ « Type de cuisine » est rempli (sinon `LocalBusiness`), fil d'Ariane vers `/restaurants/` |

Les réglages du widget Store – Locator de la page Nos restaurants reprennent la charte : épingles `#fa5c19`, groupes `#c8400a`, cartes blanches à 20 px d'arrondi, colonne de liste beige. Ils se modifient dans Elementor (onglet Style du widget).

## Points à savoir

- **Carte.** Leaflet vient de unpkg.com et les fonds de carte d'OpenStreetMap, deux services externes. Ils sont à citer dans la politique de confidentialité, ou à auto-héberger (étape 7).
- **Recherche.** Elle filtre sur le nom et l'adresse, sans géocodage : « 93 » trouve les codes postaux qui contiennent 93. Le bouton de géolocalisation trie par distance.
- **Description générée.** Le plugin écrit une description SEO automatique quand la fiche n'en a pas (« Découvrez Big M Bondy… »). Il vaut mieux remplir la meta description de chaque fiche.
- **Améliorations possibles du plugin**, à faire dans son propre dépôt :
  - ne charger ses fichiers que sur les pages qui en ont besoin ;
  - étiqueter le champ de recherche ;
  - lire `?q=` lui-même ;
  - ignorer les accents et les tirets dans la recherche.

## Tester

```bash
wp eval-file tests/wp/test-bigm-contenus.php --user=IDENTIFIANT_ADMIN
```

Les vérifications « Restaurants » ne tournent que si le plugin est actif.
