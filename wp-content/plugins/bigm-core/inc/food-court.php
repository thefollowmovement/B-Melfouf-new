<?php
/**
 * Food courts (Big Canteen) : des restaurants Big M installés dans un food court du groupe, où
 * plusieurs enseignes partagent la même salle.
 *
 * - Restaurants → Food courts : un food court (nom, présentation, logo, marques par défaut, site).
 * - Fiche d'un restaurant : case du food court, et « Marques sur place » (une par ligne) si elles
 *   diffèrent de celles du food court.
 * - Site : bloc « Food court » sous le bandeau de la fiche du restaurant (crochet
 *   tfmsl_after_store_hero du gabarit de StoreLocatore), ou shortcode [bigm_food_court] dans un
 *   modèle Elementor. CSS chargé seulement sur ces fiches.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		if ( ! post_type_exists( 'tfmsl_store' ) ) {
			return;
		}
		register_taxonomy(
			'bigm_food_court',
			'tfmsl_store',
			array(
				'labels'            => array(
					'name'          => 'Food courts',
					'singular_name' => 'Food court',
					'menu_name'     => 'Food courts',
					'all_items'     => 'Tous les food courts',
					'edit_item'     => 'Modifier le food court',
					'add_new_item'  => 'Ajouter un food court',
					'new_item_name' => 'Nom du food court',
					'search_items'  => 'Rechercher un food court',
					'not_found'     => 'Aucun food court',
					'back_to_items' => '← Food courts',
				),
				'description'       => 'Food courts du groupe (Big Canteen) où se trouve un restaurant Big M.',
				'public'            => false,
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'hierarchical'      => true,
				'rewrite'           => false,
			)
		);
		$auth = static fn( $permis, $cle, $id ): bool => current_user_can( 'manage_categories' );
		foreach ( array(
			'_bigm_fc_logo'    => 'integer',
			'_bigm_fc_marques' => 'string',
			'_bigm_fc_site'    => 'string',
		) as $cle => $type ) {
			register_term_meta(
				'bigm_food_court',
				$cle,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => $auth,
					'sanitize_callback' => match ( $cle ) {
						'_bigm_fc_logo'    => static fn( $v ): int => bigm_nettoyer_media( $v, 'image/' ),
						'_bigm_fc_site'    => static fn( $v ): string => esc_url_raw( (string) $v ),
						default            => static fn( $v ): string => sanitize_textarea_field( (string) $v ),
					},
				)
			);
		}
		register_post_meta(
			'tfmsl_store',
			'_bigm_fc_marques',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $v ): string => sanitize_textarea_field( (string) $v ),
				'auth_callback'     => static fn( $permis, $cle, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
			)
		);
	},
	20
);

/**
 * Liste « une par ligne » en tableau, sans lignes vides.
 *
 * @param string $texte Texte saisi.
 * @return string[]
 */
function bigm_food_court_lignes( string $texte ): array {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n|,/', $texte ) ) ) );
}

/**
 * Food court d'un restaurant, ou null.
 *
 * @param int $id Restaurant (tfmsl_store).
 * @return array{nom: string, texte: string, logo: int, site: string, marques: string[]}|null
 */
function bigm_restaurant_food_court( int $id ): ?array {
	if ( ! taxonomy_exists( 'bigm_food_court' ) ) {
		return null;
	}
	$termes = get_the_terms( $id, 'bigm_food_court' );
	if ( ! $termes || is_wp_error( $termes ) ) {
		return null;
	}
	$t       = $termes[0];
	$marques = bigm_food_court_lignes( (string) get_post_meta( $id, '_bigm_fc_marques', true ) );
	if ( ! $marques ) {
		$marques = bigm_food_court_lignes( (string) get_term_meta( $t->term_id, '_bigm_fc_marques', true ) );
	}
	return array(
		'nom'     => $t->name,
		'texte'   => (string) $t->description,
		'logo'    => (int) get_term_meta( $t->term_id, '_bigm_fc_logo', true ),
		'site'    => (string) get_term_meta( $t->term_id, '_bigm_fc_site', true ),
		'marques' => $marques,
	);
}

