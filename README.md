# Big M — refonte de bigm-burger.fr

Nouveau site WordPress de Big M (Hello Elementor + Elementor Pro), développé par The Follow Movement.

- Le brief complet est dans [`CLAUDE.md`](CLAUDE.md).
- La maquette validée par le client est dans [`prototype/`](prototype/). C'est la référence visuelle.

## Contenu du dépôt

| Dossier | Rôle |
|---|---|
| `wp-content/themes/bigm-child/` | Thème enfant : charte (tokens), polices auto-hébergées, CSS et JS communs |
| `wp-content/mu-plugins/bigm-socle.php` | Préprod non indexée, XML-RPC coupé |
| `ops/` | Déploiement et mise en place de la préprod Hostinger, par SSH |
| `docs/` | Procédures pas à pas |
| `tests/` | Vérifications lancées avant chaque déploiement |
| `prototype/` | Maquette HTML validée (ne pas modifier) |

`wp-content/` a la même arborescence que sur le serveur. Le plugin `bigm-core` y arrivera à l'étape 2.

## Avancement

1. [x] Préprod Hostinger, WordPress neuf, thème enfant, tokens et polices. Le code est prêt ; la mise en place sur Hostinger est décrite dans [`docs/preprod-hostinger.md`](docs/preprod-hostinger.md).
2. [ ] Plugin `bigm-core` : types de contenu, taxonomies, champs, page de réglages.
3. [ ] Header capsule, tiroir, barre d'onglets mobile, footer.
4. [ ] Widget Big M Slider, puis l'accueil complet.
5. [ ] Carte, catégorie et fiche produit, avec les filtres allergènes.
6. [ ] Storelocator, formulaires, actualités.
7. [ ] Redirections, SEO, performance, accessibilité.
8. [ ] Recette, puis bascule.

## Commandes

Vérifier le code (syntaxe PHP et JS, charte, absence de Google Fonts) :

```bash
tests/lint.sh
```

Déployer sur la préprod (après avoir rempli `ops/.env.preprod`) :

```bash
ops/deploy.sh
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
