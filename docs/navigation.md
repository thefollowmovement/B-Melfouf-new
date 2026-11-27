# En-tête, tiroir, barre d'onglets mobile et pied de page

Ces quatre éléments viennent du thème enfant `bigm-child` (`inc/chrome.php`), avec le balisage et le CSS du prototype (`header.css`, `footer.css`, `tabbar.css`). Ils s'affichent sur toutes les pages sans réglage dans Elementor. Tout ce qui change d'un site à l'autre se modifie dans l'admin.

## Où modifier quoi

| Élément | Où |
|---|---|
| Liens de l'en-tête (Notre carte, Actualités…) | Apparence → Menus, emplacement « En-tête » |
| Liens du menu latéral | Apparence → Menus, « Menu latéral » |
| Les 3 liens de la barre d'onglets mobile | Apparence → Menus, « Barre d'onglets mobile » |
| Colonnes Produits, L'entreprise, liens légaux du pied de page | Apparence → Menus, « Pied de page : … » |
| Boutons Commander (en-tête ; sur mobile, icône panier de l'en-tête et bouton central) | Ouvrent la fenêtre Commander (`docs/restaurants.md`). Sans elle : Réglages Big M → Lien Commander par défaut ; vide, la bande appli de l'accueil |
| Lien « Restaurants » de l'en-tête | Réglages Big M → Page Restaurants. Vide : `/restaurants/`. Icône seule sur tablette, absent sur mobile (onglet « Restos ») |
| Réseaux sociaux, e-mail, siège | Réglages Big M. Un champ vide masque l'élément |
| Badges App Store et Google Play, avec « Big M dans votre poche » | Seulement si la case « Pied de page » de Réglages Big M → Appli Big M est cochée (décochée par défaut depuis le 9 novembre 2026). Les liens restent utilisés par le bloc « Bande appli » |
| « © 2026 Big M, c'est ça qu'on aime ! » | Réglages Big M → Mention du pied de page (l'année se met à jour seule) |
| Logo | Apparence → Personnaliser → Identité du site. Sinon, le logo du prototype |

Les menus sont créés au premier passage d'un administrateur, avec les liens du prototype, et placés dans leurs emplacements. Un emplacement laissé vide affiche ces mêmes liens.

Les liens sont des chemins (`/carte/`, `/nous-rejoindre/`). Ils suivent donc l'adresse du site : rien à réécrire quand la préprod devient le site en ligne.

La mention « Pour votre santé, mangez au moins cinq fruits et légumes par jour. www.mangerbouger.fr » est obligatoire. Elle est fixe dans le thème.

### Icônes de la barre d'onglets

Par défaut, les 3 liens prennent, dans l'ordre, les icônes maison, burger et épingle. Pour en changer, ajouter au lien du menu une classe CSS : `bigm-icone-accueil`, `bigm-icone-carte` ou `bigm-icone-restos`. La case « Classes CSS » s'active dans Options de l'écran, en haut de la page Menus.

Le bouton central Commander et « Plus », qui ouvre le menu latéral, sont fixes.

## Comportement

- **Lien courant :** le lien de la page affichée est souligné, et l'onglet est allumé. « Notre carte » et « La carte » restent allumés sur les catégories et les fiches produit.
- **Défilement :** l'en-tête s'affine quand on défile (`is-scrolled`).
- **Menu latéral :** Échap le ferme, et le focus revient au bouton qui l'a ouvert. Tab reste dans le menu tant qu'il est ouvert.
- **Préférences du visiteur :** le halo du bouton central s'arrête avec « réduire les animations ». L'en-tête et la barre deviennent opaques avec « réduire la transparence ».
- **Barre d'admin :** connecté, l'en-tête reste sous la barre d'admin de WordPress.

Pour masquer la barre d'onglets sur une page (une page de jeu concours en plein écran, par exemple), utiliser le filtre `bigm_child_onglets` :

```php
add_filter( 'bigm_child_onglets', fn() => ! is_page( 'jeu-concours' ) );
```

## Avec le Theme Builder d'Elementor Pro

Rien n'est à créer dans le Theme Builder : le thème affiche déjà l'en-tête et le pied de page. Pour les placer dans un modèle du Theme Builder (par exemple pour ajouter un bandeau au-dessus), deux widgets de la catégorie « Big M » donnent exactement le même rendu :

- **Big M — En-tête**, dans un modèle En-tête ;
- **Big M — Pied de page**, dans un modèle Pied de page.

Dès qu'un modèle du Theme Builder est actif, Hello Elementor n'affiche plus l'en-tête ou le pied de page du thème : pas de doublon. Le menu latéral et la barre d'onglets restent ajoutés par le thème dans tous les cas.

Mettre la marge intérieure du conteneur Elementor à 0. L'en-tête reste collé en haut grâce à une règle de `header.css` sur `.elementor-location-header`.

## Tester

```bash
wp eval-file tests/wp/test-bigm-child.php --user=IDENTIFIANT_ADMIN
```

Il fait 17 vérifications :

- menus et liens par défaut ;
- lien courant ;
- lien Commander ;
- tiroir et barre d'onglets ;
- mention mangerbouger.fr ;
- réseaux et stores masqués quand ils sont vides.
