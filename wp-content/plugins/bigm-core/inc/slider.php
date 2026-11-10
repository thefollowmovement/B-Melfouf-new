<?php
/**
 * Sliders et bannières (bigm_slider) : une liste de slides, photo ou vidéo.
 *
 * Un même slider sert au « À la une » de l'accueil, aux bannières de /carte/
 * ou à celles d'une catégorie : le widget « Big M Slider » (étape 4) choisit
 * le format, hero (2:1) ou bannière (3:1).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_type(
			'bigm_slider',
			array(
				'labels'        => array(
					'name'               => 'Sliders',
					'singular_name'      => 'Slider',
					'menu_name'          => 'Sliders',
					'all_items'          => 'Tous les sliders',
					'add_new'            => 'Ajouter',
					'add_new_item'       => 'Ajouter un slider',
					'edit_item'          => 'Modifier le slider',
					'new_item'           => 'Nouveau slider',
					'search_items'       => 'Rechercher un slider',
					'not_found'          => 'Aucun slider',
					'not_found_in_trash' => 'Aucun slider dans la corbeille',
				),
				'public'        => false,
				'show_ui'       => true,
				'show_in_rest'  => false,
				'menu_position' => 22,
				'menu_icon'     => 'dashicons-images-alt2',
				'supports'      => array( 'title', 'revisions' ),
			)
		);
	}
);

/**
 * Valeurs par défaut d'une slide.
 *
 * @return array<string, int|string>
 */
function bigm_slide_vide(): array {
	return array(
		'type'         => 'image',
		'image'        => 0,
		'image_mobile' => 0,
		'mp4'          => 0,
		'webm'         => 0,
		'poster'       => 0,
		'alt'          => '',
		'lien'         => '',
		'cadrage'      => '',
		'debut'        => '',
		'fin'          => '',
	);
}

/**
 * Nettoie une slide reçue du formulaire. Renvoie null si elle n'a aucun média.
 *
 * @param mixed $s Slide reçue.
 * @return array<string, int|string>|null
 */
function bigm_nettoyer_slide( $s ): ?array {
	if ( ! is_array( $s ) ) {
		return null;
	}
	$slide = bigm_slide_vide();

	$slide['type'] = 'video' === ( $s['type'] ?? '' ) ? 'video' : 'image';
	$types = array(
		'image'        => 'image/',
		'image_mobile' => 'image/',
		'mp4'          => 'video/mp4',
		'webm'         => 'video/webm',
		'poster'       => 'image/',
	);
	foreach ( $types as $media => $mime ) {
		$slide[ $media ] = bigm_nettoyer_media( $s[ $media ] ?? 0, $mime );
	}
	$slide['alt']  = sanitize_text_field( $s['alt'] ?? '' );
	$slide['lien'] = esc_url_raw( trim( (string) ( $s['lien'] ?? '' ) ) );

	$cadrage          = strtolower( trim( (string) ( $s['cadrage'] ?? '' ) ) );
	$slide['cadrage'] = preg_match( '/^(\d{1,3}%|left|center|right)( (\d{1,3}%|top|center|bottom))?$/', $cadrage ) ? $cadrage : '';

	$slide['debut'] = bigm_nettoyer_date( $s['debut'] ?? '' );
	$slide['fin']   = bigm_nettoyer_date( $s['fin'] ?? '' );

	$has_media = $slide['image'] || $slide['image_mobile'] || $slide['mp4'] || $slide['webm'] || $slide['poster'];
	return $has_media ? $slide : null;
}

/**
 * Formulaire d'une slide. $key vaut « __KEY__ » pour le modèle utilisé par le bouton « Ajouter ».
 *
 * @param string                   $key   Clé du champ.
 * @param array<string, int|string> $slide Slide.
 */
