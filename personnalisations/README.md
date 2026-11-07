# Personnalisations du site (preprod Big M)

Réglages faits dans l'admin WordPress, gardés ici pour ne pas les perdre.

## `css-additionnel.css` → Apparence → Personnaliser → CSS additionnel

- **Icône de localisation dans l'en-tête mobile** (≤ 960 px) : le thème `bigm-child` masque sur mobile le lien
  « Restaurants » de l'en-tête (classe `hide-sm`). Ce CSS l'affiche en icône seule (40 × 40 px), à droite,
  en face du bouton menu. Le lien va vers la page Restaurants des Réglages Big M (`/restaurants/` par défaut),
  passe en orange sur cette page, et garde son nom « Restaurants » pour les lecteurs d'écran.
  Sur ordinateur, rien ne change.

**Reporté dans le thème** (`bigm-child/assets/css/header.css`, version 0.6.1, en 44 × 44 px : taille tactile minimale du brief). Une fois le thème 0.6.1 installé, vider ce CSS additionnel.
