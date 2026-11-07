# Recette de la préprod

Étape 8 du brief, première partie. Recette faite le 7 novembre 2026 sur `preprod.bigm-burger.fr`, avec bigm-core 0.6.0, bigm-child 0.6.0, socle 0.3.0, démo 0.4.0 et StoreLocatore 2.5.0. Les corrections sont dans les versions suivantes : bigm-core et bigm-child 0.6.1, démo 0.5.0, Nos Box 1.0.1.

## Ce qui a été vérifié

| Vérification | Outil | Portée |
|---|---|---|
| Toutes les pages : statut, erreurs JavaScript, ressources en erreur, titres, h1, descriptions, images | Playwright (Chromium), robot de parcours | 150 pages, ordinateur (1366 px) et mobile (Pixel 7 émulé) |
| Débordement horizontal sur mobile, barre d'onglets | idem | 150 pages |
| Performance, accessibilité, bonnes pratiques, SEO | Lighthouse 12, mobile | 8 pages types |
| Accessibilité détaillée | axe-core | pages Big M, ordinateur et mobile |
| Anciennes adresses | `curl`, toutes les adresses des plans du site de bigm-burger.fr | 138 adresses |
| En-têtes de sécurité, API des comptes, robots.txt | `curl` | — |

Safari (iOS) et Chrome (Android) sur de vrais téléphones restent à faire : voir « À tester sur téléphone ».

## Résultats

**Parcours (150 pages).**
- Aucune erreur JavaScript, aucun débordement horizontal sur mobile, barre d'onglets présente partout (sauf la page autonome `/avis/` du plugin, normal).
- 6 pages en 404, corrigées dans cette livraison :
  - `/actualites/news/` et `/actualites/presse/` ;
  - `/mentions-legales/`, `/politique-de-confidentialite/`, `/cookies/`, `/jeu-concours/`.

**Lighthouse mobile.**

| Page | Perf. | Access. | Bonnes pratiques | LCP | CLS |
|---|---|---|---|---|---|
| Accueil | 80 | 100 | 100 | 4,4 s | 0,007 |
| Carte | 99 | 100 | 100 | 1,9 s | 0 |
| Catégorie Burgers | 100 | 100 | 100 | 1,6 s | 0 |
| Fiche produit | 100 | 100 | 100 | 1,5 s | 0 |
| Actualités | 95 | 93 | 100 | 2,8 s | 0 |
| Devenir franchisé | 100 | 96 | 100 | 1,5 s | 0 |
| Nous rejoindre | 99 | 96 | 100 | 2,1 s | 0 |
| Restaurants | 95 | 96 | 96 | 2,6 s | 0 |

- Le score SEO (69 partout) ne perd des points que sur le `noindex` de la préprod : normal.
- Accueil : l'élément le plus lent est le visuel de **Nos Box**, chargé en différé. Corrigé dans Nos Box 1.0.1 (premier visuel chargé tout de suite).
- Restaurants : « bonnes pratiques » à 96 seulement parce que unpkg.com (Leaflet) était bloqué depuis le poste de test.

**Anciennes adresses.** Les 138 adresses des plans du site de bigm-burger.fr aboutissent à une page (directement, ou en une seule redirection 301), sauf `/concours/`, `/privacy-policy/` et `/politique-de-confidentialite/`, qui visaient les pages créées dans cette livraison.

