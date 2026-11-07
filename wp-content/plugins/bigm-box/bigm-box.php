<?php
/**
 * Plugin Name:       Big M — Nos Box
 * Description:       Widget Elementor « Big M — Nos Box » : les Box en cartes (photo et titre) ; la Box choisie montre son contenu en grand. Les Box s'ajoutent dans le widget.
 * Author:            The Follow Movement
 * Version:           1.0.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  elementor
 * License:           GPL-2.0-or-later
 * Text Domain:       bigm-box
 *
 * @package BigM\Box
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_BOX_VERSION', '1.0.1' );
define( 'BIGM_BOX_DIR', __DIR__ );
define( 'BIGM_BOX_URL', plugin_dir_url( __FILE__ ) );

/*
 * Style et script du widget, déclarés sans être chargés : Elementor ne les charge
 * que sur les pages qui utilisent le widget (get_style_depends / get_script_depends).
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_register_style( 'bigm-box', BIGM_BOX_URL . 'assets/box.css', array(), BIGM_BOX_VERSION );
		wp_register_script(
			'bigm-box',
			BIGM_BOX_URL . 'assets/box.js',
			array(),
			BIGM_BOX_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	},
	5
);

/*
 * Catégorie « Big M » du panneau d'Elementor : déjà créée par l'extension Big M — cœur,
 * créée ici si elle est désactivée.
 */
add_action(
	'elementor/elements/categories_registered',
	static function ( $manager ) {
		if ( ! array_key_exists( 'bigm', $manager->get_categories() ) ) {
			$manager->add_category(
				'bigm',
				array(
					'title' => 'Big M',
					'icon'  => 'eicon-star',
				)
			);
		}
	},
	20
);

add_action(
	'elementor/widgets/register',
	static function ( $widgets ) {
		require_once BIGM_BOX_DIR . '/inc/class-bigm-box-widget.php';
		$widgets->register( new Bigm_Box_Widget() );
	}
);