/**
 * Bloc « Food court » de la fiche d'un restaurant (vide s'il n'est pas dans un food court).
 *
 * @param int $id Restaurant (tfmsl_store).
 */
function bigm_rendu_food_court( int $id ): string {
	$fc = bigm_restaurant_food_court( $id );
	if ( ! $fc ) {
		return '';
	}
	$titre   = sprintf( 'Ce Big M est au %s', $fc['nom'] );
	$logo    = $fc['logo'] ? wp_get_attachment_image( $fc['logo'], 'thumbnail', false, array( 'class' => 'bigm-fc__logo-img', 'alt' => $fc['nom'] ) ) : '';
	$marques = '';
	foreach ( $fc['marques'] as $m ) {
		$marques .= '<li>' . esc_html( $m ) . '</li>';
	}
	return '<section class="bigm-fc" aria-labelledby="bigm-fc-titre-' . $id . '"><div class="bigm-fc__carte">'
		. ( $logo ? '<div class="bigm-fc__logo">' . $logo . '</div>' : '' )
		. '<div class="bigm-fc__corps">'
		. '<p class="bigm-fc__surtitre">Food court</p>'
		. '<h2 class="bigm-fc__titre" id="bigm-fc-titre-' . $id . '">' . esc_html( $titre ) . '</h2>'
		. ( '' !== $fc['texte'] ? '<div class="bigm-fc__texte">' . wpautop( esc_html( $fc['texte'] ) ) . '</div>' : '' )
		. ( $marques ? '<p class="bigm-fc__label">Sur place</p><ul class="bigm-fc__marques" aria-label="' . esc_attr( 'Enseignes du ' . $fc['nom'] ) . '">' . $marques . '</ul>' : '' )
		. ( '' !== $fc['site'] ? '<p class="bigm-fc__lien"><a class="btn btn--outline" href="' . esc_url( $fc['site'] ) . '">' . esc_html( 'Découvrir le ' . $fc['nom'] ) . '</a></p>' : '' )
		. '</div></div></section>';
}

/*
 * Fiche d'un restaurant (gabarit de StoreLocatore) : le bloc sous le bandeau, après les promos.
 */
