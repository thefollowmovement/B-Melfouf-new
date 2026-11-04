<?php
/**
 * Type de contenu Produit (bigm_produit) et taxonomie des catégories (bigm_categorie).
 *
 * Adresses : /carte/ (tous les produits), /carte/burgers/ (une catégorie),
 * /produit/smash-naan/ (une fiche). Pas de prix : demande du client.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le type de contenu et la taxonomie.
 */
function bigm_core_register_produit(): void {
	register_post_type(
		'bigm_produit',
		array(
			'labels'        => array(
				'name'               => 'Produits',
				'singular_name'      => 'Produit',
				'menu_name'          => 'Produits',
				'all_items'          => 'Tous les produits',
				'add_new'            => 'Ajouter',
				'add_new_item'       => 'Ajouter un produit',
				'edit_item'          => 'Modifier le produit',
				'new_item'           => 'Nouveau produit',
				'view_item'          => 'Voir le produit',
				'view_items'         => 'Voir la carte',
				'search_items'       => 'Rechercher un produit',
				'not_found'          => 'Aucun produit',
				'not_found_in_trash' => 'Aucun produit dans la corbeille',
				'featured_image'     => 'Image principale',
				'set_featured_image' => 'Choisir l’image principale',
				'archives'           => 'La carte',
			),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-food',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
			'has_archive'   => 'carte',
			'rewrite'       => array(
				'slug'       => 'produit',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'bigm_categorie',
		'bigm_produit',
		array(
			'labels'            => array(
				'name'          => 'Catégories de la carte',
				'singular_name' => 'Catégorie',
				'menu_name'     => 'Catégories',
				'all_items'     => 'Toutes les catégories',
				'edit_item'     => 'Modifier la catégorie',
				'view_item'     => 'Voir la catégorie',
				'add_new_item'  => 'Ajouter une catégorie',
				'new_item_name' => 'Nom de la catégorie',
				'parent_item'   => 'Catégorie parente',
				'search_items'  => 'Rechercher une catégorie',
				'not_found'     => 'Aucune catégorie',
				'back_to_items' => '← Retour aux catégories',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'         => 'carte',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);
}
add_action( 'init', 'bigm_core_register_produit' );

/*
 * Éditeur classique pour les produits et les sliders : les champs restent
 * sous le titre et la description, sans passer par l'éditeur de blocs.
 */
add_filter(
	'use_block_editor_for_post_type',
	static fn( bool $use, string $type ): bool => in_array( $type, array( 'bigm_produit', 'bigm_slider' ), true ) ? false : $use,
	10,
	2
);

/*
 * Blocs de champs de la fiche produit.
 */
add_action(
	'add_meta_boxes_bigm_produit',
	static function () {
		add_meta_box( 'bigm_produit_fiche', 'Fiche produit', 'bigm_produit_box_fiche', null, 'normal', 'high' );
		add_meta_box( 'bigm_produit_ingredients', 'Ingrédients', 'bigm_produit_box_ingredients', null, 'normal' );
		add_meta_box( 'bigm_produit_allergenes', 'Allergènes', 'bigm_produit_box_allergenes', null, 'normal' );
		add_meta_box( 'bigm_produit_nutrition', 'Valeurs nutritionnelles', 'bigm_produit_box_nutrition', null, 'normal' );
		add_meta_box( 'bigm_produit_associes', 'Ça va bien avec', 'bigm_produit_box_associes', null, 'normal' );
		add_meta_box( 'bigm_produit_video', 'Vidéo de vignette', 'bigm_produit_box_video', null, 'side' );
	}
);

/**
 * Badge, viande, lien de commande.
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_fiche( WP_Post $post ): void {
	wp_nonce_field( 'bigm_produit_save', 'bigm_produit_nonce' );
	$badge  = (string) get_post_meta( $post->ID, '_bigm_badge', true );
	$viande = (string) get_post_meta( $post->ID, '_bigm_viande', true );
	$lien   = (string) get_post_meta( $post->ID, '_bigm_lien_commande', true );
	$global = bigm_reglage( 'commander_url' );
	?>
	<div class="bigm-grid">
		<p>
			<label for="bigm_badge"><strong>Badge</strong></label>
			<input type="text" id="bigm_badge" name="bigm_badge" value="<?php echo esc_attr( $badge ); ?>" list="bigm_badges" class="widefat" maxlength="40">
			<datalist id="bigm_badges"><option value="Nouveau"><option value="Édition limitée"><option value="Best-seller"></datalist>
			<span class="description">Pastille jaune sur la vignette. Laisser vide pour ne rien afficher.</span>
		</p>
		<p>
			<label for="bigm_viande"><strong>Viande</strong></label>
			<select id="bigm_viande" name="bigm_viande" class="widefat">
				<option value="">— Aucune —</option>
				<?php foreach ( bigm_viandes() as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $viande, $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description">Sert au filtre « Bœuf / Poulet » de la catégorie.</span>
		</p>
		<p class="bigm-grid__full">
			<label for="bigm_lien_commande"><strong>Lien de commande</strong></label>
			<input type="url" id="bigm_lien_commande" name="bigm_lien_commande" value="<?php echo esc_attr( $lien ); ?>" class="widefat" placeholder="<?php echo esc_attr( $global ? $global : 'https://' ); ?>">
			<span class="description">
				<?php if ( $global ) : ?>
					Laisser vide pour utiliser le lien général des Réglages Big M.
				<?php else : ?>
					Laisser vide pour utiliser le lien général, à renseigner dans Réglages Big M.
				<?php endif; ?>
			</span>
		</p>
	</div>
	<p class="description">Pas de prix sur le site : c'est une demande du client. La description se saisit dans l'éditeur ci-dessus, l'image principale dans le bloc « Image principale ».</p>
	<?php
}

/**
 * Liste ordonnée des ingrédients, une ligne par ingrédient.
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_ingredients( WP_Post $post ): void {
	$lignes = (array) get_post_meta( $post->ID, '_bigm_ingredients', true );
	?>
	<textarea name="bigm_ingredients" rows="7" class="widefat" placeholder="Naan grillé&#10;Steak de bœuf smashé ×2&#10;Cheddar fondu ×2"><?php echo esc_textarea( implode( "\n", array_filter( $lignes ) ) ); ?></textarea>
	<p class="description">Un ingrédient par ligne, dans l'ordre d'affichage.</p>
	<?php
}

/**
 * Les 14 allergènes : « Contient » et « Traces ».
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_allergenes( WP_Post $post ): void {
	$contient = (array) get_post_meta( $post->ID, '_bigm_allergenes_contient', true );
	$traces   = (array) get_post_meta( $post->ID, '_bigm_allergenes_traces', true );
	?>
	<table class="widefat striped bigm-allergenes">
		<thead><tr><th scope="col">Allergène</th><th scope="col">Contient</th><th scope="col">Peut contenir des traces</th></tr></thead>
		<tbody>
		<?php foreach ( bigm_allergenes() as $slug => $a ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $a['label'] ); ?><br><span class="description"><?php echo esc_html( $a['detail'] ); ?></span></th>
				<td><label><input type="checkbox" name="bigm_allergenes_contient[]" value="<?php echo esc_attr( $slug ); ?>"<?php checked( in_array( $slug, $contient, true ) ); ?>><span class="screen-reader-text">Contient : <?php echo esc_html( $a['label'] ); ?></span></label></td>
				<td><label><input type="checkbox" name="bigm_allergenes_traces[]" value="<?php echo esc_attr( $slug ); ?>"<?php checked( in_array( $slug, $traces, true ) ); ?>><span class="screen-reader-text">Traces : <?php echo esc_html( $a['label'] ); ?></span></label></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">Données des fiches fournisseurs. Ces cases alimentent le filtre par allergène de la catégorie. Un allergène coché dans les deux colonnes est enregistré dans « Contient ».</p>
	<?php
}

/**
 * Valeurs nutritionnelles, pour une portion et pour 100 g.
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_nutrition( WP_Post $post ): void {
	$n = (array) get_post_meta( $post->ID, '_bigm_nutrition', true );
	?>
	<p>
		<label for="bigm_portion_g"><strong>Poids d'une portion (g)</strong></label>
		<input type="text" id="bigm_portion_g" name="bigm_nutrition[portion_g]" value="<?php echo esc_attr( $n['portion_g'] ?? '' ); ?>" class="small-text" inputmode="decimal">
	</p>
	<table class="widefat striped bigm-nutrition">
		<thead><tr><th scope="col">Pour</th><th scope="col">1 portion</th><th scope="col">100 g</th></tr></thead>
		<tbody>
		<?php foreach ( bigm_nutrition_lignes() as $cle => $label ) : ?>
			<tr>
				<th scope="row"><?php echo esc_html( $label ); ?></th>
				<?php foreach ( array( 'portion', '100g' ) as $col ) : ?>
					<td><input type="text" name="bigm_nutrition[<?php echo esc_attr( $col ); ?>][<?php echo esc_attr( $cle ); ?>]" value="<?php echo esc_attr( $n[ $col ][ $cle ] ?? '' ); ?>" class="small-text" inputmode="decimal" aria-label="<?php echo esc_attr( $label . ', ' . ( 'portion' === $col ? '1 portion' : '100 g' ) ); ?>"></td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">Valeurs des fiches fournisseurs, avec une virgule pour les décimales (ex. 12,5). Une ligne vide n'est pas affichée.</p>
	<?php
}

/**
 * Produits associés (« Ça va bien avec »).
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_associes( WP_Post $post ): void {
	$choisis  = array_map( 'intval', (array) get_post_meta( $post->ID, '_bigm_associes', true ) );
	$produits = get_posts(
		array(
			'post_type'      => 'bigm_produit',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 500,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'exclude'        => array( $post->ID ),
		)
	);
	if ( ! $produits ) {
		echo '<p class="description">Aucun autre produit pour l’instant.</p>';
		return;
	}
	?>
	<p><input type="search" class="widefat bigm-filtre" placeholder="Filtrer les produits…" aria-label="Filtrer les produits" data-filtre=".bigm-associes label"></p>
	<div class="bigm-associes">
		<?php foreach ( $produits as $p ) : ?>
			<label><input type="checkbox" name="bigm_associes[]" value="<?php echo (int) $p->ID; ?>"<?php checked( in_array( $p->ID, $choisis, true ) ); ?>> <?php echo esc_html( get_the_title( $p ) ); ?><?php echo 'publish' === $p->post_status ? '' : ' <em>(' . esc_html( get_post_status_object( $p->post_status )->label ) . ')</em>'; ?></label>
		<?php endforeach; ?>
	</div>
	<p class="description">Trois produits en général (accompagnement, boisson, dessert).</p>
	<?php
}

/**
 * Vidéo de vignette : remplace la photo dans la vignette, lue en boucle et sans son.
 *
 * @param WP_Post $post Produit.
 */
function bigm_produit_box_video( WP_Post $post ): void {
	$v = bigm_nettoyer_video( get_post_meta( $post->ID, '_bigm_video', true ) );
	bigm_champ_media( 'bigm_video[mp4]', $v['mp4'], 'video/mp4', 'Vidéo MP4 (H.264)' );
	bigm_champ_media( 'bigm_video[webm]', $v['webm'], 'video/webm', 'Vidéo WebM (VP9)' );
	bigm_champ_media( 'bigm_video[poster]', $v['poster'], 'image', 'Image d’aperçu' );
	echo '<p class="description">Facultatif. 3 Mo maximum par fichier. Sans vidéo, la vignette montre l’image principale.</p>';
}

/*
 * Enregistrement des champs.
 */
add_action(
	'save_post_bigm_produit',
	static function ( int $post_id ) {
		if ( ! isset( $_POST['bigm_produit_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bigm_produit_nonce'] ), 'bigm_produit_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = wp_unslash( $_POST );

		update_post_meta( $post_id, '_bigm_badge', sanitize_text_field( $in['bigm_badge'] ?? '' ) );
		$viande = sanitize_key( $in['bigm_viande'] ?? '' );
		update_post_meta( $post_id, '_bigm_viande', array_key_exists( $viande, bigm_viandes() ) ? $viande : '' );
		update_post_meta( $post_id, '_bigm_lien_commande', esc_url_raw( trim( (string) ( $in['bigm_lien_commande'] ?? '' ) ) ) );

		$lignes = preg_split( '/\R/', (string) ( $in['bigm_ingredients'] ?? '' ) );
		update_post_meta( $post_id, '_bigm_ingredients', array_values( array_filter( array_map( 'sanitize_text_field', $lignes ) ) ) );

		$slugs    = array_keys( bigm_allergenes() );
		$contient = bigm_nettoyer_slugs( $in['bigm_allergenes_contient'] ?? array(), $slugs );
		$traces   = array_values( array_diff( bigm_nettoyer_slugs( $in['bigm_allergenes_traces'] ?? array(), $slugs ), $contient ) );
		update_post_meta( $post_id, '_bigm_allergenes_contient', $contient );
		update_post_meta( $post_id, '_bigm_allergenes_traces', $traces );

		$recu      = is_array( $in['bigm_nutrition'] ?? null ) ? $in['bigm_nutrition'] : array();
		$nutrition = array( 'portion_g' => bigm_nettoyer_nutrition( $recu['portion_g'] ?? '' ) );
		foreach ( array( 'portion', '100g' ) as $col ) {
			foreach ( array_keys( bigm_nutrition_lignes() ) as $cle ) {
				$nutrition[ $col ][ $cle ] = bigm_nettoyer_nutrition( $recu[ $col ][ $cle ] ?? '' );
			}
		}
		update_post_meta( $post_id, '_bigm_nutrition', $nutrition );

		$associes = array_map( 'absint', is_array( $in['bigm_associes'] ?? null ) ? $in['bigm_associes'] : array() );
		$associes = array_filter( $associes, static fn( int $id ): bool => $id !== $post_id && 'bigm_produit' === get_post_type( $id ) );
		update_post_meta( $post_id, '_bigm_associes', array_values( array_unique( $associes ) ) );

		update_post_meta( $post_id, '_bigm_video', bigm_nettoyer_video( $in['bigm_video'] ?? array() ) );
	}
);

/*
 * Colonnes de la liste des produits : image, badge, viande.
 */
add_filter(
	'manage_bigm_produit_posts_columns',
	static function ( array $cols ): array {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'title' === $key ) {
				$new['bigm_image'] = '<span class="screen-reader-text">Image</span>';
			}
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['bigm_badge']  = 'Badge';
				$new['bigm_viande'] = 'Viande';
			}
		}
		return $new;
	}
);

add_action(
	'manage_bigm_produit_posts_custom_column',
	static function ( string $col, int $post_id ) {
		if ( 'bigm_image' === $col ) {
			echo get_the_post_thumbnail( $post_id, array( 48, 48 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'bigm_badge' === $col ) {
			echo esc_html( (string) get_post_meta( $post_id, '_bigm_badge', true ) );
		} elseif ( 'bigm_viande' === $col ) {
			$viande = (string) get_post_meta( $post_id, '_bigm_viande', true );
			echo esc_html( bigm_viandes()[ $viande ] ?? '' );
		}
	},
	10,
	2
);

/*
 * Ordre manuel (champ « Ordre ») par défaut dans la liste de l'admin.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ) {
		if ( is_admin() && $q->is_main_query() && 'bigm_produit' === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		}
	}
);