function bigm_slide_form( string $key, array $slide ): void {
	$slide = array_merge( bigm_slide_vide(), $slide );
	$name  = static fn( string $field ): string => 'bigm_slides[' . $key . '][' . $field . ']';
	$id    = static fn( string $field ): string => 'bigm_slide_' . $key . '_' . $field;
	$date  = static fn( string $v ): string => $v ? str_replace( ' ', 'T', $v ) : '';
	?>
	<li class="bigm-slide" data-type="<?php echo esc_attr( (string) $slide['type'] ); ?>">
		<div class="bigm-slide__bar">
			<span class="bigm-slide__handle dashicons dashicons-move" title="Glisser pour changer l’ordre" aria-hidden="true"></span>
			<strong class="bigm-slide__title"><?php echo $slide['alt'] ? esc_html( (string) $slide['alt'] ) : 'Nouvelle slide'; ?></strong>
			<span class="bigm-slide__status"></span>
			<button type="button" class="button-link bigm-slide__up" aria-label="Monter la slide">▲</button>
			<button type="button" class="button-link bigm-slide__down" aria-label="Descendre la slide">▼</button>
			<button type="button" class="button-link button-link-delete bigm-slide__remove">Supprimer</button>
		</div>
		<div class="bigm-slide__body">
			<fieldset class="bigm-slide__type">
				<legend class="screen-reader-text">Type de slide</legend>
				<label><input type="radio" name="<?php echo esc_attr( $name( 'type' ) ); ?>" value="image"<?php checked( $slide['type'], 'image' ); ?>> Photo</label>
				<label><input type="radio" name="<?php echo esc_attr( $name( 'type' ) ); ?>" value="video"<?php checked( $slide['type'], 'video' ); ?>> Vidéo</label>
			</fieldset>

			<div class="bigm-slide__medias bigm-slide__medias--image">
				<?php bigm_champ_media( $name( 'image' ), (int) $slide['image'], 'image', 'Image ordinateur', '2400 × 1200 (hero) ou 2400 × 800 (bannière).' ); ?>
				<?php bigm_champ_media( $name( 'image_mobile' ), (int) $slide['image_mobile'], 'image', 'Image mobile', '1200 × 600. Facultative : sinon l’image ordinateur.' ); ?>
			</div>
			<div class="bigm-slide__medias bigm-slide__medias--video">
				<?php bigm_champ_media( $name( 'mp4' ), (int) $slide['mp4'], 'video/mp4', 'Vidéo MP4 (H.264)' ); ?>
				<?php bigm_champ_media( $name( 'webm' ), (int) $slide['webm'], 'video/webm', 'Vidéo WebM (VP9)' ); ?>
				<?php bigm_champ_media( $name( 'poster' ), (int) $slide['poster'], 'image', 'Image d’aperçu', 'Obligatoire : affichée avant la lecture.' ); ?>
			</div>

			<div class="bigm-grid">
				<p>
					<label for="<?php echo esc_attr( $id( 'alt' ) ); ?>"><strong>Texte alternatif</strong></label>
					<input type="text" id="<?php echo esc_attr( $id( 'alt' ) ); ?>" name="<?php echo esc_attr( $name( 'alt' ) ); ?>" value="<?php echo esc_attr( (string) $slide['alt'] ); ?>" class="widefat bigm-slide__alt" placeholder="Menu Doublés : deux burgers, frites et boisson">
					<span class="description">Ce que dit la bannière, pour les lecteurs d’écran et le référencement.</span>
				</p>
				<p>
					<label for="<?php echo esc_attr( $id( 'lien' ) ); ?>"><strong>Lien</strong></label>
					<input type="url" id="<?php echo esc_attr( $id( 'lien' ) ); ?>" name="<?php echo esc_attr( $name( 'lien' ) ); ?>" value="<?php echo esc_attr( (string) $slide['lien'] ); ?>" class="widefat" placeholder="https://">
				</p>
				<p>
					<label for="<?php echo esc_attr( $id( 'debut' ) ); ?>"><strong>Début</strong></label>
					<input type="datetime-local" id="<?php echo esc_attr( $id( 'debut' ) ); ?>" name="<?php echo esc_attr( $name( 'debut' ) ); ?>" value="<?php echo esc_attr( $date( (string) $slide['debut'] ) ); ?>" class="bigm-slide__debut">
				</p>
				<p>
					<label for="<?php echo esc_attr( $id( 'fin' ) ); ?>"><strong>Fin</strong></label>
					<input type="datetime-local" id="<?php echo esc_attr( $id( 'fin' ) ); ?>" name="<?php echo esc_attr( $name( 'fin' ) ); ?>" value="<?php echo esc_attr( $date( (string) $slide['fin'] ) ); ?>" class="bigm-slide__fin">
				</p>
				<p>
					<label for="<?php echo esc_attr( $id( 'cadrage' ) ); ?>"><strong>Cadrage</strong></label>
					<input type="text" id="<?php echo esc_attr( $id( 'cadrage' ) ); ?>" name="<?php echo esc_attr( $name( 'cadrage' ) ); ?>" value="<?php echo esc_attr( (string) $slide['cadrage'] ); ?>" class="regular-text" placeholder="50% 50%">
					<span class="description">Point de l’image gardé visible quand elle est recadrée : horizontal puis vertical (ex. 50% 40%).</span>
				</p>
			</div>
			<p class="description">Dates facultatives, à l’heure du site : sans début, la slide est visible tout de suite ; sans fin, elle le reste.</p>
		</div>
	</li>
	<?php
}

