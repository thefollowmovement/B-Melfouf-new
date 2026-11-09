# Personnalisations du site (preprod Big M)

Réglages faits dans l'admin WordPress, gardés ici pour ne pas les perdre.

## `css-additionnel.css` → Apparence → Personnaliser → CSS additionnel

- **Icône de localisation dans l'en-tête mobile** (≤ 960 px) : le thème `bigm-child` masque sur mobile le lien
  « Restaurants » de l'en-tête (classe `hide-sm`). Ce CSS l'affiche en icône seule (40 × 40 px), à droite,
  en face du bouton menu. Le lien va vers la page Restaurants des Réglages Big M (`/restaurants/` par défaut),
  passe en orange sur cette page, et garde son nom « Restaurants » pour les lecteurs d'écran.
  Sur ordinateur, rien ne change.

**Reporté dans le thème** (`bigm-child/assets/css/header.css`, version 0.6.1, en 44 × 44 px : taille tactile minimale du brief). Une fois le thème 0.6.1 installé, vider ce CSS additionnel.

## `liens-commande.json`

Liens Uber Eats et Deliveroo de chaque restaurant (**Restaurants → Liens de commande**), saisis sur la préprod le 9 novembre 2026 d'après les fiches trouvées par recherche web : 69 liens Uber Eats et 58 liens Deliveroo, sur 70 restaurants. La clé `a_verifier` signale les cas douteux. Pour les remettre sur un autre site (la production après la bascule, si sa base ne vient pas de la préprod), les envoyer à `POST /wp-json/bigm/v1/commander/restaurants` (voir `docs/restaurants.md`) : les identifiants des restaurants doivent être les mêmes.
