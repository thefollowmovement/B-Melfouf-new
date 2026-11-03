<?php
/**
 * Plugin Name: Big M — socle
 * Description: Ferme la préprod aux moteurs de recherche et coupe XML-RPC. Extension obligatoire (mu-plugin), indépendante du thème.
 * Author:      The Follow Movement
 * Version:     0.1.0
 * Requires PHP: 8.2
 *
 * @package BigM\Socle
 */

defined( 'ABSPATH' ) || exit;

/*
 * Hors production, le site n'est jamais indexé, même si la case
 * « Demander aux moteurs de recherche de ne pas indexer ce site » est décochée.
 * L'environnement se règle dans wp-config.php :
 *   define( 'WP_ENVIRONMENT_TYPE', 'staging' );     // préprod
 *   define( 'WP_ENVIRONMENT_TYPE', 'production' );  // site en ligne
 */
if ( 'production' !== wp_get_environment_type() ) {
	add_filter( 'pre_option_blog_public', static fn() => '0' );
	add_filter( 'wp_robots', 'wp_robots_no_robots' );
	add_action(
		'send_headers',
		static function () {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}
	);

	// Repère dans la barre d'admin, pour ne pas confondre préprod et site en ligne.
	add_action(
		'admin_bar_menu',
		static function ( WP_Admin_Bar $bar ) {
			$bar->add_node(
				array(
					'id'     => 'bigm-env',
					'parent' => 'top-secondary',
					'title'  => '<span style="background:#c8400a;color:#fff;padding:0 8px;border-radius:4px;font-weight:600">'
						. esc_html( strtoupper( wp_get_environment_type() ) ) . ' · non indexé</span>',
				)
			);
		},
		0
	);
}

/*
 * XML-RPC coupé : il ne sert à rien ici et c'est une cible d'attaques
 * (force brute via system.multicall, pingbacks). Le filtre xmlrpc_enabled
 * laisse passer les méthodes system.*, donc on répond 403 à toute requête.
 * Le .htaccess bloque aussi xmlrpc.php avant WordPress (docs/preprod-hostinger.md).
 */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( 'XML-RPC est désactivé.' );
}
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
remove_action( 'wp_head', 'rsd_link' );
