# Big M — refonte de bigm-burger.fr

Nouveau site WordPress de Big M (Hello Elementor + Elementor Pro), développé par The Follow Movement.

- Le brief complet est dans [`CLAUDE.md`](CLAUDE.md).
- La maquette validée par le client est dans [`prototype/`](prototype/). C'est la référence visuelle.

## Contenu du dépôt

| Dossier | Rôle |
|---|---|
| `wp-content/themes/bigm-child/` | Thème enfant : charte (tokens), polices auto-hébergées, CSS et JS communs, en-tête, tiroir, barre d'onglets mobile, pied de page ([`docs/navigation.md`](docs/navigation.md)), gabarits de la carte, des catégories et des fiches produit |
| `wp-content/plugins/bigm-core/` | Contenus gérés dans l'admin : produits, catégories, sliders, réglages ([`docs/bigm-core.md`](docs/bigm-core.md)) ; widgets Elementor Big M ([`docs/accueil.md`](docs/accueil.md), [`docs/carte.md`](docs/carte.md)) ; pages de la carte ; redirections, référencement, performance, reprise des articles ([`docs/seo.md`](docs/seo.md)) |
| `wp-content/plugins/bigm-demo/` | Contenu de démonstration du prototype, pages de départ (accueil, restaurants ; franchise et recrutement sur demande), temporaire ([`docs/accueil.md`](docs/accueil.md), [`docs/pages.md`](docs/pages.md)) |
| `wp-content/mu-plugins/bigm-socle.php` | Préprod non indexée, XML-RPC et éditeur de fichiers coupés, en-têtes de sécurité, réglages de départ |
| `ops/` | Déploiement et mise en place de la préprod Hostinger, par SSH |
| `docs/` | Procédures pas à pas |
| `tests/` | Vérifications lancées avant chaque déploiement |
| `prototype/` | Maquette HTML validée (ne pas modifier) |

`wp-content/` a la même arborescence que sur le serveur.

## Avancement

1. [x] Préprod Hostinger (`preprod.bigm-burger.fr`), WordPress neuf, thème enfant, tokens et polices. Procédure : [`docs/preprod-hostinger.md`](docs/preprod-hostinger.md).
2. [x] Plugin `bigm-core` : types de contenu, taxonomies, champs (meta boxes natives), page de réglages. Détails : [`docs/bigm-core.md`](docs/bigm-core.md).
3. [x] Header capsule, tiroir, barre d'onglets mobile, footer. Détails : [`docs/navigation.md`](docs/navigation.md).
4. [x] Widget Big M Slider, puis l'accueil complet. Détails : [`docs/accueil.md`](docs/accueil.md).
5. [x] Carte, catégorie et fiche produit, avec les filtres allergènes. Détails : [`docs/carte.md`](docs/carte.md).
6. [x] Storelocator, formulaires, actualités. Détails : [`docs/restaurants.md`](docs/restaurants.md), [`docs/pages.md`](docs/pages.md).
7. [x] Redirections, SEO, performance, accessibilité. Détails : [`docs/seo.md`](docs/seo.md).
8. [ ] Recette, puis bascule.

## Commandes

Vérifier le code (syntaxe PHP et JS, charte, absence de Google Fonts) :

```bash
tests/lint.sh
```

Fabriquer les zips à téléverser dans l'admin WordPress (sans accès SSH) :

```bash
ops/build-zips.sh
```

Déployer sur la préprod par SSH (après avoir rempli `ops/.env.preprod`) :

```bash
ops/deploy.sh
```

Tester le plugin bigm-core sur un WordPress de test :

```bash
wp eval-file tests/wp/test-bigm-core.php --user=IDENTIFIANT_ADMIN
```

Tester les widgets Elementor (slider et sections de l'accueil) :

```bash
wp eval-file tests/wp/test-bigm-widgets.php --user=IDENTIFIANT_ADMIN
```

Tester la carte, les catégories (filtres) et les fiches produit :

```bash
wp eval-file tests/wp/test-bigm-carte.php --user=IDENTIFIANT_ADMIN
```

Tester les restaurants, les actualités, les pages de contenu et les formulaires :

```bash
wp eval-file tests/wp/test-bigm-contenus.php --user=IDENTIFIANT_ADMIN
```

Tester les redirections, le référencement, la performance, la reprise des articles et les en-têtes de sécurité :

```bash
wp eval-file tests/wp/test-bigm-seo.php --user=IDENTIFIANT_ADMIN
```

Tester l'en-tête, la barre d'onglets et le pied de page du thème :

```bash
wp eval-file tests/wp/test-bigm-child.php --user=IDENTIFIANT_ADMIN
```

Préparer une installation WordPress neuve (une fois, après le premier déploiement) :

```bash
ops/provision.sh
```

Réappliquer la charte au kit Elementor, sur le serveur, après un changement de couleur :

```bash
wp bigm kit --user=IDENTIFIANT_ADMIN
```

Ouvrir le prototype en local, puis aller sur http://localhost:8000 :

```bash
cd prototype && python3 -m http.server 8000
```
