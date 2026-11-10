<?php
/**
 * Champs des catégories de la carte : titre de page, ordre, icône, vignette, bannières.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sliders disponibles pour les bannières d'une catégorie.
 *
 * @return WP_Post[]
 */
function bigm_sliders_liste(): array {
	return get_posts(
		array(
			'post_type'      => 'bigm_slider',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
}

/**
 * Champs du formulaire de catégorie, en mode « ajout » (div) ou « modification » (lignes de tableau).
 *
 * @param WP_Term|null $term Catégorie, ou null pour l'ajout.
 */
function bigm_categorie_champs( ?WP_Term $term ): void {
	$id     = $term ? $term->term_id : 0;
	$titre  = $id ? (string) get_term_meta( $id, '_bigm_titre', true ) : '';
	$ordre  = $id ? (string) get_term_meta( $id, '_bigm_ordre', true ) : '';
	$icone  = $id ? (string) get_term_meta( $id, '_bigm_icone', true ) : '';
	$image  = $id ? bigm_nettoyer_media( get_term_meta( $id, '_bigm_image', true ) ) : 0;
	$video  = bigm_nettoyer_video( $id ? get_term_meta( $id, '_bigm_video', true ) : array() );
	$slider = $id ? (int) get_term_meta( $id, '_bigm_slider', true ) : 0;

	$sliders = bigm_sliders_liste();
	$rows    = array(
		'titre'  => array(
			'Titre de la page',
			'<input type="text" name="bigm_cat[titre]" id="bigm_cat_titre" value="' . esc_attr( $titre ) . '" class="regular-text" placeholder="Nos burgers">',
			'Grand titre de la page de la catégorie. Vide : le nom de la catégorie.',
		),
		'ordre'  => array(
			'Ordre d’affichage',
			'<input type="number" name="bigm_cat[ordre]" id="bigm_cat_ordre" value="' . esc_attr( $ordre ) . '" class="small-text" min="0" step="1">',
			'Position dans la barre de catégories et la grille de la carte (1, 2, 3…).',
		),
		'icone'  => array(
			'Icône (SVG)',
			'<textarea name="bigm_cat[icone]" id="bigm_cat_icone" rows="4" class="large-text code">' . esc_textarea( $icone ) . '</textarea>'
				. ( $icone ? '<span class="bigm-icone-apercu">' . bigm_nettoyer_svg( $icone ) . '</span>' : '' ),
			'Coller un SVG complet, ou seulement son tracé (attribut d) sur une grille de 24 × 24. L’icône prend la couleur du texte.',
		),
	);
	$nonce = wp_nonce_field( 'bigm_categorie_save', 'bigm_categorie_nonce', true, false );

	ob_start();
	bigm_champ_media( 'bigm_cat[image]', $image, 'image', 'Image' );
	bigm_champ_media( 'bigm_cat[video][mp4]', $video['mp4'], 'video/mp4', 'Vidéo MP4' );
	bigm_champ_media( 'bigm_cat[video][webm]', $video['webm'], 'video/webm', 'Vidéo WebM' );
	bigm_champ_media( 'bigm_cat[video][poster]', $video['poster'], 'image', 'Image d’aperçu' );
	$rows['vignette'] = array( 'Vignette', ob_get_clean(), 'Tuile de la grille de la carte. La vidéo, facultative, remplace l’image.' );

	$options = '<option value="0">— Aucune bannière —</option>';
	foreach ( $sliders as $s ) {
		$options .= '<option value="' . (int) $s->ID . '"' . selected( $slider, $s->ID, false ) . '>' . esc_html( get_the_title( $s ) ) . '</option>';
	}
	$rows['slider'] = array(
		'Bannières',
		'<select name="bigm_cat[slider]" id="bigm_cat_slider">' . $options . '</select>',
		$sliders ? 'Slider affiché en haut de la page de la catégorie (menu Sliders).' : 'Créer d’abord un slider dans le menu Sliders.',
	);

	foreach ( $rows as $key => $row ) {
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row"><label for="bigm_cat_' . esc_attr( $key ) . '">' . esc_html( $row[0] ) . '</label></th><td>'
				. $row[1] . '<p class="description">' . esc_html( $row[2] ) . '</p></td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- champs construits et échappés ci-dessus.
		} else {
			echo '<div class="form-field"><label for="bigm_cat_' . esc_attr( $key ) . '">' . esc_html( $row[0] ) . '</label>'
				. $row[1] . '<p>' . esc_html( $row[2] ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo $nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'bigm_categorie_add_form_fields', static fn() => bigm_categorie_champs( null ) );
add_action( 'bigm_categorie_edit_form_fields', static fn( WP_Term $term ) => bigm_categorie_champs( $term ) );

/**
 * Enregistre les champs d'une catégorie.
 *
 * @param int $term_id Catégorie.
 */
function bigm_categorie_save( int $term_id ): void {
	if ( ! isset( $_POST['bigm_categorie_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bigm_categorie_nonce'] ), 'bigm_categorie_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = wp_unslash( $_POST['bigm_cat'] ?? array() );
	$in = is_array( $in ) ? $in : array();

	update_term_meta( $term_id, '_bigm_titre', sanitize_text_field( $in['titre'] ?? '' ) );
	$ordre = trim( (string) ( $in['ordre'] ?? '' ) );
	update_term_meta( $term_id, '_bigm_ordre', '' === $ordre ? '' : absint( $ordre ) );
	update_term_meta( $term_id, '_bigm_icone', bigm_nettoyer_svg( $in['icone'] ?? '' ) );
	update_term_meta( $term_id, '_bigm_image', bigm_nettoyer_media( $in['image'] ?? 0, 'image/' ) );
	update_term_meta( $term_id, '_bigm_video', bigm_nettoyer_video( $in['video'] ?? array() ) );
	$slider = absint( $in['slider'] ?? 0 );
	update_term_meta( $term_id, '_bigm_slider', 'bigm_slider' === get_post_type( $slider ) ? $slider : 0 );
}
add_action( 'created_bigm_categorie', 'bigm_categorie_save' );
add_action( 'edited_bigm_categorie', 'bigm_categorie_save' );

/*
 * Colonnes de la liste des catégories : icône et ordre.
 */
add_filter(
	'manage_edit-bigm_categorie_columns',
	static function ( array $cols ): array {
		$new = array();
		foreach ( $cols as $key => $label ) {
			if ( 'name' === $key ) {
				$new['bigm_icone'] = '<span class="screen-reader-text">Icône</span>';
			}
			$new[ $key ] = $label;
		}
		$new['bigm_ordre'] = 'Ordre';
		return $new;
	}
);

add_filter(
	'manage_bigm_categorie_custom_column',
	static function ( string $out, string $col, int $term_id ): string {
		if ( 'bigm_icone' === $col ) {
			return '<span class="bigm-icone-apercu">' . bigm_nettoyer_svg( (string) get_term_meta( $term_id, '_bigm_icone', true ) ) . '</span>';
		}
		if ( 'bigm_ordre' === $col ) {
			return esc_html( (string) get_term_meta( $term_id, '_bigm_ordre', true ) );
		}
		return $out;
	},
	10,
	3
);
