# Cookies (extension bigm-cookies)

Bandeau de consentement conforme au RGPD et aux lignes directrices de la CNIL. Code : `wp-content/plugins/bigm-cookies/` (README : fonctionnement, filtres, codes courts). Réglages → Cookies.

## Ce que le site dépose (inventaire du 9 novembre 2026)

| Finalité | Quoi | Avant l'accord |
|---|---|---|
| Nécessaires (sans accord) | Choix sur les cookies (`bigm_cookies`, 6 mois) ; restaurant choisi pour commander (`tfmsl_promo_store`, stockage du navigateur) ; connexion des administrateurs | — |
| Mesure d'audience | Google Analytics 4 (`G-4689YBK4SD`, posé par Site Kit) : cookies `_ga`, `_ga_*`, 13 mois au plus sans prolongation (`cookie_expires`, `cookie_update: false`, en tête de page et par le filtre `googlesitekit_gtag_opt` de Site Kit ; par défaut Google garde 2 ans et prolonge à chaque visite) | script en attente, rien n'est chargé ; mode de consentement Google « refusé » |
| Contenus d'autres sites | Vidéos et publications intégrées (aucune le 9 novembre ; bigm-core passe déjà YouTube en `youtube-nocookie.com`) | encart « Afficher le contenu » |

Sans cookie, donc sans accord : carte OpenStreetMap et Leaflet (unpkg) de la page Restaurants, photos des avis Google (googleusercontent), polices auto-hébergées, liens vers Uber Eats, Deliveroo et les réseaux sociaux. À revoir à chaque nouveau service : pixel publicitaire (Meta, TikTok, Snapchat), chat, carte Google Maps, reCAPTCHA…

## Règles CNIL suivies

- Aucun traceur soumis au consentement avant l'accord (vérifié dans le navigateur : aucune requête vers Google, aucun cookie `_ga`).
- « Tout refuser » au premier niveau, même aspect que « Tout accepter » ; pas de case cochée d'avance ; consentement par finalité ; pas de mur de cookies.
- Information : finalités, fournisseur (Google Ireland), transfert hors UE (Data Privacy Framework), durées ; lien vers la page de la politique.
- Choix gardé 182 jours (recommandation CNIL : 6 mois), puis redemandé ; redemandé aussi quand on coche « Redemander leur choix à tous les visiteurs » (nouvelle version).
- Retrait aussi simple que l'accord : lien « Paramétrer les cookies » en pied de page sur toutes les pages ; le retrait efface les cookies `_ga` et recharge la page.
- Preuve : le cookie `bigm_cookies` garde la version du texte et la date du choix ; le code de l'extension (Git) montre le mécanisme. Pas de journal des choix côté serveur (pas de donnée personnelle de plus).

## Page « Gestion des cookies » (`/cookies/`)

Réécrite le 9 novembre 2026 (ancienne version sauvegardée hors du dépôt) : votre choix et comment le changer (bouton `[bigm_cookies_gestion]`), ce que le site utilise, la liste des cookies (`[bigm_cookies_liste]`, à jour toute seule), les réglages du navigateur. **La politique de confidentialité** (`/politique-de-confidentialite/`) a été corrigée le même jour : Google (Google Ireland Ltd, transfert possible aux États-Unis) ajouté aux prestataires, seulement avec l'accord du visiteur ; paragraphe « Cookies » : mesure d'audience avec accord, cookies de 13 mois au plus, lien « Paramétrer les cookies ». À tenir à jour avec la page cookies quand un service est ajouté.

Elle doit décrire la même chose que le bandeau : Google Analytics, les contenus d'autres sites, le lien pour changer d'avis. Codes courts : `[bigm_cookies_liste]` (tableau des cookies), `[bigm_cookies_gestion]` (bouton « Gérer mes cookies »).

## Affichage du bandeau

Caché dans le HTML (`hidden`) : sans JavaScript, rien n'est accepté ni chargé. Un script en ligne de quelques lignes, imprimé juste après lui, lit le cookie `bigm_cookies` comme `cookies.js` et l'affiche aussitôt s'il n'y a pas de choix valable (1.0.3). Le bandeau apparaît avec le reste de la page au lieu d'attendre le script différé : à la première visite, son texte est souvent le plus grand élément de l'écran, celui que mesure le LCP.

## Cache des pages

Le bandeau est imprimé dans le HTML, identique pour tous les visiteurs : les pages restent en cache (LiteSpeed Cache), le choix est lu dans le navigateur. Depuis 1.0.2, enregistrer **Réglages → Cookies** vide tout le cache (`litespeed_purge_all`), pour qu'un nouveau texte ou « Redemander le choix » s'affiche tout de suite.

## Tester

- `wp eval-file tests/wp/test-bigm-cookies.php --user=<admin>` (bandeau, boutons de même aspect, scripts de Site Kit en attente, mode de consentement, contenus intégrés, lien du pied de page, réglages, codes courts).
- Navigateur : sur un site de test avec l'extension et un faux Google Analytics (script d'identifiant `google_gtagjs` qui pose `window.__gaCharge` et un cookie `_ga`, voir l'historique de la session du 9 novembre 2026), `SITE=http://… node tests/navigateur/test-cookies.js` : première visite (rien de chargé), refus, rechargement, lien du pied de page, accord (chargement dans l'ordre, consentement « accordé »), retrait (cookies effacés). Captures d’écran (ordinateur, panneau, mobile) dans `CAPTURES`, sinon le dossier temporaire du système.
- En production, après installation : outils de développement → Application → Cookies : aucun `_ga` avant l'accord ; onglet Réseau : aucune requête vers `googletagmanager.com` avant l'accord.
