# Jeux concours

Module de `bigm-core` (`inc/concours.php`, `assets/css/concours.css`, `assets/js/concours.js`). Un jeu se crée dans le menu **Jeux concours** de l'admin. Il s'affiche à deux endroits :

- sur la page **Jeu concours** (`/jeu-concours/`), avec le widget Elementor « Big M — Jeu concours » ou le code court `[bigm_jeu_concours]` ;
- sur la fiche de chaque **restaurant participant**, dans une bande sous le bandeau.

## Créer un jeu

Menu **Jeux concours → Ajouter**. Le titre du jeu sert de grand titre de la page.

| Champ | Rôle |
|---|---|
| Partenaire | Nom de la marque partenaire, affiché « Jeu concours avec … » |
| Accroche | Une ou deux phrases : ce qu'on peut gagner |
| Condition de participation | Obligation d'achat, âge, zone : sous les boutons et dans la bande des fiches |
| Début, Fin | Heure de Paris. Le compte à rebours s'arrête à la fin (59 s comprises) |
| Tirage au sort | Facultatif : annoncé quand le jeu est terminé |
| Lien pour jouer, Texte du bouton | Site du jeu, ouvert dans un nouvel onglet. Sans lien : pas de bouton |
| Couleurs du bandeau | Dégradé du haut vers le bas, sous du texte blanc : couleurs foncées |
| Comment jouer | Une étape par ligne : `Titre : texte` |
| À gagner | Un lot par ligne : `Quantité \| Lot \| Précision`. Une étoile devant (`*1 \| Casque Apple \| Tirage au sort`) met le lot en avant (« Lot principal ») |
| Questions fréquentes | Une question par bloc : la question sur la première ligne, la réponse dessous, une ligne vide entre deux questions |
| Mentions légales | Organisateur, dates, conditions, marques citées. En bas de la page, avec le règlement |
| Affiche story | Image 9:16. Remplace la vidéo s'il n'y en a pas, et sert d'aperçu |
| Vidéo MP4, WebM, aperçu | 9:16, 3 Mo au plus chacune (brief § 11). Sans son au départ, en boucle, seulement quand elle est visible |
| Règlement (PDF) | Bouton « Télécharger le règlement » |
| Restaurants participants | « Tous les restaurants », ou une liste à cocher (filtre, tout cocher, tout décocher) |

## Ce qui s'affiche

**Page Jeu concours.** Le widget montre le jeu en cours. Sinon, il montre le prochain jeu s'il commence dans moins de 30 jours, ou le dernier s'il est terminé depuis moins de 30 jours. Sans jeu, il affiche le titre et le texte d'attente du widget. On peut aussi choisir un jeu précis dans les réglages du widget.

La section du jeu comprend, dans l'ordre :

1. un bandeau en dégradé avec :
   - la pastille d'état (« En cours », « Bientôt », « Terminé ») ;
   - le titre et l'accroche ;
   - le compte à rebours ;
   - les boutons « Je joue » et « N restaurants participants » ;
   - la condition de participation ;
   - la vidéo dans un téléphone, avec des boutons pause et son ;
2. « Comment jouer », en cartes numérotées ;
3. « À gagner », avec des nombres qui défilent à l'arrivée et le lot principal mis en avant ;
4. les restaurants participants, avec une recherche sans accents ni tirets et un lien vers chaque fiche ;
5. les questions, en sections dépliables ;
6. le règlement en PDF et les mentions.

**Fiche d'un restaurant participant.** Une bande s'affiche sous le bandeau, après les promos de StoreLocatore et avant le food court (`tfmsl_after_store_hero`, priorité 15). Elle contient :
- l'affiche ou la vidéo ;
- « Jeu concours dans ce restaurant », le titre, l'accroche et la condition ;
- le compte à rebours en jours et heures ;
- les boutons « Je joue » et « Lots et règlement ».

Sur mobile, la bande montre l'affiche seule, sans vidéo, pour économiser les données. Dans un modèle Elementor de fiche, on utilise le code court `[bigm_jeu_concours_bande]`. Le CSS et le JS ne sont chargés que sur ces fiches et sur la page du jeu.

