<?php
/**
 * Plugin Name:  Big M — socle
 * Description:  Préprod fermée aux moteurs de recherche, XML-RPC et éditeur de fichiers coupés, en-têtes de sécurité, comptes non énumérables, réglages de départ appliqués une fois. Fonctionne en mu-plugin (wp-content/mu-plugins/) ou en extension classique (wp-content/plugins/bigm-socle/).
 * Author:       The Follow Movement
 * Version:      0.3.0
 * Requires PHP: 8.2
 *
 * @package BigM\Socle
 */

defined( 'ABSPATH' ) || exit;

// Une seule fois, même si le fichier est présent en mu-plugin et en extension.
if ( defined( 'BIGM_SOCLE_VERSION' ) ) {
	return;
}
define( 'BIGM_SOCLE_VERSION', '0.3.0' );

// Pas d'éditeur de thèmes ni d'extensions dans l'admin.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

if ( ! function_exists( 'bigm_socle_environment' ) ) {
	/**
	 * Environnement du site :
	 * 1. WP_ENVIRONMENT_TYPE s'il est défini (wp-config.php ou variable d'environnement) ;
	 * 2. sinon d'après l'adresse : preprod.*, staging.* et *.hostingersite.com sont des préprods ;
	 * 3. sinon production.
	 */
	function bigm_socle_environment(): string {
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) || false !== getenv( 'WP_ENVIRONMENT_TYPE' ) ) {
			return wp_get_environment_type();
		}
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		return preg_match( '/^(preprod|staging)\.|\.hostingersite\.com$/i', $host ) ? 'staging' : 'production';
	}
}

/*
 * Hors production, le site n'est jamais indexé, même si quelqu'un décoche
 * « Demander aux moteurs de recherche de ne pas indexer ce site ».
 */
if ( 'production' !== bigm_socle_environment() ) {
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
						. esc_html( strtoupper( bigm_socle_environment() ) ) . ' · non indexé</span>',
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
 * Le .htaccess bloque aussi xmlrpc.php avant WordPress (voir plus bas).
 */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	exit( 'XML-RPC est désactivé.' );
}
remove_action( 'wp_head', 'rsd_link' );

/*
 * En-têtes de sécurité des pages (brief, § 12) :
 * - pas de devinette du type de fichier par le navigateur ;
 * - adresse d'origine réduite au domaine quand on quitte le site ;
 * - site affichable dans un cadre seulement par lui-même (aperçu d'Elementor) ;
 * - caméra, micro, paiement et USB coupés ; géolocalisation réservée au site
 *   (bouton « Me localiser » des restaurants et des promos).
 * Filtre « bigm_socle_headers » pour en changer.
 */
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );
		$securite = array(
			'X-Content-Type-Options' => 'nosniff',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Permissions-Policy'     => 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)',
		);
		return array_merge( $headers, (array) apply_filters( 'bigm_socle_headers', $securite ) );
	}
);

/*
 * Identifiants des comptes non devinables par les visiteurs :
 * - pas de liste des utilisateurs dans l'API REST ;
 * - pas de pages auteur (/?author=1 renverrait vers /author/identifiant/) ;
 * - pas de nom d'auteur dans les aperçus oEmbed.
 * Les personnes connectées (éditeur, Elementor) ne sont pas concernées.
 */
