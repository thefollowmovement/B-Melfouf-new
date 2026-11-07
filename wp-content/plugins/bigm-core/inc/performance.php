<?php
/**
 * Performance (brief, § 11) : images WebP, fichiers inutiles retirés, cache vidé aux bons moments.
 *
 * - Les tailles d'images générées par WordPress (vignettes, srcset) sont en WebP quand le
 *   serveur sait en produire ; l'original téléversé reste tel quel.
 * - Les emojis de WordPress et le CSS des blocs ne sont pas chargés là où ils ne servent pas
 *   (pages Elementor, carte, produits, restaurants).
 * - Le cache de pages (LiteSpeed Cache sur Hostinger) est vidé :
 *   aux dates de début et de fin des slides programmées, et quand on enregistre un slider ou
 *   les Réglages Big M (en-tête, pied de page, liens) : ces contenus apparaissent sur des
 *   pages que le cache ne sait pas relier à eux.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/*
 * Tailles d'images en WebP (JPEG et PNG, transparence comprise).
 */
add_filter(
	'image_editor_output_format',
	static function ( array $formats ): array {
		static $verification = null;
		if ( null === $verification ) {
			// wp_image_editor_supports() relit ce filtre : on bloque la boucle le temps du test.
			$verification = false;
			$verification = wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		}
		if ( $verification && apply_filters( 'bigm_images_webp', true ) ) {
			$formats['image/jpeg'] = 'image/webp';
			$formats['image/png']  = 'image/webp';
		}
		return $formats;
	}
);

/*
 * Emojis : les navigateurs les affichent seuls ; le script et le style de WordPress sont inutiles.
 */
add_action(
	'init',
	static function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}
);

/*
 * CSS des blocs de l'éditeur : seulement sur les contenus écrits avec des blocs.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$objet = get_queried_object();
		if ( $objet instanceof WP_Post && has_blocks( $objet ) ) {
			return;
		}
		if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() ) {
			return; // Articles (ceux repris de l'ancien site portent des classes de blocs) et leurs listes.
		}
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	},
	100
);

/**
 * Vide le cache de pages : LiteSpeed Cache, et toute extension qui écoute « bigm_vider_cache ».
 */
function bigm_vider_cache(): void {
	do_action( 'litespeed_purge_all', 'Big M' );
	do_action( 'bigm_vider_cache' );
}

/**
 * Programme le vidage du cache aux dates de début et de fin des slides d'un slider.
 *
 * @param int $slider_id Slider.
 */
function bigm_programmer_vidages( int $slider_id ): void {
	foreach ( _get_cron_array() ? _get_cron_array() : array() as $quand => $taches ) {
		foreach ( $taches['bigm_slides_changement'] ?? array() as $tache ) {
			if ( (int) ( $tache['args'][0] ?? 0 ) === $slider_id ) {
				wp_unschedule_event( $quand, 'bigm_slides_changement', $tache['args'] );
			}
		}
	}
	if ( 'publish' !== get_post_status( $slider_id ) ) {
		return;
	}
	$maintenant = time();
	$dates      = array();
	foreach ( (array) get_post_meta( $slider_id, '_bigm_slides', true ) as $slide ) {
		foreach ( array( 'debut', 'fin' ) as $cle ) {
			$texte = is_array( $slide ) ? (string) ( $slide[ $cle ] ?? '' ) : '';
			$date  = '' !== $texte ? DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $texte, wp_timezone() ) : false;
			if ( $date && $date->getTimestamp() > $maintenant ) {
				$dates[ $date->getTimestamp() ] = true;
			}
		}
	}
	foreach ( array_keys( $dates ) as $ts ) {
		// Une minute après le changement, pour que la page régénérée le montre.
		wp_schedule_single_event( $ts + 60, 'bigm_slides_changement', array( $slider_id, $ts ) );
	}
}

add_action( 'bigm_slides_changement', 'bigm_vider_cache' );

add_action(
	'save_post_bigm_slider',
	static function ( int $post_id ) {
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		bigm_programmer_vidages( $post_id );
		bigm_vider_cache();
	},
	20
);

add_action( 'update_option_bigm_reglages', 'bigm_vider_cache' );
