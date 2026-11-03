<?php
/**
 * Feuilles de style et scripts communs à toutes les pages.
 *
 * Les composants (en-tête, pied de page, slider, vignettes…) auront chacun leur
 * fichier, mis en file d'attente seulement là où ils servent.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Numéro de version d'un fichier du thème : sa date de modification,
 * pour que le cache des navigateurs se vide à chaque déploiement.
 *
 * @param string $relative Chemin depuis la racine du thème.
 */
function bigm_child_asset_version( string $relative ): string {
	$path = BIGM_CHILD_DIR . '/' . ltrim( $relative, '/' );
	return is_file( $path ) ? (string) filemtime( $path ) : BIGM_CHILD_VERSION;
}

/**
 * URL d'un fichier du thème.
 *
 * @param string $relative Chemin depuis la racine du thème.
 */
function bigm_child_asset_url( string $relative ): string {
	return BIGM_CHILD_URI . '/' . ltrim( $relative, '/' );
}

/*
 * Priorité 20 : après Hello Elementor, pour que le reset du prototype
 * passe après theme.css.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style(
			'bigm-tokens',
			bigm_child_asset_url( 'assets/css/tokens.css' ),
			array(),
			bigm_child_asset_version( 'assets/css/tokens.css' )
		);

		wp_enqueue_style(
			'bigm-base',
			bigm_child_asset_url( 'assets/css/base.css' ),
			array( 'bigm-tokens' ),
			bigm_child_asset_version( 'assets/css/base.css' )
		);

		wp_enqueue_script(
			'bigm-base',
			bigm_child_asset_url( 'assets/js/base.js' ),
			array(),
			bigm_child_asset_version( 'assets/js/base.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	},
	20
);
