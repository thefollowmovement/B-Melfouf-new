# Plugin bigm-core — contenus gérés dans l'admin

Le plugin `wp-content/plugins/bigm-core/` (« Big M — cœur ») porte tout ce que le client modifie sans toucher au code. Les champs sont des **meta boxes natives** : aucune extension de champs n'est nécessaire.

Installation sans SSH : `ops/build-zips.sh`, puis Extensions → Ajouter → Téléverser `bigm-core.zip` → Activer. L'activation crée les adresses et les catégories par défaut.

## Dans l'admin

### Produits

Menu **Produits**. Éditeur classique : titre, description, image principale, puis ces blocs :

| Bloc | Contenu |
|---|---|
| Fiche produit | Badge (Nouveau, Édition limitée…), viande (pour le filtre Bœuf / Poulet), lien de commande propre au produit |
| Ingrédients | Un par ligne, dans l'ordre d'affichage |
| Allergènes | Les 14 allergènes réglementaires, colonnes « Contient » et « Peut contenir des traces » |
| Valeurs nutritionnelles | Poids d'une portion, puis énergie, matières grasses, dont saturées, glucides, dont sucres, protéines, sel, pour 1 portion et pour 100 g |
| Ça va bien avec | Produits associés, avec un champ de filtre |
| Vidéo de vignette | MP4, WebM et image d'aperçu ; remplace la photo dans la vignette |
| Attributs → Ordre | Position du produit dans sa catégorie |

Pas de prix : c'est une demande du client.

### Catégories

**Produits → Catégories**. Créées à l'activation, dans l'ordre du prototype : Nouveautés, Menus, Burgers (Bœuf, Poulet), Poulet, Tex-Mex, Petite faim, Desserts, Boissons, avec leurs icônes. Champs propres :

- **Titre de la page** (« Nos burgers ») ;
- **Ordre d'affichage** ;
- **Icône (SVG)** : un SVG complet ou seulement son tracé `d`, sur une grille de 24 × 24 ;
- **Vignette** : image, vidéo MP4 + WebM et image d'aperçu ;
- **Bannières** : le slider affiché en haut de la page.

La description native sert de chapeau sous le titre. Une catégorie supprimée n'est pas recréée.

### Sliders

Menu **Sliders**. Un slider est une liste de slides, à réordonner par glisser-déposer ou avec les flèches. Chaque slide comprend :

- **un type** : photo (image ordinateur et image mobile) ou vidéo (MP4, WebM et image d'aperçu) ;
- **un texte alternatif et un lien** ;
- **un cadrage** (`50% 40%`) ;
- **des dates de début et de fin**, facultatives, à l'heure du site.

Chaque slide affiche son état : visible, programmée ou terminée. Une slide sans aucun média n'est pas enregistrée, et un avertissement le signale.

Les sliders s'affichent avec le widget Elementor « Big M Slider » (étape 4), ou en bannières d'une catégorie.

### Réglages Big M

Menu **Réglages Big M** : lien Commander, page Restaurants, App Store et Google Play, réseaux sociaux, e-mail, siège et mention du pied de page, chiffres de la franchise. Chaque champ indique son shortcode, à placer dans un widget Texte d'Elementor : `[bigm_reglage cle="franchise_droit_entree"]` affiche « 30 000 € HT ». Les nombres sont toujours mis en forme à la française.

### Actualités

Articles natifs. L'activation crée les catégories **News** et **Presse**.

## Adresses

| Adresse | Contenu |
|---|---|
| `/carte/` | Archive des produits |
| `/carte/burgers/`, `/carte/burgers/boeuf/` | Une catégorie, une sous-catégorie |
| `/produit/smash-naan/` | Une fiche produit |

## Données (pour les développeurs)

Les clés commencent par `_bigm_` : elles n'apparaissent pas dans le bloc « Champs personnalisés » de WordPress.

| Où | Clé | Valeur |
|---|---|---|
| Produit | `_bigm_badge` | texte |
| | `_bigm_viande` | `boeuf`, `poulet`, `poisson`, `vegetarien` ou vide (filtre `bigm_viandes`) |
| | `_bigm_lien_commande` | URL, vide = lien général |
| | `_bigm_ingredients` | tableau de textes |
| | `_bigm_allergenes_contient`, `_bigm_allergenes_traces` | tableaux d'identifiants : `gluten`, `lait`, `oeufs`, `moutarde`, `sesame`, `soja`, `celeri`, `poissons`, `crustaces`, `mollusques`, `arachides`, `fruitsacoque`, `sulfites`, `lupin` (ceux du prototype) |
| | `_bigm_nutrition` | `portion_g`, puis `portion` et `100g` → `kj`, `kcal`, `lipides`, `satures`, `glucides`, `sucres`, `proteines`, `sel` (texte saisi, ex. `12,5`) |
| | `_bigm_associes` | identifiants de produits |
| | `_bigm_video` | `mp4`, `webm`, `poster` (identifiants de médias) |
| Catégorie | `_bigm_titre`, `_bigm_ordre`, `_bigm_icone` (SVG nettoyé), `_bigm_image`, `_bigm_video`, `_bigm_slider` | |
| Slider | `_bigm_slides` | liste de slides : `type`, `image`, `image_mobile`, `mp4`, `webm`, `poster`, `alt`, `lien`, `cadrage`, `debut`, `fin` |
| Option | `bigm_reglages` | voir `bigm_reglages_champs()` |

Les types des médias sont vérifiés à l'enregistrement : un MP4 n'est pas accepté dans le champ WebM, ni une vidéo dans un champ image.

## Fonctions pour les widgets

Les widgets des étapes 4 et 5 lisent les données par ces fonctions, jamais par `get_post_meta()` :

| Fonction | Renvoie |
|---|---|
| `bigm_produit( $post )` | toutes les données d'un produit : vidéo avec URL, allergènes et libellés, lignes nutritionnelles remplies, associés publiés, lien de commande |
| `bigm_produits_de( $term_id )` | produits publiés d'une catégorie et de ses sous-catégories, dans l'ordre manuel |
| `bigm_categorie( $term )` | titre, icône, vignette, slider, URL |
| `bigm_categories( $parent = 0 )` | catégories dans l'ordre d'affichage |
| `bigm_slides_actives( $slider_id )` | slides visibles maintenant, avec les URL des médias et le cadrage |
| `bigm_reglage( $cle )`, `bigm_reglage_texte( $cle )` | valeur brute ou mise en forme |
| `bigm_lien_commande( $post_id = null )` | lien du produit, sinon le lien général |
| `bigm_allergenes()`, `bigm_viandes()`, `bigm_nutrition_lignes()` | listes de référence |

## Tester

Sur un site de test où le plugin est actif. Le test crée des contenus puis les supprime :

```bash
wp eval-file tests/wp/test-bigm-core.php --user=IDENTIFIANT_ADMIN
```

Il fait 42 vérifications :

- adresses et catégories créées à l'activation ;
- nettoyage des SVG ;
- enregistrement de chaque champ de la fiche produit ;
- réglages et shortcode ;
- dates des slides ;
- champs des catégories.