**Sécurité.** En-têtes présents (`nosniff`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`), `/wp-json/wp/v2/users` fermé aux visiteurs, `noindex` en préprod.

## Corrigé dans cette livraison

| Problème | Correction |
|---|---|
| Filtres News / Presse de `/actualites/` en 404 | Les règles d'adresses avaient été recalculées avec l'ancienne base des catégories. bigm-core les recalcule de lui-même dès qu'elles ne connaissent pas `/actualites/…`. |
| Pages légales et jeu concours absentes | Créées par la démo 0.5 (une fois, gardées si on supprime la démo) : textes de l'ancien site, mis à jour (`docs/pages.md`) |
| Accueil lent sur mobile | Nos Box 1.0.1 : visuel de la première Box chargé tout de suite |
| Liens « Recrutement » et « Postuler » via une redirection | Par défaut vers `/nous-rejoindre/` (pied de page, carte « Recrutez-nous ! » de l'accueil) |
| Contraste de la barre d'onglets sur la carte des restaurants (4,39:1) | Gris `#706056` et onglet actif plus foncé : au moins 5:1 sur tout fond |
| 8 catégories sans description | Phrase générique quand la description est vide |
| Vidéos YouTube des articles repris | Mode « confidentialité renforcée » (pas de cookie avant la lecture) |
| Icône Restaurants de l'en-tête mobile (CSS additionnel) | Reprise dans le thème, en 44 × 44 px (taille tactile du brief) |

## Vérification après installation (7 novembre, bigm-core et bigm-child 0.6.1)

- Filtres News / Presse, mentions légales, confidentialité, cookies et jeu concours : en ligne (200), 0 erreur d'accessibilité sur ordinateur et mobile.
- `/concours/` → `/jeu-concours/`, `/privacy-policy/` → `/mentions-legales/`, `/recrutement/` → `/nous-rejoindre/`.
- « Postuler » de l'accueil et icône Restaurants de l'en-tête mobile : à jour.
- Accueil (Lighthouse mobile) : LCP encore à 4,3 s. Le visuel de Nos Box était bien chargé tout de suite, mais restait invisible jusqu'au script des apparitions, chargé en dernier.
  - Corrigé dans bigm-child 0.6.2 : les blocs déjà à l'écran s'affichent dès la fin de la page.
  - Le reste vient du temps de réponse du serveur (0,6 s, pas de cache de pages) : installer LiteSpeed Cache (`docs/seo.md`).
- Second parcours (160 pages, ordinateur et mobile) : aucune erreur JavaScript, aucun débordement sur mobile. Une régression trouvée et corrigée dans bigm-core 0.6.3 :
  - la pagination de la page Actualités (`/actualites/page/2/`) répondait en 404 ;
  - cause : les règles des catégories, maintenant sous `/actualites/`, passaient avant elle et lisaient « page/2 » comme un nom de catégorie ;
  - correction : une règle de pagination placée en tête. bigm-core la remet de lui-même si elle manque.
- Pastilles Tout / News / Presse du modèle « Blog — Actualités » : « Tout » (un widget Bouton) n'est pas aligné avec les deux autres (le widget Catégories de WordPress) et n'est jamais marqué actif. bigm-core 0.6.2 ajoute le shortcode `[bigm_categories_articles]`, qui affiche les trois pastilles ensemble (voir ci-dessous).

## À corriger dans l'admin (préprod)

Ces réglages sont dans des pages Elementor faites sur la préprod. Ils ne viennent pas du code.

1. **`/restaurants/`.**
   - Il n'y a pas de titre `h1`. Ajouter le widget **Big M — En-tête de page** au-dessus du store locator (titre « Nos restaurants », texte, bouton Commander).
   - Dans le widget Store – Locator, changer le titre « Trouvez votre boutique » en « Trouvez votre restaurant », ou le vider.
2. **`/devenir-franchise/` et `/nous-rejoindre/`, bandeau orange.**
   - Le texte blanc de 14 à 17 px sur l'orange `#fa5c19` n'a qu'un contraste de 3,17:1, sous le minimum AA de 4,5:1.
   - Passer ces textes en brun (`#2b1406`), ou au moins en 24 px.
   - Pour les boutons, prendre l'orange des boutons `#c8400a` avec texte blanc (brief, § 3), ou un bouton blanc à texte brun.
3. **`/actualites/` (modèle Theme Builder « Blog — Actualités »).**
   - Les vignettes des articles sont des liens sans nom quand l'image n'a pas de texte alternatif : remplir le texte alternatif des images à la une.
   - Le lien « Précédent » désactivé de la pagination est trop pâle (2,96:1).
   - Le rôle ARIA posé sur les `<article>` n'est pas autorisé.
   - Les liens « Lire la suite » portent un nom caché différent de leur texte : laisser le texte du lien vide dans le widget, ou le masquer.
   - Pastilles : remplacer le bouton « Tout » et le widget Catégories par un widget **Code court** contenant `[bigm_categories_articles]`.
4. **Lien Commander** (Réglages Big M) :
   - retirer le paramètre de suivi `?srsltid=…` ;
   - viser la page française d'Uber Eats (`/fr/` au lieu de `/fr-en/`).
5. **Menu du pied de page « L'entreprise »** (Apparence → Menus) : le lien « Recrutement » pointe encore vers `/recrutement/` (menu créé à l'installation avec les liens du prototype). Le passer à `/nous-rejoindre/`. La redirection marche en attendant.
6. **CSS additionnel** (Apparence → Personnaliser) : le vider une fois le thème 0.6.1 installé, l'icône Restaurants est maintenant dans le thème.

## À signaler dans le dépôt de StoreLocatore

- Cartes de la liste des restaurants : un `h3` sans `h2` au-dessus (ordre des titres).
- Fiche d'un restaurant : `<img class="tfmsl-lightbox-img" src="">` vide dans la page (visionneuse), à ne remplir qu'à l'ouverture.
- Widget Store – Locator : titre par défaut « Trouvez votre boutique », enregistré dans la page et donc non traduit par bigm-core.
- Page `/avis/{restaurant}/` : pas de `h1`.

## À tester sur téléphone

Sur un iPhone (Safari) et un Android (Chrome), en 4G :

- [ ] Accueil : le slider défile au doigt avec élan, la vidéo de la première slide se lance sans son ; pause quand on sort de l'écran.
- [ ] En-tête : il s'affine au défilement ; le tiroir s'ouvre et se ferme ; l'icône Restaurants mène à `/restaurants/`.
- [ ] Barre d'onglets : au-dessus de la barre de Safari, sans chevauchement (zone sûre de l'iPhone).
- [ ] Carte et catégorie : barre des catégories, filtres viande et allergènes, message « aucun produit ».
- [ ] Fiche produit : sections dépliables, « Ça va bien avec ».
- [ ] Restaurants : recherche (`?q=` depuis l'accueil), « Me localiser » (autorisation de position), liste / carte, itinéraire, appel.
- [ ] Formulaires : envoi d'une candidature franchise, et d'une candidature recrutement avec un CV pris dans le téléphone ; réception des e-mails, entrées dans WPForms.
- [ ] Pages légales, jeu concours, actualités et un article repris.
- [ ] Réglage « Réduire les animations » du téléphone : plus d'animation ni de lecture automatique.

## Contenus encore attendus du client (brief, § 13)

- Photos et vidéos définitives des produits, bannières de campagne.
- Liste complète des produits avec allergènes et valeurs nutritionnelles.
- Horaires, services et liens de livraison de chaque restaurant.
- Lien Commander définitif.
- Validation des pages légales :
  - hébergeur (Hostinger International Ltd, Chypre) ;
  - durées de conservation des candidatures : 3 ans pour la franchise, 2 ans pour le recrutement ;
  - responsable de la publication.
- Contenus marqués `[…]` et produits de démonstration à retirer.