**Bannière.** Widget « Big M — Bannière jeu concours » ou code court `[bigm_jeu_concours_banniere]`. C'est la même bande que sur les fiches, à la largeur du site, avec « Jeu concours avec … » en surtitre. Elle n'affiche rien hors des dates du jeu : le widget peut rester en place d'un jeu à l'autre (dans l'éditeur, un cadre en pointillés le signale). Elle n'est pas posée sur l'accueil : Big M a préféré le garder tel quel (10 novembre 2026). Pour l'ajouter, il suffit de glisser le widget sous « Nos Box » dans Elementor.

**Bouton flottant.** Une pastille « Jeu concours en cours · Plus que N jours pour jouer » mène à la page du jeu. Elle se place en bas à gauche (à droite, il y a le bouton des promos de StoreLocatore). Sur mobile, elle se place au-dessus de la barre d'onglets.
- **Où :** sur toutes les pages, sauf celle du jeu et les fiches des restaurants participants, qui ont déjà la bande.
- **Quand :** imprimée cachée en pied de page (`wp_footer`), puis montrée par le JS pendant le jeu, une fois le bandeau des cookies fermé.
- **Bannière à l'écran :** la pastille s'efface et revient après.
- **Fermeture :** la croix la ferme pour de bon. Le choix est gardé dans le navigateur (`bigm_jeu_ferme_{id}`, listé dans les stockages nécessaires du bandeau des cookies), donc un nouveau jeu la remontre.
- **Fichiers :** `assets/css/concours-bouton.css` et `assets/js/concours-bouton.js`, chargés seulement quand elle s'affiche. Filtre `bigm_concours_bouton_flottant` pour la retirer d'une page.

## Dates et cache

Les pages sont en cache (LiteSpeed). Les dates de début et de fin sont donc écrites dans le HTML (`data-debut`, `data-fin`), et le JS décide de ce qui s'affiche :
- le compte à rebours, à la seconde ;
- « Commence dans » avant le début ;
- après la fin : « Jeu terminé », sans bouton, avec l'annonce du tirage ;
- la bande des fiches, cachée hors des dates.

Côté serveur, le cache est vidé à l'enregistrement d'un jeu, puis une minute après son début et sa fin (tâches `bigm_concours_changement`, lancées par la tâche cron Hostinger toutes les 5 minutes). Après la fin, la bande disparaît du HTML des fiches dans la journée.

## Référencement

Tant qu'un jeu est en cours ou à venir, la page qui l'affiche (widget ou code court) prend sa description dans le jeu : titre, « jusqu'au » date de fin, puis l'accroche (155 caractères au plus). Elle remplace celle saisie dans le bloc Référencement de la page, qui sert pour la page sans jeu. Sans image de partage choisie pour la page, l'affiche story sert d'image de partage. Filtre `bigm_seo_donnees_page` de `inc/seo.php`.

## Accessibilité et mouvement

- Un seul `h1` (le titre du jeu) et des sections en `h2`.
- Les boutons qui ouvrent le site du jeu annoncent « nouvel onglet ».
- La vidéo a un bouton pause : elle boucle plus de 5 secondes.
- Le compte à rebours n'est pas annoncé à chaque seconde. Une phrase pour les lecteurs d'écran (« Fin du jeu dans 13 jours, 1 heure et 16 minutes ») est mise à jour à la minute.
- Avec `prefers-reduced-motion`, rien ne bouge : pas de lecture automatique, de pulsation, de soleil, de téléphone qui flotte, de chiffres qui défilent ni de survol qui soulève. Avec `prefers-reduced-transparency`, les fonds sont opaques.
- axe (WCAG 2.1 AA) : aucune violation sur la page et sur la bande, ordinateur et mobile (9 novembre 2026).

## API REST

Type de contenu `bigm_concours` sous `/wp-json/wp/v2/bigm_concours`. Les champs sont des meta `_bigm_jc_…` :
- textes : `partenaire`, `accroche`, `condition`, `etapes`, `lots`, `faq`, `mentions`, `bouton` ;
- dates : `debut`, `fin` (`Y-m-d H:i`), `tirage` (`Y-m-d`) ;
- lien : `lien` ;
- médias (identifiants) : `story`, `video_mp4`, `video_webm`, `video_poster`, `reglement` ;
- couleurs : `couleur1`, `couleur2` ;
- restaurants : `tous` (`'1'` ou `'0'`), `restaurants` (liste d'identifiants `tfmsl_store`).

Pour lire ou modifier ces champs, il faut le droit `edit_post`.

## Jeu Big M × Oasis (septembre – novembre 2026)

Créé le 9 novembre 2026 à partir du règlement de l'organisateur, Orangina Schweppes France.

- **Dates :** du 15 septembre 2026 à 8 h au 22 novembre 2026 à 23 h 59. Tirage au sort le 23 novembre.
- **Participation :** avec obligation d'achat (un menu Big M et un Oasis Ice Tea citron-fraise 33 cl), sur le mini-site de NoPo (`https://www.nopo.fr/jeubigmoasis`).
- **Lots :**
  - 15 jeux GTA VI sur PS5 et 10 sur Xbox, en instants gagnants (70 € TTC) ;
  - 2 AirPods, en instants gagnants (149 € TTC) ;
  - 1 casque Apple au tirage au sort (579 € TTC).
- **Médias :**
  - vidéo story ramenée de 14,6 Mo à 1,5 Mo en MP4 et 2,1 Mo en WebM (720 × 1280, son compris) ;
  - aperçu tiré de la dernière image ;
  - affiche story et règlement en PDF.

**Restaurants.** L'annexe 1 du règlement compte 73 restaurants. 68 ont une fiche sur le site : « Aulnay-sous-Bois, Le Haut de Galy » correspond à la fiche « Centre Commercial O'Parinor ».
- **Sans fiche sur le site :** Grenoble 2 (6 rue Félix Poulat), Le Creusot, Sarreguemines, Sète et Villeurbanne – Croix-Luizet.
- **Fiches absentes de l'annexe :** Aéroville et Colmar – Big Canteen, cochés quand même à la demande de Big M (9 novembre 2026).
- **Dakar (3 restaurants), non cochés :** ils figurent dans l'annexe, mais le règlement (article 2) réserve le jeu aux résidents de France métropolitaine. Ils peuvent être ajoutés d'un clic dans la liste.

**Liste en ligne :** 67 restaurants.

**Écart à vérifier avec l'organisateur.** Le règlement annonce 15 jeux PS5 et 10 jeux Xbox (« jeux GTA »). L'affiche annonce « + 10 nouveaux jeux GTA VI à gagner ». La page reprend les chiffres du règlement.
