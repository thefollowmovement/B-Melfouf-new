# Big M — refonte du site bigm-burger.fr

Brief de développement pour Claude Code. Le design est **validé par le client** : le dossier `prototype/` est la référence visuelle et fonctionnelle. Il faut le reproduire dans WordPress, à l'identique, sans réinventer le design.

## 1. Contexte

- **Client :** Big M, chaîne française de fast-food 100 % halal, née à Bondy en 2019.
- **Agence :** The Follow Movement / JustFollow. C'est l'agence qui mettra le site à jour après la livraison, avec Elementor.
- **Site actuel :** WordPress vieux de 6 ans, sur thème + Slider Revolution. On ne le met pas à jour, on repart d'une **installation neuve**.
- **Hébergement :** Hostinger, sur un compte distinct de celui qui a été infecté par un malware. On développe d'abord sur une **préprod en sous-domaine** (ex. `preprod.bigm-burger.fr`), avec `noindex`.
- **Référence d'usage :** burgerking.fr. Le slider s'inspire de McDonald's Japon : la slide active est au centre et les voisines sont visibles.

## 2. Stack

- WordPress (dernière version), PHP 8.2+.
- **Elementor Pro.** Thème : **Hello Elementor + un thème enfant** `bigm-child` qui contient les tokens, les polices, le JS et les widgets personnalisés.
- **Plugin maison `bigm-core`.** Il contient les types de contenu, les champs, les widgets Elementor et les shortcodes. Il est versionné sur Git.
- **Champs :** ACF Pro, ou à défaut des meta boxes natives. Choisir l'un des deux et s'y tenir.
- **Restaurants :** le plugin **storelocator** de l'agence (dépôt GitHub et Claude Code de l'utilisateur). On l'intègre, on ne le réécrit pas.
- **Plugins interdits :** Slider Revolution, page builders tiers, « nulled ». Garder le moins de plugins possible.

## 3. Charte (reprendre `prototype/assets/site.css`)

| Token | Valeur | Usage |
|---|---|---|
| `--bg` | `#faf8f5` | Fond blanc cassé |
| `--tile` | `#f2ede7` | Fond des vignettes |
| `--ink` | `#2b1406` | Texte, pied de page |
| `--muted` | `#6b4a3a` | Texte secondaire |
| `--orange` | `#fa5c19` | Grands aplats (bande appli, bouton central mobile) |
| `--btn` | `#c8400a` | Boutons, avec texte blanc (contraste AA) |
| `--yellow` | `#ffc21a` | Badges « Nouveau » |

- **Polices :** Fredoka 500/600 pour les titres, Figtree 400/600 pour le texte. Elles sont **auto-hébergées** en woff2 dans `prototype/assets/fonts/` : pas de Google Fonts en externe (RGPD).
- **Rayons :** 20 à 28 px pour les cartes, 12 px pour les boutons, 24 px pour la capsule du header.
- **Accessibilité :**
  - focus visible ;
  - zones tactiles de 44 px minimum ;
  - `prefers-reduced-motion` respecté (aucune lecture auto, ni animation, ni halo) ;
  - `prefers-reduced-transparency` respecté (header et barres opaques).

## 4. Pages et gabarits

| Prototype | WordPress | Notes |
|---|---|---|
| `index.html` | Page d'accueil (Elementor) | Sections dans l'ordre : slider à la une, bande appli, restaurants, services, classiques, « Big M les a réunis » (4 affiches 1967 / 1971 / 1993 / 1957), actualités, franchise + recrutement |
| `carte.html` | Archive du type de contenu Produit (`/carte/`) | Barre de catégories, bannières dynamiques, grille de catégories, bloc allergènes |
| `categorie.html` | Archive de la taxonomie (`/carte/burgers/`) | Bannières de la catégorie, filtres (viande + 14 allergènes), produits regroupés par sous-catégorie |
| `produit.html` | Single Produit (`/produit/smash-naan/`) | Bandeau, nom, description, ingrédients, allergènes (« contient » et « traces »), valeurs nutritionnelles, « Ça va bien avec » |
| — | Restaurants (`/restaurants/`) | Plugin storelocator, habillé aux couleurs de la charte |
| — | Franchise (`/devenir-franchise/`) | Reprendre le contenu et le formulaire de la page actuelle |
| — | Recrutement (`/recrutement/`) | Formulaire avec envoi de CV |
| — | Actualités (`/actualites/`) | Fusion des anciennes rubriques News et Presse, par catégorie |
| — | Jeu concours, mentions légales, confidentialité, cookies | |

