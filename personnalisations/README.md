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

Google Place ID des restaurants (champ « Google Place ID » des fiches StoreLocatore : avis Google, horaires, lien « Laisser un avis »), saisis sur la préprod le 9 novembre 2026 : les 70 restaurants (Colmar avec le Place ID de son Big Canteen). Mérignac, Le Creusot, Sarreguemines, Villeurbanne – Croix Luizet et Sète, fermés, ont été mis à la corbeille le 9 novembre 2026 (sauvegardes gardées hors du dépôt). Trouvés par l'API Places de Google (« Big M » + adresse de la fiche, avec la clé des réglages de StoreLocatore), en ne gardant qu'un établissement « Big M » ouvert à l'adresse de la fiche. Les 5 Place ID déjà saisis à la main étaient identiques. `gps_corrige` signale les 20 fiches (21 avec Sète, fermé depuis) dont l'épingle était à plus de 50 m du restaurant sur Google (jusqu'à 937 m pour Nevers) : coordonnées remplacées par celles de Google le 9 novembre 2026, anciennes valeurs sauvegardées hors du dépôt. Toutes les fiches sont maintenant à moins de 50 m de leur établissement Google.

## Food court Big Canteen

Restaurants → Food courts → **Big Canteen** (créé le 9 novembre 2026 sur la préprod) : présentation « Le food court du groupe Big M : plusieurs enseignes sous un même toit, une grande salle, et chacun choisit ce qu’il aime. », marques par défaut Big M, Fatburger, Buffalo’s Café, Original Riz Crousty, ADS. Associé à **Colmar - Big Canteen** (marques sur place : Big M, ADS ; la fiche s'appelait « Colmar », son adresse `/restaurants/colmar/` n'a pas changé). Le Big Canteen de Mérignac a fermé : sa fiche a été mise à la corbeille le 9 novembre 2026 (sauvegarde gardée hors du dépôt). Marques d'après la presse des ouvertures (2026), à confirmer. Logo et site web à ajouter.


## Catégories Poulet et Sauces retirées de la carte

Supprimées dans l'admin par l'agence le 9 novembre 2026, volontairement et pour le moment : pas de commande de ces produits actuellement. Les 2 Chick'n Box (Curry, Tandoori) et les 13 sauces restent publiés, sans catégorie, donc absents de la carte. Ne pas les recréer ni ranger ces produits ailleurs sans demande. `data/carte.json` contient toujours ces deux catégories : un import complet (Produits → Importer la carte) les recréerait et y remettrait ces produits. Pour ajouter ou corriger un produit, passer par l'import ciblé (`POST bigm/v1/carte/importer`), qui ne crée que les catégories du produit demandé.

## Services, paiements et bouton Commander des restaurants

Remplis le 9 novembre 2026 en production, par l'API REST (bigm-core 0.8.9), sur les 70 restaurants : **Services proposés** « Vente à emporter, Livraison, Sur place », **Moyens de paiement acceptés** « Carte bancaire, Espèces, Ticket restaurant ». **URL du bouton Commander** (`tfmsl_order_url`) : le lien Uber Eats du restaurant (celui de Restaurants → Liens de commande) pour 64 restaurants. Inchangés : Boulogne-Billancourt, Dakar, Dakar 2 – Mariste, Dakar 2 – Plateau et Nevers (pas de lien Uber Eats) ; Vienne garde `https://borneoapp.com/BigMVienne`, son appli de commande, proposée en « Commander en ligne » dans la fenêtre Commander. Anciennes valeurs sauvegardées hors du dépôt.
