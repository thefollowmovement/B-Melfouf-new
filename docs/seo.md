# Redirections, référencement, performance, sécurité

Étape 7 du brief (§ 10 à 12). Tout est dans `bigm-core` (`inc/redirections.php`, `inc/seo.php`, `inc/performance.php`, `inc/reprise.php`) et dans le socle (`mu-plugins/bigm-socle.php`). Aucune extension de plus n'est nécessaire, sauf LiteSpeed Cache sur le serveur.

## Redirections 301

**Réglages Big M → Redirections**, une règle par ligne :

```
/nos-restaurants/ /restaurants/      adresse exacte
/boutiques/* /restaurants/*          début d'adresse ; « * » reprend la suite
/app/ [commander]                    lien Commander des Réglages Big M
# commentaire
```

- Une règle ne joue **que si l'adresse n'existe pas** sur le nouveau site : une page créée plus tard à la même adresse reste visible.
- Majuscules et barre finale ne comptent pas. La première règle qui correspond l'emporte.
- Une règle dont la cible est l'adresse elle-même est ignorée, pour éviter les boucles.

Les règles par défaut viennent du plan du site de l'ancien bigm-burger.fr et du brief :

| Ancienne adresse | Nouvelle |
|---|---|
| `/nos-restaurants/`, `/boutique/`, `/storelocator-test/`, `/template-boutique/` | `/restaurants/` |
| `/boutiques/{restaurant}/` | `/restaurants/{restaurant}/` |
| `/contact/`, `/recrutement/` | `/nous-rejoindre/` |
| `/news/`, `/presse/` | `/actualites/` |
| `/category/presse/…` | `/actualites/presse/` |
| `/category/…`, `/tag/…`, `/type/…` | `/actualites/` |
| `/concours/` | `/jeu-concours/` |
| `/app/` | lien Commander |
| `/offre-deliveroo/` | `/promos/` |
| `/le-big/`, `/menu-test/`, `/menu-test-2/`, `/produit/…`, `/categorie-de-produit/…` | `/carte/` (`/categorie-de-produit/burger/` → `/carte/burgers/`) |
| `/privacy-policy/` | `/mentions-legales/` |
| `/accueil-2/` | `/` |

`/devenir-franchise/` garde la même adresse. Les articles repris gardent leur adresse (`/{article}/`).

**Adresses introuvables.** Sous le formulaire de Réglages Big M, un journal liste les 100 dernières adresses demandées qui n'existent pas, les plus fréquentes en premier. Les fichiers (images, scripts) et les sondes de robots (`wp-…`, `xmlrpc`) n'y figurent pas. Après la bascule, une adresse de l'ancien site qui revient souvent mérite une règle.

## Référencement

Le référencement Big M s'efface de lui-même si une extension SEO est active (Yoast, Rank Math, SEOPress, The SEO Framework, All in One SEO, Slim SEO). On peut aussi le couper par le filtre `bigm_seo_actif`.

| Sujet | Où |
|---|---|
| Titre et description | Bloc **Référencement** des pages, articles et produits ; champs en bas des catégories de produits et d'articles. Sans saisie : le titre de WordPress, et l'extrait ou le début du texte (155 caractères). `/carte/` reprend le texte de Réglages Big M → La carte. |
| Ne pas indexer | Case du bloc Référencement : balise `noindex`, page retirée du plan du site |
| Partage (Open Graph, Twitter) | Image du bloc Référencement, sinon image à la une, image de la catégorie, puis **Réglages Big M → Référencement → Image de partage** (1200 × 630), puis le logo |
| Adresse canonique | Celle de WordPress sur les pages ; ajoutée sur la carte, les catégories et les listes d'articles |
| Données structurées | Accueil : `Organization` (logo, réseaux) et `WebSite`. Produits : `MenuItem` (halal, valeurs nutritionnelles par portion) et fil d'Ariane. Catégories : fil d'Ariane. Restaurants : `Restaurant` par le plugin StoreLocatore (`docs/restaurants.md`). |
| Plan du site | Celui de WordPress : `/wp-sitemap.xml`, sans la liste des comptes ni les formats d'articles. Le plugin StoreLocatore ajoute `/tfmsl-sitemap.xml`. |
| Zoom | Autorisé : `width=device-width, initial-scale=1`, sans `maximum-scale` |

Les fiches restaurants gardent le référencement du plugin (onglet SEO de la fiche).

À la bascule, déclarer `https://bigm-burger.fr/wp-sitemap.xml` dans Google Search Console. En préprod, tout est en `noindex` (socle).

## Reprise des articles

**Outils → Reprise des articles.** Elle lit l'API publique de l'ancien site (`/wp-json/wp/v2/posts`), 5 articles par lot ; la page se recharge seule jusqu'au dernier lot.