**Header (Theme Builder) :**
- une capsule flottante arrondie, sticky, avec un fond translucide flouté, qui s'affine quand on défile ;
- menu à gauche, logo au centre, « Restaurants » et « Commander » à droite ;
- un menu latéral (tiroir) pour les autres liens.

**Barre d'onglets mobile (≤ 760 px) :**
- Accueil, La carte, **bouton central Commander** (logo dans une pastille surélevée), Restos, Plus (qui ouvre le tiroir) ;
- elle est cachée sur ordinateur.

**Footer :** brun, avec réseaux sociaux, badges des stores, liens et la mention « mangerbouger.fr » **obligatoire**.

## 5. Contenus gérés en admin (le cœur du projet)

Le client doit pouvoir **tout changer sans toucher au code**.

### 5.1 Type de contenu `bigm_produit`

- **Titre, description, image principale.**
- **Vidéo de vignette (optionnelle) :**
  - fichiers MP4 + WebM et une image d'aperçu ;
  - s'il y a une vidéo, elle remplace la photo dans la vignette ;
  - elle se lance seule, en boucle et sans son, quand la vignette est visible, et se met en pause hors écran.
- **Taxonomie `bigm_categorie`** (hiérarchique) : Nouveautés, Menus, Burgers (sous-catégories Bœuf / Poulet), Poulet, Tex-Mex, Petite faim, Desserts, Boissons. Chaque catégorie a :
  - une icône (SVG) ;
  - une image et une vidéo de vignette ;
  - **ses bannières** (voir 5.2).
