# Big M — Cookies

Bandeau de consentement aux cookies, conforme au RGPD, à la directive ePrivacy et aux lignes directrices de la CNIL (2020) :

- **Rien avant l'accord.** Google Analytics posé par Site Kit (`google_gtagjs`, ses scripts en ligne, les suivis d'événements `googlesitekit-events-provider-*`) est mis en attente (`type="text/plain"`) et ne se charge qu'après l'accord « Mesure d'audience ». Mode de consentement de Google v2 « refusé » par défaut. Vidéos et publications d'autres sites (YouTube, Vimeo, Instagram, TikTok, X, Facebook, Spotify, Google Maps…) remplacées par un encart « Afficher le contenu ».
- **Refuser aussi simple qu'accepter.** « Tout refuser » et « Tout accepter » au premier niveau, même aspect ; « Personnaliser » pour choisir finalité par finalité, aucune case cochée d'avance. Pas de mur de cookies : le site reste consultable.
- **Choix gardé 6 mois** (réglable, 13 mois au plus), redemandé si le texte change (« Redemander leur choix à tous les visiteurs »).
- **Changer d'avis à tout moment** : lien « Paramétrer les cookies » ajouté au pied de page (thème bigm-child 0.6.5 ou plus), tout lien vers `#gestion-cookies`, ou le code court `[bigm_cookies_gestion]`. Retirer son accord efface les cookies `_ga` et recharge la page.
- **Information** : finalités, fournisseurs, durées ; `[bigm_cookies_liste]` affiche le tableau des cookies sur la page de la politique.
- Accessible : premier élément de la page au clavier, dialogue nommé, interrupteurs à vraies cases à cocher, focus visible, sans animation si le visiteur l'a demandé. Pages en cache : tout se décide dans le navigateur.

Réglages → Cookies : titre, texte, page « En savoir plus », durée, finalités proposées.

Pour un nouveau service (pixel publicitaire…) : filtre `bigm_cookies_scripts` (identifiant du script => finalité), ou `<script type="text/plain" data-bigm-cookies="mesure">` dans un bloc HTML ; ajouter la finalité et ses cookies avec le filtre `bigm_cookies_categories`, puis « Redemander leur choix à tous les visiteurs ». JS : `window.bigmCookies.accepte('mesure')`, `window.bigmCookies.ouvrir()`.

Tests : `wp eval-file tests/wp/test-bigm-cookies.php --user=<admin>` ; parcours dans le navigateur : `docs/cookies.md`.