- Titre, adresse (même slug), date, extrait et texte sont repris. Les vidéos YouTube et Vimeo deviennent leur adresse seule sur une ligne, que WordPress intègre lui-même. Scripts et styles sont retirés.
- Les images de l'ancien site (`/wp-content/uploads/`) sont copiées dans la médiathèque, l'image à la une comprise ; leur texte de remplacement est gardé.
- Catégories : **Presse** pour les anciennes rubriques Presse, Print, Web, Radio et TV, **News** pour les autres. Print, Web, Radio et TV et les anciennes étiquettes deviennent des étiquettes.
- On peut relancer : un article déjà repris (même identifiant d'origine ou même adresse) n'est pas recréé, une image déjà copiée n'est pas recopiée.

L'ancien site doit être en ligne. La reprise ne fait que le lire.

## Performance

| Sujet | Réglage |
|---|---|
| Images | Les tailles générées par WordPress (vignettes, `srcset`) sont en **WebP** quand le serveur sait en produire ; l'original reste tel quel. Les images déjà téléversées gardent leurs tailles JPEG : les régénérer par SSH avec `wp media regenerate --yes`. |
| Chargement différé | WordPress et Elementor posent `loading="lazy"`, sauf sur la première slide du slider (`fetchpriority="high"`) |
| Vidéos | `preload="none"`, chargées quand elles deviennent visibles ; image d'aperçu limitée à 1536 px |
| Fichiers inutiles | Emojis de WordPress retirés ; CSS de l'éditeur de blocs chargé seulement sur les articles et les contenus écrits en blocs |
| Cache | Vidé automatiquement (LiteSpeed Cache) quand on enregistre un slider ou les Réglages Big M, et **aux dates de début et de fin des slides programmées** |

### LiteSpeed Cache (Hostinger)

Réglages conseillés, à vérifier page par page sur la préprod :

1. **Cache** : activé ; pas de cache pour les personnes connectées ; navigateur activé.
2. **Exclusions** : ajouter `/avis/` (collecte d'avis du plugin StoreLocatore, qui contient un jeton de sécurité).
3. **Optimisation de page** :
   - réduire (minify) le CSS et le JS : oui ;
   - combiner le CSS et le JS : **non** (Elementor et Big M ne chargent les fichiers que sur les pages qui en ont besoin) ;
   - « Retarder le JS » et « CSS critique / UCSS » : non, ou après des tests poussés ;
   - **chargement différé des images : non** (WordPress le fait déjà, et la première slide ne doit pas l'être).
4. **Images (QUIC.cloud)** : facultatif, les tailles sont déjà en WebP.
5. Après un déploiement : **LiteSpeed Cache → Purger tout**, et vider le CDN Hostinger s'il est activé.
6. Vérifier ensuite que les formulaires (franchise, recrutement) s'envoient bien depuis une page en cache.

### Mesures

Lighthouse mobile sur la préprod, avant cette étape :

| Page | Performance | LCP | CLS | Accessibilité |
|---|---|---|---|---|
| Accueil | 74 | 6,3 s | 0 | 99 |
| Carte | 99 | 1,7 s | 0 | 100 |
| Fiche produit | 98 | 1,8 s | 0 | 94 |

- La carte et la fiche produit tiennent les cibles (LCP < 2,5 s, CLS < 0,1).
- Sur l'accueil, l'élément le plus lent est une image du widget **Nos Box** (`bigm-box`), chargée en différé alors qu'elle est en haut de page. Il faut lui retirer `loading="lazy"`, lui donner `fetchpriority="high"` et des tailles adaptées (`srcset`).
- La première slide du hero est une vidéo MP4 de 1,8 Mo sans version WebM : en ajouter une (VP9, 3 Mo maximum, brief § 11).
- Le score SEO (~60) vient du `noindex` de la préprod : normal.

## Accessibilité

Audit axe-core (ordinateur et mobile) : aucune erreur sur l'accueil, la carte, une catégorie, une fiche produit, les actualités et un article. Corrections de cette étape :

- niveaux de titres : sections dépliables de la fiche produit, colonnes du pied de page et cartes de la page Actualités en `h2` ;
- contraste des libellés de la barre d'onglets mobile ;
- repère `<main>` aussi sur les pages faites avec le Theme Builder ou le gabarit « Elementor pleine largeur » ;
- boutons des articles repris (blocs de l'éditeur) aux couleurs de la charte.

Reste, dans le plugin StoreLocatore (à corriger dans son dépôt) : les cartes de la liste des restaurants commencent par un `h3` sans `h2` au-dessus.

## Sécurité (socle 0.3)

| Sujet | Réglage |
|---|---|
| En-têtes | `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy` (caméra, micro, paiement et USB coupés, géolocalisation réservée au site). Filtre `bigm_socle_headers`. |
| Comptes | Pas de liste des comptes dans l'API REST pour les visiteurs, pas de pages auteur (`/?author=1` renvoie à l'accueil), pas de nom d'auteur dans les aperçus oEmbed ni dans le plan du site |
| Déjà en place | XML-RPC bloqué, éditeur de fichiers coupé, préprod non indexée |

`Strict-Transport-Security` (HSTS) n'est pas posé par le socle : l'activer dans hPanel une fois le certificat SSL du domaine en place.

## Avant la mise en ligne

1. LiteSpeed Cache installé et réglé comme ci-dessus.
2. Reprise des articles lancée sur le site définitif, puis relue.
3. Régénérer les tailles d'images en WebP (`wp media regenerate --yes`).
4. Image de partage (Réglages Big M → Référencement) et descriptions des pages principales.
5. SMTP authentifié pour les e-mails des formulaires (boîte e-mail Hostinger du domaine, avec une extension SMTP légère).
6. Après la bascule : Search Console (plan du site), journal des adresses introuvables à surveiller les premières semaines.

## Tester

```bash
wp eval-file tests/wp/test-bigm-seo.php --user=IDENTIFIANT_ADMIN
```

Il fait 34 vérifications (redirections, référencement, WebP, cache des slides programmées, reprise sans contacter l'ancien site, en-têtes de sécurité). Il crée une page, un produit, un slider et un article, puis les supprime.
