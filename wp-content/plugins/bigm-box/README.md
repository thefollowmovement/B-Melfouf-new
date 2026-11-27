# Big M — Nos Box

Extension WordPress qui ajoute le widget Elementor **« Big M — Nos Box »** (catégorie Big M du panneau).

- **Ordinateur** : le visuel du contenu de la Box choisie à gauche ; à droite le titre, le texte et une carte par Box (photo et nom).
- **Mobile et tablette** (≤ 960 px) : titre, visuel, puis les cartes en bande qui défile au doigt.
- Cliquer (ou toucher) une carte affiche son contenu. Au clavier : flèches, Début, Fin (onglets accessibles).
- Les Box se gèrent dans le widget : **Box, dans l'ordre → Ajouter un élément**. Pas de limite de nombre.

Indépendante de l'extension « Big M — cœur » : elle reprend seulement ses classes et variables
(`.container`, `.section`, `.btn`, `--orange`, `--tile`, `--radius`…), présentes sur toutes les pages
par le thème `bigm-child`. Elle range son widget dans la catégorie Big M si elle existe, sinon la crée.

## Réglages du widget

| Réglage | Rôle |
| ------- | ---- |
| Titre, Texte sous le titre | En-tête du bloc |
| Box, dans l'ordre | Une ligne par Box : nom, **photo de la carte** (carrée), **visuel du contenu** (paysage 4:3), texte « ce qu'elle contient », badge (pastille jaune), lien du bouton |
| Texte du bouton | « Commander » par défaut ; vide = pas de bouton |
| Ancre | `box` : lien direct `/#box` |
| Apparence → Fond | Aplat orange (défaut), aplat crème, sans fond |
| Apparence → Visuel du contenu | Remplit le cadre (recadré) ou image entière |

Lien du bouton : celui de la Box, sinon le **Lien Commander** de *Réglages Big M*. Si les deux sont vides,
pas de bouton. Filtre `bigm_box_lien_commander` pour fournir un autre lien par défaut.

Sans visuel du contenu, la photo de la carte le remplace.

## Détails

- Le bloc garde la hauteur de la Box la plus haute : rien ne saute quand on change de Box.
- Les visuels des autres Box sont préchargés quand on approche du bloc : le changement est immédiat.
- Style et script (`assets/box.css`, `assets/box.js`, sans dépendance) ne sont chargés que sur les pages qui ont le widget.
- Le visuel de la première Box est chargé tout de suite (il est souvent le plus grand élément de l'écran sur mobile) ; les autres en différé.
- Le contour de la carte choisie est un pseudo-élément : le reset de Hello Elementor retire `box-shadow` et
  `outline` des boutons qui ont le focus.

## Installation

Extensions → Ajouter → Téléverser `bigm-box.zip` (fabriqué par `ops/build-zips.sh`, avec les autres zips) → Activer. Puis, sur l'accueil (Modifier avec Elementor) :
supprimer le widget « Big M — Bande appli » et glisser « Big M — Nos Box » à sa place.

Le lien Commander de secours du thème pointe vers `/#appli`, l'ancre de la Bande appli : une fois la bande
retirée, renseigner le **Lien Commander** dans *Réglages Big M*.