add_action(
	'tfmsl_after_store_hero',
	static function ( $post_id ) {
		echo bigm_rendu_food_court( (int) $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_rendu_food_court().
	},
	20
);

/*
 * [bigm_food_court] : le même bloc dans un modèle Elementor de fiche (restaurant de la page).
 */
add_shortcode(
	'bigm_food_court',
	static function (): string {
		wp_enqueue_style( 'bigm-food-court' );
		return bigm_rendu_food_court( (int) get_the_ID() );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$fichier = BIGM_CORE_DIR . '/assets/css/food-court.css';
		wp_register_style( 'bigm-food-court', BIGM_CORE_URL . 'assets/css/food-court.css', array(), is_file( $fichier ) ? (string) filemtime( $fichier ) : BIGM_CORE_VERSION );
		if ( is_singular( 'tfmsl_store' ) && bigm_restaurant_food_court( (int) get_queried_object_id() ) ) {
			wp_enqueue_style( 'bigm-food-court' );
		}
	},
	25
);

/* ---------------------------------------------------------------------------------------------
 * Admin : champs d'un food court, et marques sur place dans la fiche d'un restaurant.
 * ------------------------------------------------------------------------------------------ */

/**
 * Champs d'un food court (ajout et modification).
 *
 * @param WP_Term|null $terme Food court, ou null à l'ajout.
 */
function bigm_food_court_champs( ?WP_Term $terme = null ): void {
	$v = static fn( string $cle ) => $terme ? get_term_meta( $terme->term_id, $cle, true ) : '';
	$lignes = array(
		'logo'    => array( 'Logo', static fn() => bigm_champ_media( 'bigm_fc[logo]', (int) $v( '_bigm_fc_logo' ), 'image', '', 'Carré, affiché dans le bloc de la fiche.' ) ),
		'marques' => array( 'Marques', static fn() => printf( '<textarea id="bigm-fc-marques" name="bigm_fc[marques]" rows="5" class="large-text">%s</textarea><p class="description">Une par ligne, dans l’ordre d’affichage. Chaque restaurant peut avoir sa propre liste (fiche du restaurant, bloc « Food court »).</p>', esc_textarea( (string) $v( '_bigm_fc_marques' ) ) ) ),
		'site'    => array( 'Site web', static fn() => printf( '<input type="url" id="bigm-fc-site" name="bigm_fc[site]" value="%s" class="regular-text"><p class="description">Facultatif : bouton « Découvrir le … ».</p>', esc_attr( (string) $v( '_bigm_fc_site' ) ) ) ),
	);
	wp_nonce_field( 'bigm_food_court', 'bigm_fc_nonce' );
	foreach ( $lignes as $cle => list( $label, $champ ) ) {
		if ( $terme ) {
			echo '<tr class="form-field"><th scope="row"><label for="bigm-fc-' . esc_attr( $cle ) . '">' . esc_html( $label ) . '</label></th><td>';
			$champ();
			echo '</td></tr>';
		} else {
			echo '<div class="form-field"><label for="bigm-fc-' . esc_attr( $cle ) . '">' . esc_html( $label ) . '</label>';
			$champ();
			echo '</div>';
		}
	}
}
add_action( 'bigm_food_court_add_form_fields', static fn() => bigm_food_court_champs() );
add_action( 'bigm_food_court_edit_form_fields', static fn( WP_Term $t ) => bigm_food_court_champs( $t ) );

/**
 * Enregistre les champs d'un food court.
 *
 * @param int $term_id Food court.
 */
function bigm_food_court_enregistrer( int $term_id ): void {
	if ( ! isset( $_POST['bigm_fc_nonce'], $_POST['bigm_fc'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bigm_fc_nonce'] ) ), 'bigm_food_court' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$d = (array) wp_unslash( $_POST['bigm_fc'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par les sanitize_callback des meta.
	update_term_meta( $term_id, '_bigm_fc_logo', $d['logo'] ?? 0 );
	update_term_meta( $term_id, '_bigm_fc_marques', $d['marques'] ?? '' );
	update_term_meta( $term_id, '_bigm_fc_site', $d['site'] ?? '' );
}
add_action( 'created_bigm_food_court', 'bigm_food_court_enregistrer' );
add_action( 'edited_bigm_food_court', 'bigm_food_court_enregistrer' );

add_action(
	'add_meta_boxes_tfmsl_store',
	static function () {
		add_meta_box(
			'bigm-food-court-marques',
			'Food court : marques sur place',
			static function ( WP_Post $post ) {
				wp_nonce_field( 'bigm_fc_marques_' . $post->ID, 'bigm_fc_marques_nonce' );
				printf(
					'<p><label for="bigm-fc-marques-resto" class="screen-reader-text">Marques sur place</label><textarea id="bigm-fc-marques-resto" name="bigm_fc_marques" rows="5" class="widefat">%s</textarea></p><p class="description">Si ce restaurant est dans un food court (bloc « Food courts »), les enseignes présentes ici, une par ligne. Vide : celles du food court.</p>',
					esc_textarea( (string) get_post_meta( $post->ID, '_bigm_fc_marques', true ) )
				);
			},
			'tfmsl_store',
			'side',
			'low'
		);
	}
);

add_action(
	'save_post_tfmsl_store',
	static function ( int $post_id ) {
		if ( ! isset( $_POST['bigm_fc_marques_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bigm_fc_marques_nonce'] ) ), 'bigm_fc_marques_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_bigm_fc_marques', sanitize_textarea_field( wp_unslash( $_POST['bigm_fc_marques'] ?? '' ) ) );
	},
	20
);
