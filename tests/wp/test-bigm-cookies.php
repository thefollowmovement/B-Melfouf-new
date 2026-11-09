<?php
/**
 * Test de l'extension bigm-cookies (bandeau de consentement). Sur un site de test avec
 * l'extension active (et le thème Big M pour le lien du pied de page) :
 *
 *   wp eval-file tests/wp/test-bigm-cookies.php --user=<administrateur>
 *
 * Le parcours dans le navigateur (rien avant l'accord, refus, accord, retrait) se vérifie avec
 * Playwright : voir docs/cookies.md.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_cookies_html' ) ) {
	WP_CLI::error( 'Il faut l’extension bigm-cookies active.' );
}

$echecs = 0;
$ok     = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};

WP_CLI::log( 'Bandeau' );
$html = bigm_cookies_html();
$ok( str_contains( $html, 'role="dialog"' ) && str_contains( $html, 'aria-labelledby="bigm-cookies-titre"' ) && str_contains( $html, ' hidden>' ), 'dialogue nommé, caché tant que le script n’a pas lu le choix (sans JavaScript, rien n’est accepté)' );
preg_match( '#<button[^>]*data-bigm-cookies-action="refuser"[^>]*>#', $html, $refus );
preg_match( '#<button[^>]*data-bigm-cookies-action="accepter"[^>]*>#', $html, $accord );
$classe = static fn( string $b ): string => (string) preg_replace( '/.*class="([^"]*)".*/', '$1', $b );
$ok( $refus && $accord && $classe( $refus[0] ) === $classe( $accord[0] ) && 'bigm-cookies__btn' === $classe( $refus[0] ), '« Tout refuser » et « Tout accepter » : même bouton, même aspect (CNIL)' );
$ok( str_contains( $html, 'data-bigm-cookies-cat="mesure"' ) && str_contains( $html, 'data-bigm-cookies-cat="contenus"' ) && ! preg_match( '/data-bigm-cookies-cat="[a-z]+"[^>]*checked/', $html ), 'une case par finalité, aucune cochée d’avance' );
$ok( str_contains( $html, 'Toujours actifs' ) && ! str_contains( $html, 'data-bigm-cookies-cat="necessaires"' ), 'cookies nécessaires : signalés, sans case' );
$ok( str_contains( $html, '_ga, _ga_*' ) && str_contains( $html, 'Google Ireland' ), 'liste des cookies : Google Analytics, son fournisseur' );
$filtre_off = static fn( $v ): array => array_merge( (array) $v, array( 'contenus' => '0' ) );
add_filter( 'bigm_cookies_reglages', $filtre_off );
$ok( ! str_contains( bigm_cookies_html(), 'data-bigm-cookies-cat="contenus"' ), 'finalité décochée dans Réglages → Cookies : plus proposée' );
remove_filter( 'bigm_cookies_reglages', $filtre_off );

WP_CLI::log( 'Scripts mis en attente' );
$tag_ga = apply_filters( 'script_loader_tag', "<script id=\"google_gtagjs-js-before\">var a=1;</script>\n<script src=\"https://www.googletagmanager.com/gtag/js?id=G-TEST\" id=\"google_gtagjs-js\" async></script>\n<script id=\"google_gtagjs-js-after\">gtag(\"config\",\"G-TEST\");</script>", 'google_gtagjs', 'https://www.googletagmanager.com/gtag/js?id=G-TEST' );
$ok( 3 === substr_count( $tag_ga, '<script type="text/plain" data-bigm-cookies="mesure"' ) && str_contains( $tag_ga, 'src="https://www.googletagmanager.com/gtag/js?id=G-TEST"' ), 'Google Analytics de Site Kit (script et scripts en ligne) : en attente, adresse gardée' );
$tag_ev = apply_filters( 'script_loader_tag', '<script src="https://exemple.test/e.js" id="googlesitekit-events-provider-wpforms-js" type="text/javascript"></script>', 'googlesitekit-events-provider-wpforms', 'https://exemple.test/e.js' );
$ok( str_starts_with( $tag_ev, '<script type="text/plain" data-bigm-cookies="mesure"' ) && 1 === substr_count( $tag_ev, 'type=' ), 'suivi d’événements de Site Kit : en attente (ancien type retiré)' );
$autre = '<script src="https://exemple.test/a.js" id="bigm-commander-js"></script>';
$ok( $autre === apply_filters( 'script_loader_tag', $autre, 'bigm-commander', 'https://exemple.test/a.js' ), 'autres scripts : intacts' );
$attr = apply_filters( 'wp_inline_script_attributes', array( 'id' => 'google_gtagjs-js-after' ), 'gtag("config")' );
$ok( 'text/plain' === ( $attr['type'] ?? '' ) && 'mesure' === ( $attr['data-bigm-cookies'] ?? '' ), 'script en ligne isolé de Google Analytics : en attente' );
ob_start();
do_action( 'wp_head' );
$tete = (string) ob_get_clean();
$ok( (bool) preg_match( '/gtag\("consent","default",\{[^}]*"analytics_storage":"denied"/', $tete ) && strpos( $tete, 'bigm-cookies-consentement' ) < ( strpos( $tete, 'googletagmanager' ) ?: PHP_INT_MAX ), 'mode de consentement de Google : refusé par défaut, avant tout script Google' );
$ok( str_contains( $tete, '"cookie_expires":34128000' ) && str_contains( $tete, '"cookie_update":false' ), 'cookies _ga : 13 mois au plus, sans prolongation à chaque visite (CNIL)' );

