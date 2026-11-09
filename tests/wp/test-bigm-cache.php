<?php
/**
 * Test du réglage de LiteSpeed Cache (bigm-core, inc/cache.php). Sur un site de test avec
 * bigm-core et StoreLocatore actifs (LiteSpeed Cache facultatif) :
 *
 *   wp eval-file tests/wp/test-bigm-cache.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_horaires_prochain_changement' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.9.1 ou plus.' );
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
$t = static fn( string $quand ): int => (int) strtotime( $quand . ' UTC' );

WP_CLI::log( 'Prochain changement d’horaire' );
$jour    = static fn( string $o, string $c, string $bs = '', string $be = '' ): array => array(
	'closed'      => '0',
	'open'        => $o,
	'close'       => $c,
	'break_start' => $bs,
	'break_end'   => $be,
);
$heures  = array(
	'monday'    => array( 'closed' => '1' ),
	'tuesday'   => $jour( '11:15', '23:00', '14:00', '18:30' ),
	'wednesday' => $jour( '11:15', '23:00', '14:00', '18:30' ),
	'thursday'  => $jour( '11:00', '23:00' ),
	'friday'    => $jour( '11:00', '01:00' ),
	'saturday'  => $jour( '11:00', '01:00' ),
	'sunday'    => array( 'closed' => '1' ),
);
// Le 10 novembre 2026 est un mardi.
$ok( $t( '2026-11-10 14:00' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-10 12:00' ) ), 'ouvert à midi : prochain changement au début de la pause (14:00)' );
$ok( $t( '2026-11-10 18:30' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-10 15:00' ) ), 'pendant la pause : réouverture (18:30)' );
$ok( $t( '2026-11-11 11:15' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-10 23:30' ) ), 'fermé le soir : ouverture du lendemain' );
$ok( $t( '2026-11-14 01:00' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-13 23:00' ) ), 'fermeture après minuit (vendredi 01:00) : comptée le samedi' );
$ok( $t( '2026-11-14 01:00' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-14 00:30' ) ), 'après minuit, encore ouvert depuis la veille : fermeture à 01:00' );
$ok( $t( '2026-11-17 11:15' ) === bigm_horaires_prochain_changement( $heures, $t( '2026-11-15 02:00' ) ), 'dimanche et lundi fermés : ouverture du mardi' );
$ok( 0 === bigm_horaires_prochain_changement( array( 'monday' => array( 'closed' => '1' ) ), $t( '2026-11-10 12:00' ) ), 'aucun horaire : pas de changement' );

WP_CLI::log( 'Durée de cache d’une fiche restaurant' );
if ( post_type_exists( 'tfmsl_store' ) ) {
	$resto = wp_insert_post(
		array(
			'post_type'   => 'tfmsl_store',
			'post_status' => 'publish',
			'post_title'  => 'Test cache',
		)
	);
	update_post_meta( $resto, 'tfmsl_hours', $heures );
	// Mardi 10 novembre 2026, 12:00 à Paris (UTC+1) = 11:00 UTC.
	$ok( 2 * HOUR_IN_SECONDS + MINUTE_IN_SECONDS === bigm_restaurant_duree_cache( $resto, $t( '2026-11-10 11:00' ) ), 'à 12:00 (Paris) : en cache jusqu’à 14:01' );
	// 23:30 à Paris : minuit vient avant l'ouverture du lendemain (ligne du jour).
	$ok( 31 * MINUTE_IN_SECONDS === bigm_restaurant_duree_cache( $resto, $t( '2026-11-10 22:30' ) ), 'à 23:30 : jusqu’à minuit et une minute (ligne du jour)' );
	// 00:30 à Paris = 23:30 UTC : StoreLocatore lit encore le jour en UTC, à revoir à minuit UTC.
	$ok( 31 * MINUTE_IN_SECONDS === bigm_restaurant_duree_cache( $resto, $t( '2026-11-10 23:30' ) ), 'à 00:30 (Paris) : jusqu’à 01:01, minuit UTC (jour lu par StoreLocatore)' );
	update_post_meta( $resto, 'tfmsl_hours', array() );
	$ok( 12 * HOUR_IN_SECONDS + MINUTE_IN_SECONDS === bigm_restaurant_duree_cache( $resto, $t( '2026-11-10 11:00' ) ), 'sans horaires : jusqu’à minuit' );

	$vides = array();
	$espion = static function ( $id ) use ( &$vides ) {
		$vides[] = (int) $id;
	};
	add_action( 'litespeed_purge_post', $espion );
	update_post_meta( $resto, 'tfmsl_hours', $heures );
	remove_action( 'litespeed_purge_post', $espion );
	$ok( in_array( $resto, $vides, true ), 'horaires changés sans enregistrer la fiche (import de Google) : sa page est vidée' );

	// Règle de la page, vue par un visiteur.
	$ttl     = array();
	$espion2 = static function ( $duree ) use ( &$ttl ) {
		$ttl[] = (int) $duree;
	};
	add_action( 'litespeed_control_set_ttl', $espion2 );
	$utilisateur = get_current_user_id();
	wp_set_current_user( 0 );
	$GLOBALS['wp_query']     = new WP_Query( array( 'p' => $resto, 'post_type' => 'tfmsl_store' ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	bigm_cache_regle_page();
	remove_action( 'litespeed_control_set_ttl', $espion2 );
	$ok( 1 === count( $ttl ) && $ttl[0] >= 2 * MINUTE_IN_SECONDS && $ttl[0] <= DAY_IN_SECONDS, 'fiche vue par un visiteur : durée de cache transmise à LiteSpeed (' . ( $ttl[0] ?? '?' ) . ' s)' );
	wp_set_current_user( $utilisateur );
	wp_delete_post( $resto, true );
} else {
	WP_CLI::warning( 'StoreLocatore inactif : fiches restaurants non testées.' );
}

WP_CLI::log( 'Pages à formulaire' );
$pages = array();
foreach ( array(
	'wpforms'   => array( '[wpforms id="12"]', '' ),
	'elementor' => array( '', '[{"elType":"widget","widgetType":"form","settings":[]}]' ),
	'aucun'     => array( '<p>Bonjour</p>', '[{"elType":"widget","widgetType":"heading"}]' ),
) as $cle => $contenu ) {
	$pages[ $cle ] = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Test cache ' . $cle,
			'post_content' => $contenu[0],
		)
	);
	if ( $contenu[1] ) {
		update_post_meta( $pages[ $cle ], '_elementor_data', wp_slash( $contenu[1] ) );
	}
}
$ok( bigm_page_a_formulaire( $pages['wpforms'] ) && bigm_page_a_formulaire( $pages['elementor'] ) && ! bigm_page_a_formulaire( $pages['aucun'] ), 'formulaire WPForms ou Elementor Pro reconnu, page sans formulaire non' );
$hors_cache = array();
$espion3    = static function ( $raison ) use ( &$hors_cache ) {
	$hors_cache[] = (string) $raison;
};
add_action( 'litespeed_control_set_nocache', $espion3 );
$utilisateur = get_current_user_id();
wp_set_current_user( 0 );
foreach ( array( 'wpforms', 'aucun' ) as $cle ) {
	$GLOBALS['wp_query']     = new WP_Query( array( 'page_id' => $pages[ $cle ] ) );
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	bigm_cache_regle_page();
}
wp_set_current_user( $utilisateur );
remove_action( 'litespeed_control_set_nocache', $espion3 );
$ok( 1 === count( $hors_cache ), 'page à formulaire hors cache, les autres en cache' );
foreach ( $pages as $id ) {
	wp_delete_post( $id, true );
}
wp_reset_query();

WP_CLI::log( 'Réglages de LiteSpeed Cache' );
$r = bigm_litespeed_reglages();
$ok( false === $r['cache-rest'] && false === $r['cache-priv'] && true === $r['purge-post_all'] && true === $r['purge-upgrade'] && false === $r['optm-js_comb'] && false === $r['cdn-quic'], 'liste : API REST et connectés hors cache, tout vidé à chaque contenu et mise à jour, pas d’optimisation des JS, pas de QUIC.cloud' );
if ( defined( 'LSCWP_V' ) ) {
	$lus = bigm_litespeed_appliquer();
	$ok( is_array( $lus ) && ! $lus['cache-rest'] && ! $lus['cache-priv'] && $lus['purge-post_all'] && $lus['purge-upgrade'] && ! $lus['optm-css_comb'] && BIGM_LITESPEED_REGLAGES === get_option( 'bigm_litespeed' ), 'réglages appliqués à LiteSpeed Cache ' . LSCWP_V . ' et relus' );
	$route = rest_do_request( new WP_REST_Request( 'POST', '/bigm/v1/litespeed' ) );
	$ok( 200 === $route->get_status() && isset( $route->get_data()['reglages']['cache-rest'] ), 'POST bigm/v1/litespeed : réapplique et renvoie les valeurs' );
} else {
	$ok( null === bigm_litespeed_appliquer(), 'sans LiteSpeed Cache : rien n’est appliqué' );
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'Cache : toutes les vérifications passent.' );