- **Badge** optionnel (Nouveau, Édition limitée…).
- **Ingrédients :** une liste ordonnée.
- **Allergènes :** cases à cocher sur les **14 allergènes réglementaires**, une colonne « Contient » et une colonne « Traces ». Ces données alimentent le filtre de la catégorie.
- **Valeurs nutritionnelles :** énergie, matières grasses, dont saturées, glucides, dont sucres, protéines, sel, pour une portion et pour 100 g.
- **Produits associés** pour « Ça va bien avec ».
- **Lien de commande :** un lien global défini dans les réglages (Uber Eats, Deliveroo ou l'appli), avec la possibilité de le surcharger par produit.
- **Pas de prix**, c'est une demande du client.

### 5.2 Sliders et bannières (`bigm_slider`)

Un slider est une liste de slides. **Chaque slide contient :**
- un type : image ou vidéo ;
- une image ordinateur et une image mobile, ou bien une vidéo MP4 + WebM avec son image d'aperçu ;
- un texte alternatif ;
- un lien ;
- des dates de début et de fin (facultatives) pour programmer les offres.

**Où ils s'affichent :**
- le slider « À la une » de l'accueil ;
- le slider de bannières de `/carte/` ;
- un slider de bannières par catégorie.

**Comportement à reprendre de `prototype/assets/site.js` (bloc « Slider à la une »), sans le changer :**
- slide centrée avec les voisines visibles, défilement en boucle ;
- glisser au doigt ou à la souris avec l'élan du geste ;
- animation en ressort, sans rebond ;
- une photo reste 6 s (5 s pour les bannières) ; une vidéo passe à la slide suivante quand elle se termine ;
- pause au survol, quand le slider sort de l'écran et via le bouton pause ;
- navigation au clavier, par les points ou par les flèches.

**Widget :** un widget Elementor « Big M Slider » avec deux formats, `hero` (2:1) et `bannière` (3:1, et 2:1 sur mobile).

### 5.3 Autres contenus

- **Actualités :** articles natifs, catégories News et Presse.
- **Réglages Big M** (page d'options) :
  - lien Commander ;
  - liens App Store et Google Play ;
  - réseaux sociaux ;
  - les chiffres de la franchise : contrat de 5 ans, 30 000 € HT d'entrée, 5 000 € HT de formation, 5 % de redevance d'enseigne, 2 % de redevance communication.

## 6. Widgets Elementor à créer (`bigm-core`)

1. **Big M Slider :** hero et bannière, photo ou vidéo (voir 5.2).
2. **Grille de catégories :** les tuiles de `carte.html`, avec la vidéo de fond optionnelle.
3. **Grille de produits :**
   - filtres par viande et par allergènes ;
   - regroupement par sous-catégorie ;
   - un message quand aucun produit ne correspond ;
   - le filtrage se fait côté client, comme dans le prototype.
4. **Carrousel « Nos classiques ».**
5. **Bloc fiche produit :** ingrédients, allergènes et valeurs nutritionnelles, en sections dépliables.
6. **Barre de catégories sticky** (en capsule).
7. **Barre d'onglets mobile :** elle peut aussi être injectée par le thème enfant, avec ses liens réglables.

Chaque widget s'appuie sur les classes CSS du prototype. Le CSS et le JS sont mis dans la file d'attente **seulement sur les pages qui en ont besoin**.

## 7. Animations (à conserver)

- **Boutons :**
  - au survol, ils se soulèvent avec un reflet lumineux qui les traverse, et l'icône penche ;
  - au clic, ils s'enfoncent et une onde part de l'endroit touché ;
  - c'est le bloc « Boutons » de `site.js` / `site.css`.
- **Cartes et vignettes :** elles se soulèvent au survol et s'enfoncent au toucher.
- **Apparitions :** à l'arrivée au défilement (`data-reveal`), en décalé.
- **Header :** il s'affine quand on défile.
- **Bouton central mobile :** un halo qui pulse.

Tout cela est désactivé quand `prefers-reduced-motion` est actif.

## 8. Restaurants (storelocator)

- Installer le plugin depuis le dépôt de l'agence.
- Importer les restaurants : adresse, horaires, services, liens de livraison. **Les données sont à fournir par le client.**
- Habiller le plugin aux couleurs de la charte : cartes blanches arrondies, bouton `--btn`, épingles orange.
- Brancher dessus le bloc « Tous nos restaurants » de l'accueil : la recherche doit renvoyer vers `/restaurants/?q=…`.

## 9. Formulaires (Elementor Pro Forms)

- **Franchise :**
  - nom, prénom, e-mail, téléphone ;
  - zone géographique, département ou ville, adresse si connue ;
  - situation actuelle, métier et expériences ;
  - apport hors emprunt (130–150 k€ / 150–200 k€ / plus de 200 k€) ;
  - délai du projet (3 / 6 / 12 mois et plus) ;
  - questions.
- **Recrutement :**
  - prénom, nom ;
  - ville(s) où la personne peut travailler ;
  - adresse, téléphone ;
  - CV et lettre de motivation (jusqu'à 3 fichiers, PDF ou DOCX, 5 Mo maximum) ;
  - message.
- **Pour les deux :** anti-spam (honeypot + Cloudflare Turnstile ou reCAPTCHA v3), notification par e-mail, case de consentement RGPD.

## 10. Migration et SEO

- **Redirections 301 depuis l'ancien site :**
  - `/nos-restaurants/` → `/restaurants/`
  - `/contact/` → `/recrutement/`
  - `/news/` et `/presse/` → `/actualites/`
  - `/devenir-franchise/` → `/devenir-franchise/`
  - `/concours/` → `/jeu-concours/`
  - `/app/` → le lien Commander
- **Articles :** reprendre les articles existants (News / Presse) avec leurs images.
- **Référencement technique :**
  - un titre et une meta description par page ;
  - un plugin SEO léger, ou les réglages natifs + un sitemap ;
  - Open Graph ;
  - données structurées `Restaurant` / `LocalBusiness` sur les restaurants ;
  - `FAQPage` si on ajoute une FAQ plus tard.
- **Viewport :** autoriser le zoom. L'ancien site bloquait `maximum-scale=1`, c'est à corriger.

## 11. Performance

- **Images :** WebP/AVIF, en `srcset` ; en différé (`loading="lazy"`), **sauf** pour la première slide.
- **Vidéos :**
  - MP4 (H.264) + WebM (VP9), de 3 Mo maximum chacune ;
  - toujours avec une image d'aperçu ;
  - chargées seulement quand elles deviennent visibles (`preload="none"` + chargement par le JS) ;
  - le JS choisit le format lisible (`window.bigmVideoSrc`).
- **Polices :** préchargement de Fredoka 600.
- **Cibles :** LCP < 2,5 s sur mobile, CLS < 0,1. Vérifier avec Lighthouse.
- **Cache :** LiteSpeed Cache, puisque l'hébergement Hostinger est sous LiteSpeed.

## 12. Sécurité

- Installation neuve, comptes administrateurs nominatifs, 2FA.
- Mises à jour automatiques des correctifs mineurs.
- XML-RPC désactivé, édition de fichiers désactivée dans l'admin.
- Sauvegardes quotidiennes externes.
- Clés et mots de passe **jamais** dans le code ni dans Git.

## 13. Contenus à demander au client

- [ ] Photos produits en HD (PNG transparents si possible) et vidéos de produits.
- [ ] Bannières de campagne en grand format (ordinateur 2400×1200, mobile 1200×900) et vidéos.
- [ ] Logo en vectoriel (SVG).
- [ ] Liste complète des produits : recettes de M1 à M16, W1 à W3, desserts, menus kids.
- [ ] Allergènes et valeurs nutritionnelles de chaque produit (fiches fournisseurs).
- [ ] Liste des restaurants : adresse, horaires, services, liens de livraison.
- [ ] Lien Commander définitif (Uber Eats, Deliveroo ou l'appli).
- [ ] Textes des mentions légales et de la politique de confidentialité à jour.

**À retirer avant la mise en ligne :** les contenus marqués `[…]` dans le prototype et les vidéos de démonstration, qui ne sont que des zooms générés à partir des photos.

## 14. Ordre de travail conseillé

1. Préprod Hostinger, WordPress neuf, thème enfant, tokens et polices.
2. Plugin `bigm-core` : types de contenu, taxonomies, champs, page de réglages.
3. Header capsule, tiroir, barre d'onglets mobile, footer.
4. Widget Big M Slider, puis l'accueil complet.
5. Carte, catégorie et fiche produit, avec les filtres allergènes.
6. Storelocator, formulaires, actualités.
7. Redirections, SEO, performance, accessibilité.
8. Recette (ordinateur, mobile, Safari iOS, Chrome Android), puis bascule.

## 15. Fichiers du prototype

- `prototype/index.html`, `carte.html`, `categorie.html`, `produit.html` : les pages de référence.
- `prototype/assets/site.css` : tokens, composants, header, slider, barre d'onglets, animations.
- `prototype/assets/site.js` : tiroir, slider, vidéos de vignettes, boutons, apparitions.
- `prototype/assets/img/`, `video/`, `fonts/` : les visuels provisoires et les polices.
- `prototype/build.py` et `pages.py` : le générateur des pages statiques. C'est la source du balisage, utile pour voir comment chaque composant est construit.

Pour ouvrir le prototype en local : `cd prototype && python3 -m http.server 8000`, puis http://localhost:8000.

## 16. Dépôt et conventions (tenu à jour pendant le développement)

- **Arborescence :** `wp-content/` reproduit celle du serveur : thème `themes/bigm-child/`, mu-plugin `mu-plugins/bigm-socle.php` (aussi livré en extension zip), `plugins/bigm-core/`, `plugins/bigm-box/` (widget « Nos Box ») et `plugins/bigm-demo/` (temporaire). `ops/` déploie par SSH, `docs/` décrit les procédures, `tests/lint.sh` vérifie le code avant chaque déploiement. `elementor/modeles/` : modèles Elementor et formulaires WPForms à importer (franchise, recrutement, blog), avec leurs générateurs. `personnalisations/` : réglages faits dans l'admin, gardés pour mémoire.
- **CSS :** chaque composant reprend ses règles de `prototype/assets/site.css` (et du `<style>` de sa page), dans le même ordre, dans son propre fichier, mis en file d'attente seulement là où il sert. L'en-tête de `assets/css/base.css` liste ce qui reste à reprendre.
- **Tokens renommés :** `--display` devient `--font-display` et `--text` devient `--font-text`, car Elementor écrit `--display: flex` sur chacun de ses conteneurs. En reprenant du CSS du prototype, remplacer ces deux noms ; `tests/check-tokens.php` le vérifie.
- **Couleurs :** déclarées dans `assets/css/tokens.css` et dans `inc/tokens.php` (pour le kit Elementor). Après un changement : `wp bigm kit --user=<admin>`.
- **Reset :** le `reset.css` de Hello Elementor est désactivé ; c'est celui du prototype qui s'applique (`base.css`). `theme.css` de Hello reste chargé.
- **Elementor :** Fredoka et Figtree sont servies en local (groupe « Big M (auto-hébergées) ») et Google Fonts est forcé à « désactivé ». Points de rupture : mobile ≤ 760 px, tablette ≤ 960 px. Largeur du contenu : 1240 px.
- **Production depuis le 9 novembre 2026 :** `bigm-burger.fr` (compte Hostinger `u296589459`, base `u296589459_PMHeU`), installée par Duplicator Pro depuis la préprod (`docs/bascule.md`). La préprod `preprod.bigm-burger.fr` n'existe plus (fichiers supprimés avec l'ancien site, base `u296589459_IHZJK` gardée) : les zips se téléversent directement en production, après une sauvegarde hPanel. Les slides (liens en adresse complète, sérialisés) ont été abîmés par le changement d'adresse : `inc/reparation.php` (`bigm_reparer_serialisations()`) recalcule les longueurs des valeurs sérialisées illisibles, une fois au premier passage d'un administrateur, ou par `POST bigm/v1/maintenance/serialisations`. Ancienne procédure de préprod : `docs/preprod-hostinger.md`.
- **Socle :** `bigm-socle.php` marche en mu-plugin (SSH) ou en extension (zip) et ne se charge qu'une fois. Sans `WP_ENVIRONMENT_TYPE`, il reconnaît la préprod à son adresse (`preprod.`, `staging.`, `.hostingersite.com`). Il applique les réglages de départ au premier passage d'un administrateur. Depuis 0.3 : en-têtes de sécurité (`wp_headers`, filtre `bigm_socle_headers`), liste des comptes REST réservée aux connectés, pas de pages auteur, oEmbed sans auteur.
- **Déploiement :** sans SSH, `ops/build-zips.sh` (ou l'artefact `bigm-zips` de GitHub Actions) puis téléversement dans l'admin ; avec SSH, `ops/deploy.sh`.
- **bigm-core :** champs en meta boxes natives (pas d'ACF). Clés `_bigm_…`, identifiants d'allergènes et de viandes du prototype. Les widgets lisent les données par les fonctions de `inc/api.php` (`bigm_produit()`, `bigm_categories()`, `bigm_slides_actives()`, `bigm_reglage()`…). Référence : `docs/bigm-core.md`. Test : `wp eval-file tests/wp/test-bigm-core.php --user=<admin>`.
- **En-tête, tiroir, barre d'onglets, pied de page :** rendus par le thème (`inc/chrome.php`), à la place de ceux de Hello (`template-parts/`). Le tiroir et la barre d'onglets sont ajoutés par `wp_footer`. Liens dans 6 emplacements de menus (`inc/navigation.php`), avec les liens du prototype par défaut. Commander, Restaurants, réseaux, stores, contact et mention viennent de Réglages Big M ; badges des stores dans le pied de page seulement si la case `pied_stores` est cochée (décochée par défaut). Widgets « Big M — En-tête » et « Big M — Pied de page » pour le Theme Builder. Référence : `docs/navigation.md`. Test : `tests/wp/test-bigm-child.php`.
- **Widgets Elementor :** dans `bigm-core` (`inc/elementor/`), catégorie « Big M », base commune `Bigm_Widget`. Styles et scripts déclarés dans `inc/elementor.php`, chargés seulement par les widgets qui en dépendent (`get_style_depends` / `get_script_depends`). Les widgets rendent des sections complètes : conteneur Elementor pleine largeur, marge et espacement à 0. Règles de compatibilité `.elementor …` juste après la règle d'origine (Elementor écrase `box-shadow` des liens, `height`/`border-radius` des images). Ne pas passer `loading` aux images : WordPress et Elementor le posent. Référence : `docs/accueil.md`. Test : `tests/wp/test-bigm-widgets.php`.
- **Carte, catégories, fiches produit :** balisage dans `bigm-core/inc/rendu.php`, partagé par les widgets et par les gabarits du thème (`archive-bigm_produit.php`, `taxonomy-bigm_categorie.php`, `single-bigm_produit.php`), qui appellent `bigm_page_carte()`, `bigm_page_categorie()` et `bigm_page_produit()` (`inc/pages.php`) sauf si un modèle du Theme Builder s'applique. Ces pages chargent leurs styles à la priorité 25, après ceux du thème. Textes et bannières de `/carte/` dans Réglages Big M (section « La carte »). Catégories vides masquées, sections vides de la fiche masquées. Nouveautés (badge « Nouveau » ou catégorie Nouveautés, `bigm_produit_est_nouveau()`) en tête de chaque catégorie, dans un groupe « Nouveautés » s'il y a des sous-catégories, et vignettes qui se secouent trois fois en apparaissant (`is-new`, moins de 5 s) : deux cases dans Réglages Big M → La carte. Référence : `docs/carte.md`. Test : `tests/wp/test-bigm-carte.php`.
- **Restaurants :** plugin StoreLocatore de l'agence (`thefollowmovement/storelocatore-wordpress`, 2.5 ou plus), jamais modifié ici. Il charge seul ses fichiers là où ils servent, lit `?q=` et s'habille aux couleurs du kit Elementor. `bigm-core/inc/restaurants.php` ne garde que : fiches sous `/restaurants/…` sans archive du plugin (`register_post_type_args`), libellés et textes « restaurant », défauts `tfmsl_store_style` = `site` et `tfmsl_mobile_bar` = `0`, type de cuisine « Burgers halal » par défaut (schéma `Restaurant`), `tfmsl_gmb_place_id`, `tfmsl_lat`, `tfmsl_lng`, `tfmsl_services`, `tfmsl_payment_methods`, `tfmsl_order_url` dans l'API REST (`register_post_meta`, saisie en lot) et recherche des Place ID possibles (`GET bigm/v1/restaurants/{id}/place-id`, clé Google du plugin, côté serveur). `/restaurants/` est la page Elementor d'adresse `restaurants` avec le widget Store – Locator. Zip : `ops/build-zips.sh` avec le dépôt cloné à côté. Référence : `docs/restaurants.md`.
- **Horaires Google :** `bigm-core/inc/horaires.php` corrige l'import de StoreLocatore 2.5 (une seule plage par jour) : après chaque import (`added_post_meta` / `updated_post_meta` sur `tfmsl_gmb_last_sync`), relit les plages de l'option `tfmsl_gmb_debug_{id}` et réécrit `tfmsl_hours` avec la pause (`bigm_horaires_depuis_google()`). S'efface avec StoreLocatore 2.5.1 ou plus (`periods_to_hours`, filtre `bigm_horaires_correction`). `POST bigm/v1/restaurants/{id}/horaires-google` relance l'import ; `tfmsl_gmb_sync` dans l'API REST.
- **Appels à Google :** `bigm-core/inc/google.php` : planification chaque nuit (`bigm_google_planning`, 4 h) des horaires (tous les `google_horaires_jours`, 3) et des avis (tous les `google_avis_jours`, 7) par groupes ; un appel `get_place_summary()` remplit `tfmsl_place_summary_{id}` et `tfmsl_reviews_{id}` (au format de `get_reviews()`) pour la fréquence + 8 jours (fiche : 5 étoiles seulement, `bigm_google_avis_note_min` ; mémoire lue vide pendant `wp_head` des fiches, donc pas d'`aggregateRating` calculé par le plugin) ; import quotidien de StoreLocatore débranché (`remove_action` sur `tfmsl_sync_google_hours`). Disjoncteur `pre_http_request` (priorité 5) sur places.googleapis.com : jamais depuis une page vue par un visiteur (`bigm_google_contexte_autorise()`), plafond mensuel par catégorie (`google_plafond`, 900 ; `details`, `avis`, `recherche`), compteur `bigm_google_appels`. Un faux Google de test doit laisser passer un refus (`false !== $pre`). `POST bigm/v1/google/planification`.
- **Food courts (Big Canteen) :** `bigm-core/inc/food-court.php` : taxonomie `bigm_food_court` sur `tfmsl_store` (non publique, dans l'API REST), meta du food court `_bigm_fc_logo`, `_bigm_fc_marques`, `_bigm_fc_site`, marques propres au restaurant `_bigm_fc_marques` ; bloc sous le bandeau de la fiche par `tfmsl_after_store_hero` (priorité 20, après les promos), `[bigm_food_court]`, `assets/css/food-court.css` seulement sur ces fiches. Référence : `docs/restaurants.md`.
- **Fenêtre Commander :** `bigm-core/inc/commander.php` + `assets/js/commander.js` (sur toutes les pages, différé ; `commander.css` chargé à l'ouverture). Le JS intercepte les liens vers le lien Commander par défaut, `#commander`, `[data-bigm-commander]` et les boutons `click_order` de StoreLocatore. Restaurant choisi en `localStorage` sous la clé des promos de StoreLocatore (`tfmsl_promo_store`, même format `{id, name, short, order, url, dist}`), jamais côté serveur (pages en cache). Plateformes dans `bigm_reglages['commande_plateformes']` (identifiant fixe), liens des restaurants en `_bigm_commande_liens` (plateforme => lien), `_bigm_commande_tel`, `_bigm_commande_numero` ; sans lien saisi, `tfmsl_order_url` est rangé sous sa plateforme par domaine. Données `GET /wp-json/bigm/v1/commander` (transient vidé à l'enregistrement d'un restaurant ou des réglages). Liens nettoyés (`bigm_lien_commande_propre()` : `srsltid`, `utm_…`, `/fr-en/` d'Uber Eats). Référence : `docs/restaurants.md`.
- **Formulaires :** sur la préprod, pages WPForms `/devenir-franchise/` et `/nous-rejoindre/` (modèles dans `elementor/modeles/`). Le recrutement est donc à `/nous-rejoindre/` : liens par défaut du thème et du widget « Nous rejoindre » vers cette adresse, `/recrutement/` et `/contact/` redirigés. Variante Elementor Pro dans la démo, créée seulement par le bouton « Créer les pages à formulaire » (`bigm_demo_formulaires()`) : styles dans les réglages du widget (les défauts globaux d'Elementor l'emportent sinon), complément `assets/css/formulaires.css` (carte = conteneur « en boîte » à classe `bigm-formulaire`) chargé sur les pages qui contiennent `"widgetType":"form"`. Ne garder qu'une des deux extensions.
- **Actualités :** gabarits `home.php`, `category.php`, `single-post.php` du thème → `bigm_page_actualites()`, `bigm_page_article()` (sauf modèle du Theme Builder, via `bigm_child_emplacement()`, qui ajoute `<main>`). Cartes d'articles partagées avec l'accueil (`bigm_carte_article()`, `assets/css/articles.css`). Catégories sous `/actualites/` (base réglée par `inc/activation.php`). Référence : `docs/pages.md`. Test (étape 6) : `tests/wp/test-bigm-contenus.php`.
- **Vidéos :** MP4 et WebM sont deux médias ; le balisage porte `data-src` (MP4) et `data-src-webm`, et `window.bigmVideoSrc(src, webm)` choisit.
- **Démo :** `bigm-demo` (temporaire) importe le contenu du prototype et crée l'accueil, puis la page Restaurants dès que StoreLocatore est actif, et les pages légales et Jeu concours (`bigm_demo_pages_legales()`, textes de l'ancien site mis à jour), une fois chacune, sur `admin_init`. Les pages à formulaire se créent par un bouton. Ses contenus portent `_bigm_demo` et se suppriment d'un clic ; les pages et les médias encore utilisés ailleurs (`bigm_demo_media_utilise()`) sont gardés. À retirer avant la mise en ligne.
- **Étape 7 (`bigm-core`) :** `inc/redirections.php` (règles dans Réglages Big M, appliquées seulement sur une 404, journal `bigm_404`), `inc/seo.php` (meta, Open Graph, JSON-LD, plans du site ; s'efface si une extension SEO est active ; rien sur les fiches `tfmsl_store`, laissées au plugin), `inc/performance.php` (tailles en WebP, emojis et CSS des blocs retirés, vidage de LiteSpeed Cache aux dates des slides), `inc/reprise.php` (Outils → Reprise des articles, API de l'ancien site, 5 par lot). Titres : sections de la fiche, colonnes du pied de page et cartes de la page Actualités en `h2`. `inc/llms.php` : fichier `llms.txt` à la racine (carte, restaurants, franchise, actualités), réécrit à chaque modification du contenu et chaque jour ; l'option LLMs.txt de l'extension Hostinger est à désactiver. Référence : `docs/seo.md`. Test : `tests/wp/test-bigm-seo.php`.
- **Cache :** LiteSpeed Cache (production, depuis le 9 novembre 2026), réglé par `bigm-core/inc/cache.php` : liste de réglages appliquée une fois au premier passage d'un administrateur (`bigm_litespeed_reglages()`, version `BIGM_LITESPEED_REGLAGES` dans l'option `bigm_litespeed`, `POST bigm/v1/litespeed`) : pas de cache des connectés ni de l'API REST, tout vidé à chaque contenu et à chaque mise à jour, aucune optimisation des CSS et JS, pas de QUIC.cloud. Pages à formulaire (`bigm_page_a_formulaire()`) hors cache ; fiches restaurants en cache jusqu'au prochain changement d'horaire ou minuit (`bigm_restaurant_duree_cache()`, minuit UTC compris, jour lu par StoreLocatore) et vidées quand `tfmsl_hours` change ; tout vidé aux réglages de StoreLocatore, du personnalisateur et des cookies. Référence : `docs/seo.md`. Test : `tests/wp/test-bigm-cache.php`.
- **Cookies :** extension `bigm-cookies` (Réglages → Cookies) : bandeau CNIL (« Tout refuser » au premier niveau, même aspect que « Tout accepter », finalités mesure d'audience et contenus d'autres sites), Google Analytics de Site Kit en attente (`type="text/plain" data-bigm-cookies`, `script_loader_tag` et `wp_inline_script_attributes`) jusqu'à l'accord, mode de consentement Google v2 refusé par défaut, contenus intégrés remplacés par un encart, choix dans le cookie `bigm_cookies` (182 jours, version), lien « Paramétrer les cookies » (`#gestion-cookies`) ajouté au pied de page par le filtre `bigm_child_liens` du thème (0.6.5). Référence : `docs/cookies.md`. Tests : `tests/wp/test-bigm-cookies.php`, `tests/navigateur/test-cookies.js`.
- **Nos Box :** extension `bigm-box` faite par une autre session (widget « Big M — Nos Box », à la place de la Bande appli sur l'accueil). Indépendante de bigm-core ; le visuel de la première Box est chargé sans différé (LCP mobile).
- **Adresses des catégories d'articles :** `bigm_core_verifier_regles()` (init, priorité 101) recalcule les règles si elles ne connaissent pas la base `/actualites/` : une mise à jour qui change la base recalcule les règles avant que la catégorie ne l'ait prise (filtres News / Presse en 404 sur la préprod). `bigm_core_regle_pagination()` place en tête des règles des catégories la pagination de la page des articles (`/actualites/page/2/`), sinon lue comme une catégorie.
- **Recette et bascule :** `docs/recette.md` (résultats, corrections à faire dans l'admin, essais sur téléphone), `docs/bascule.md` (procédure SSH avec WP-CLI, retour arrière).
- **Viandes et import de la carte :** `_bigm_viande` est une liste (plusieurs viandes, `bigm_produit_viandes()` lit aussi l'ancien texte) ; `data-meat` des vignettes en porte plusieurs, séparées par des espaces. Produits → Importer la carte (`inc/import.php`) lit `bigm-core/data/carte.json`, fabriqué par `ops/carte/carte.py` (tableau des allergènes du client + `ops/carte/uber-eats-carte.json`, cartes Uber Eats recopiées : menus, boissons, sauces, descriptions). `ops/carte/uber-eats/{restaurant}.json` (extraits des pages Uber Eats enregistrées) : photos (Argenteuil d'abord, puis Bondy) et descriptions (Bondy). Photo importée = média avec `_bigm_source_image` : remplacée par un nouvel import, jamais une photo choisie dans l'admin. Allergènes d'un menu (ou d'une Box) = ceux de ses produits réunis. Catégorie Box (Big Box, M Box, Box Noxx, aussi en Nouveautés). Gamme Mamamia (3 burgers + 3 menus simples, allergènes à confirmer). `POST bigm/v1/carte/importer` (`{"produits": [adresses]}`) n'importe que ces produits et leurs catégories (`bigm_import_categories_de()`). Produits (et articles de la reprise) retrouvés par `bigm_post_par_adresse()`, jamais par `get_page_by_path()`, qui renvoie aussi un média de même adresse. Bacon de bœuf. Un nouvel import change viandes et allergènes ; nom, catégories et statut seulement si le nom n'a pas été retouché ; description et ingrédients s'ils sont vides ou encore ceux d'un import (`_bigm_import_description`, `_bigm_import_ingredients`, `anciennes_descriptions`). Référence : `docs/carte.md`.
- **Secrets :** accès SSH et licences jamais dans Git. `ops/.env.preprod` est ignoré ; Elementor Pro s'installe à la main.