WP_CLI::log( 'Contenus d’autres sites' );
$video  = '<iframe src="https://www.youtube.com/embed/abc" title="Vidéo"></iframe>';
$encart = apply_filters( 'embed_oembed_html', $video, 'https://www.youtube.com/watch?v=abc', array(), 0 );
$ok( str_contains( $encart, 'data-bigm-cookies-contenu="contenus"' ) && ! str_contains( $encart, '<iframe' ) && preg_match( '/data-contenu="([^"]+)"/', $encart, $m ) && str_contains( base64_decode( $m[1] ), '<iframe' ) && str_contains( base64_decode( $m[1] ), '/embed/abc' ) && str_contains( $encart, 'hébergé par YouTube' ), 'vidéo YouTube : encart « Afficher le contenu », code d’origine en attente' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
$carte_osm = '<iframe src="https://www.openstreetmap.org/export/embed.html"></iframe>';
$ok( $carte_osm === apply_filters( 'embed_oembed_html', $carte_osm, 'https://www.openstreetmap.org/', array(), 0 ), 'contenu sans cookie connu (OpenStreetMap) : intact' );
$colle = apply_filters( 'the_content', '<p>Avant</p><iframe src="https://player.vimeo.com/video/1"></iframe>' );
$ok( str_contains( $colle, 'hébergé par Vimeo' ) && ! str_contains( $colle, '<iframe' ), 'cadre Vimeo collé dans un contenu : en attente' );

WP_CLI::log( 'Pied de page et réglages' );
if ( function_exists( 'bigm_child_liens' ) ) {
	$legal = bigm_child_liens( 'bigm-pied-legal' );
	$ok( 1 === count( array_filter( $legal, static fn( $l ): bool => str_ends_with( (string) $l['url'], '#gestion-cookies' ) ) ), 'lien « Paramétrer les cookies » dans les liens légaux du pied de page, une fois' );
}
$n = bigm_cookies_nettoyer(
	array(
		'titre'      => ' <b>Cookies</b> ',
		'duree'      => '900',
		'mesure'     => '1',
		'redemander' => '1',
	)
);
$ok( 'Cookies' === $n['titre'] && 395 === $n['duree'] && '1' === $n['mesure'] && '0' === $n['contenus'] && $n['version'] !== bigm_cookies_reglages()['version'], 'réglages nettoyés : durée 13 mois au plus, case absente = décochée, « redemander » change la version' );
$vidages = 0;
$espion  = static function () use ( &$vidages ) {
	++$vidages;
};
add_action( 'litespeed_purge_all', $espion );
do_action( 'update_option_bigm_cookies', array(), bigm_cookies_reglages(), 'bigm_cookies' );
remove_action( 'litespeed_purge_all', $espion );
$ok( 1 === $vidages, 'réglages enregistrés : cache des pages vidé (le bandeau est dans le HTML)' );
$ok( 182 === bigm_cookies_defauts()['duree'], 'choix gardé 6 mois par défaut (recommandation de la CNIL)' );
$ok( str_contains( do_shortcode( '[bigm_cookies_gestion]' ), 'data-bigm-cookies-ouvrir' ) && str_contains( do_shortcode( '[bigm_cookies_liste]' ), '<table class="bigm-cookies__table">' ), 'codes courts : bouton pour rouvrir le bandeau, tableau des cookies' );

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'bigm-cookies : toutes les vérifications passent.' );