add_filter(
	'rest_endpoints',
	static function ( array $routes ): array {
		if ( is_user_logged_in() ) {
			return $routes;
		}
		foreach ( array_keys( $routes ) as $route ) {
			if ( str_starts_with( $route, '/wp/v2/users' ) ) {
				unset( $routes[ $route ] );
			}
		}
		return $routes;
	}
);
add_action(
	'template_redirect',
	static function () {
		if ( ! is_user_logged_in() && ( is_author() || isset( $_GET['author'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_safe_redirect( home_url( '/' ), 301, 'Big M' );
			exit;
		}
	},
	0
);
add_filter(
	'oembed_response_data',
	static function ( array $data ): array {
		unset( $data['author_name'], $data['author_url'] );
		return $data;
	}
);

if ( ! function_exists( 'bigm_socle_htaccess_rules' ) ) {
	/**
	 * Règles Big M du .htaccess, par bloc.
	 *
	 * @param string $environment Environnement du site.
	 * @return array<string, string[]>
	 */
	function bigm_socle_htaccess_rules( string $environment ): array {
		return array(
			'Big M'         => array(
				'# XML-RPC bloqué avant même de charger WordPress.',
				'<Files xmlrpc.php>',
				'  Order allow,deny',
				'  Deny from all',
				'</Files>',
				'# Aucun fichier PHP exécuté avant WordPress. Coupe notamment le pare-feu',
				'# Wordfence que le .htaccess de l\'ancien site impose aux dossiers placés dessous.',
				'<IfModule LiteSpeed>',
				'  php_value auto_prepend_file none',
				'</IfModule>',
				'<IfModule lsapi_module>',
				'  php_value auto_prepend_file none',
				'</IfModule>',
			),
			// Vide en production : le bloc disparaît de lui-même à la bascule.
			'Big M preprod' => 'production' === $environment ? array() : array(
				'# Préprod : images et PDF non indexés non plus.',
				'<IfModule mod_headers.c>',
				'  Header set X-Robots-Tag "noindex, nofollow"',
				'</IfModule>',
			),
		);
	}
}

if ( ! function_exists( 'bigm_socle_first_setup' ) ) {
	/**
	 * Réglages de départ d'une installation neuve. Une seule fois : ensuite,
	 * ils se changent normalement dans l'admin.
	 *
	 * @return string[] Ce qui a été fait.
	 */
	function bigm_socle_first_setup(): array {
		$done = array();

		update_option( 'timezone_string', 'Europe/Paris' );
		update_option( 'date_format', 'j F Y' );
		update_option( 'time_format', 'H:i' );
		update_option( 'start_of_week', 1 );
		$done[] = 'fuseau Europe/Paris, dates en français';

		update_option( 'default_comment_status', 'closed' );
		update_option( 'default_ping_status', 'closed' );
		update_option( 'default_pingback_flag', 0 );
		$done[] = 'commentaires et pings fermés par défaut';

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$done[] = 'permaliens /%postname%/';

		// Article et page d'exemple de WordPress, seulement s'ils n'ont jamais été modifiés.
		$samples = array(
			'post' => array( 'hello-world', 'bonjour-tout-le-monde' ),
			'page' => array( 'sample-page', 'page-d-exemple' ),
		);
		foreach ( $samples as $type => $slugs ) {
			foreach ( $slugs as $slug ) {
				$post = get_page_by_path( $slug, OBJECT, $type );
				if ( $post && $post->post_modified_gmt === $post->post_date_gmt ) {
					wp_delete_post( $post->ID, true );
					$done[] = "contenu d'exemple supprimé : $slug";
				}
			}
		}

		return $done;
	}
}

/*
 * Au premier passage d'un administrateur : réglages de départ (une fois),
 * puis .htaccess, réécrit quand la version ou l'environnement change.
 */
add_action(
	'admin_init',
	static function () {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$report = array();

		if ( ! get_option( 'bigm_socle_first_setup' ) ) {
			$report = bigm_socle_first_setup();
			update_option( 'bigm_socle_first_setup', BIGM_SOCLE_VERSION, false );
		}

		$environment = bigm_socle_environment();
		$state       = BIGM_SOCLE_VERSION . '|' . $environment;
		if ( get_option( 'bigm_socle_htaccess' ) !== $state || $report ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			// N'écrire que dans le dossier qui sert le site, jamais ailleurs.
			$dir     = get_home_path();
			$written = is_file( $dir . 'index.php' ) && is_file( $dir . 'wp-blog-header.php' );
			if ( $written ) {
				flush_rewrite_rules( true );
				foreach ( bigm_socle_htaccess_rules( $environment ) as $marker => $lines ) {
					$written = insert_with_markers( $dir . '.htaccess', $marker, $lines ) && $written;
				}
			}
			if ( $written ) {
				update_option( 'bigm_socle_htaccess', $state, false );
				$report[] = '.htaccess : XML-RPC bloqué, pare-feu de l\'ancien site coupé' . ( 'production' === $environment ? '' : ', fichiers non indexés' );
			} else {
				$report[] = '.htaccess non modifiable : ajouter les blocs à la main (docs/preprod-hostinger.md)';
			}
		}

		if ( $report ) {
			set_transient( 'bigm_socle_report', $report, HOUR_IN_SECONDS );
		}
	}
);

// Compte rendu affiché une fois dans l'admin.
add_action(
	'admin_notices',
	static function () {
		$report = get_transient( 'bigm_socle_report' );
		if ( ! $report || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		delete_transient( 'bigm_socle_report' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Big M — socle</strong></p><ul style="list-style:disc;padding-left:20px">';
		foreach ( (array) $report as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
);
