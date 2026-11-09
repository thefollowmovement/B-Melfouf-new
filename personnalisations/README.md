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

## `place-id.json`

Google Place ID des restaurants (champ « Google Place ID » des fiches StoreLocatore : avis Google, horaires, lien « Laisser un avis »), saisis sur la préprod le 9 novembre 2026 : 71 restaurants sur 74 (Colmar avec le Place ID de son Big Canteen ; Mérignac, fermé, retiré). Trouvés par l'API Places de Google (« Big M » + adresse de la fiche, avec la clé des réglages de StoreLocatore), en ne gardant qu'un établissement « Big M » ouvert à l'adresse de la fiche. Les 5 Place ID déjà saisis à la main étaient identiques. `non_remplis` donne la raison des 5 autres ; `gps_corrige` signale les 7 fiches dont les coordonnées GPS étaient à plus de 300 m du restaurant sur Google (Sète, Nevers, O'Parinor, Rosny 2, Metz, Saumur, Claye-Souilly) : remplacées par celles de Google le 9 novembre 2026, anciennes valeurs sauvegardées hors du dépôt.

## Food court Big Canteen

Restaurants → Food courts → **Big Canteen** (créé le 9 novembre 2026 sur la préprod) : présentation « Le food court du groupe Big M : plusieurs enseignes sous un même toit, une grande salle, et chacun choisit ce qu’il aime. », marques par défaut Big M, Fatburger, Buffalo’s Café, Original Riz Crousty, ADS. Associé à **Colmar - Big Canteen** (marques sur place : Big M, ADS ; la fiche s'appelait « Colmar », son adresse `/restaurants/colmar/` n'a pas changé). Le Big Canteen de Mérignac a fermé : sa fiche a été mise à la corbeille le 9 novembre 2026 (sauvegarde gardée hors du dépôt). Marques d'après la presse des ouvertures (2026), à confirmer. Logo et site web à ajouter.

