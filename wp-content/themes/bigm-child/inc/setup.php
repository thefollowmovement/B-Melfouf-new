<?php
/**
 * Réglages du thème : ce que l'on change chez Hello Elementor et dans le <head>.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/*
 * Le reset de Hello Elementor (reset.css) colore liens et boutons en rose (#c36)
 * et impose ses propres contours de focus. On le remplace par le reset du
 * prototype (assets/css/base.css). theme.css est conservé : il porte les
 * utilitaires WordPress (lien d'évitement, alignements, légendes).
 */
add_filter( 'hello_elementor_enqueue_style', '__return_false' );

/*
 * Viewport : le zoom reste autorisé (l'ancien site le bloquait avec maximum-scale=1).
 * viewport-fit=cover permet à la barre d'onglets mobile de tenir compte de l'encoche.
 */
add_filter(
	'hello_elementor_viewport_content',
	static fn() => 'width=device-width, initial-scale=1, viewport-fit=cover'
);

/*
 * Classe « js » sur <html> dès le <head>, comme dans le prototype :
 * les apparitions au défilement ([data-reveal]) en dépendent.
 */
add_action(
	'wp_head',
	static function () {
		wp_print_inline_script_tag( "document.documentElement.classList.add('js')" );
	},
	1
);

/*
 * Préchargement de Fredoka 600, la police des titres.
 */
add_filter(
	'wp_preload_resources',
	static function ( array $resources ): array {
		$resources[] = array(
			'href'        => BIGM_CHILD_URI . '/assets/fonts/fredoka-latin-600-normal.woff2',
			'as'          => 'font',
			'type'        => 'font/woff2',
			'crossorigin' => 'anonymous',
		);
		return $resources;
	}
);

/*
 * Emplacements de menus de Hello (« Header », « Footer ») retirés : l'en-tête et
 * le pied de page Big M utilisent les leurs (inc/navigation.php).
 */
add_filter( 'hello_elementor_register_menus', '__return_false' );
