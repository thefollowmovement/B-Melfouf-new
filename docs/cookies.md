# Cookies (extension bigm-cookies)

Bandeau de consentement conforme au RGPD et aux lignes directrices de la CNIL. Code : `wp-content/plugins/bigm-cookies/` (README : fonctionnement, filtres, codes courts). Réglages → Cookies.

## Ce que le site dépose (inventaire du 9 novembre 2026)

| Finalité | Quoi | Avant l'accord |
|---|---|---|
| Nécessaires (sans accord) | Choix sur les cookies (`bigm_cookies`, 6 mois) ; restaurant choisi pour commander (`tfmsl_promo_store`, stockage du navigateur) ; connexion des administrateurs | — |
| Mesure d'audience | Google Analytics 4 (`G-4689YBK4SD`, posé par Site Kit) : cookies `_ga`, `_ga_*`, 13 mois | script en attente, rien n'est chargé ; mode de consentement Google « refusé » |
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

Elle doit décrire la même chose que le bandeau : Google Analytics, les contenus d'autres sites, le lien pour changer d'avis. Codes courts : `[bigm_cookies_liste]` (tableau des cookies), `[bigm_cookies_gestion]` (bouton « Gérer mes cookies »).

## Tester

- `wp eval-file tests/wp/test-bigm-cookies.php --user=<admin>` (bandeau, boutons de même aspect, scripts de Site Kit en attente, mode de consentement, contenus intégrés, lien du pied de page, réglages, codes courts).
- Navigateur : sur un site de test avec l'extension et un faux Google Analytics (script d'identifiant `google_gtagjs` qui pose `window.__gaCharge` et un cookie `_ga`, voir l'historique de la session du 9 novembre 2026), `SITE=http://… node tests/navigateur/test-cookies.js` : première visite (rien de chargé), refus, rechargement, lien du pied de page, accord (chargement dans l'ordre, consentement « accordé »), retrait (cookies effacés).
- En production, après installation : outils de développement → Application → Cookies : aucun `_ga` avant l'accord ; onglet Réseau : aucune requête vers `googletagmanager.com` avant l'accord.