add_action(
	'add_meta_boxes_bigm_slider',
	static function () {
		add_meta_box( 'bigm_slider_slides', 'Slides', 'bigm_slider_box_slides', null, 'normal', 'high' );
		add_meta_box( 'bigm_slider_usage', 'Où l’afficher', 'bigm_slider_box_usage', null, 'side' );
	}
);

/**
 * Liste des slides, avec ajout, suppression et changement d'ordre.
 *
 * @param WP_Post $post Slider.
 */
function bigm_slider_box_slides( WP_Post $post ): void {
	wp_nonce_field( 'bigm_slider_save', 'bigm_slider_nonce' );
	$slides = (array) get_post_meta( $post->ID, '_bigm_slides', true );
	?>
	<p class="description">Une photo reste affichée 6 s (5 s en format bannière) ; une vidéo passe à la slide suivante quand elle se termine. Glisser une slide pour changer l’ordre.</p>
	<ol class="bigm-slides" data-now="<?php echo esc_attr( current_time( 'Y-m-d\TH:i' ) ); ?>">
		<?php
		foreach ( array_values( array_filter( $slides, 'is_array' ) ) as $i => $slide ) {
			bigm_slide_form( (string) $i, $slide );
		}
		?>
	</ol>
	<p><button type="button" class="button button-primary bigm-slides__add">Ajouter une slide</button></p>
	<script type="text/html" id="tmpl-bigm-slide"><?php bigm_slide_form( '__KEY__', bigm_slide_vide() ); ?></script>
	<?php
}

/**
 * Où ce slider est utilisé.
 *
 * @param WP_Post $post Slider.
 */
function bigm_slider_box_usage( WP_Post $post ): void {
	$cats = get_terms(
		array(
			'taxonomy'   => 'bigm_categorie',
			'hide_empty' => false,
			'meta_key'   => '_bigm_slider', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $post->ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	echo '<p>Dans une page Elementor : widget « Big M Slider », format hero (2:1) ou bannière (3:1).</p>';
	echo '<p>Pour une catégorie : Produits → Catégories → champ « Bannières ».</p>';
	if ( ! is_wp_error( $cats ) && $cats ) {
		echo '<p><strong>Utilisé par :</strong> ' . esc_html( implode( ', ', wp_list_pluck( $cats, 'name' ) ) ) . '</p>';
	}
}

add_action(
	'save_post_bigm_slider',
	static function ( int $post_id ) {
		if ( ! isset( $_POST['bigm_slider_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bigm_slider_nonce'] ), 'bigm_slider_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$recu = wp_unslash( $_POST['bigm_slides'] ?? array() );
		$recu = is_array( $recu ) ? $recu : array();
		unset( $recu['__KEY__'] );
		$slides = array_values( array_filter( array_map( 'bigm_nettoyer_slide', $recu ) ) );
		update_post_meta( $post_id, '_bigm_slides', $slides );

		// Slides sans aucun média : non enregistrées, on le signale au prochain affichage.
		$ignorees = count( $recu ) - count( $slides );
		if ( $ignorees > 0 ) {
			set_transient( 'bigm_slides_ignorees_' . get_current_user_id(), $ignorees, MINUTE_IN_SECONDS * 5 );
		}
	}
);

add_action(
	'admin_notices',
	static function () {
		$screen = get_current_screen();
		if ( ! $screen || 'bigm_slider' !== $screen->post_type ) {
			return;
		}
		$cle      = 'bigm_slides_ignorees_' . get_current_user_id();
		$ignorees = (int) get_transient( $cle );
		if ( ! $ignorees ) {
			return;
		}
		delete_transient( $cle );
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html(
				1 === $ignorees
					? '1 slide n’a pas été enregistrée : elle n’avait ni image ni vidéo.'
					: $ignorees . ' slides n’ont pas été enregistrées : elles n’avaient ni image ni vidéo.'
			)
		);
	}
);

/*
 * Colonne de la liste : nombre de slides, dont visibles maintenant.
 */
add_filter(
	'manage_bigm_slider_posts_columns',
	static function ( array $cols ): array {
		$date = $cols['date'] ?? null;
		unset( $cols['date'] );
		$cols['bigm_slides'] = 'Slides';
		if ( $date ) {
			$cols['date'] = $date;
		}
		return $cols;
	}
);

add_action(
	'manage_bigm_slider_posts_custom_column',
	static function ( string $col, int $post_id ) {
		if ( 'bigm_slides' !== $col ) {
			return;
		}
		$total   = count( array_filter( (array) get_post_meta( $post_id, '_bigm_slides', true ), 'is_array' ) );
		$visible = count( bigm_slides_actives( $post_id ) );
		echo esc_html( sprintf( '%d (%d visible%s maintenant)', $total, $visible, $visible > 1 ? 's' : '' ) );
	},
	10,
	2
);
