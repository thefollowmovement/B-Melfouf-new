<?php
/**
 * Test d'IndexNow (bigm-core, inc/indexnow.php) et des données structurées des restaurants
 * (filtre tfmsl_store_schema de inc/restaurants.php, StoreLocatore 2.15). Aucune requête ne part :
 * les appels à api.indexnow.org sont interceptés.
 *
 *   wp eval-file tests/wp/test-bigm-indexnow.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_indexnow_ajouter' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.15.0 ou plus.' );
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

// État d'origine, remis à la fin.
$avant = array(
	'file'    => get_option( 'bigm_indexnow_file', null ),
	'journal' => get_option( 'bigm_indexnow_journal', null ),
	'cle'     => get_option( 'bigm_indexnow_cle', null ),
);
delete_option( 'bigm_indexnow_file' );
delete_option( 'bigm_indexnow_journal' );
wp_clear_scheduled_hook( 'bigm_indexnow_envoi' );

// Requêtes interceptées : réponse choisie par $reponse.
$envois  = array();
$reponse = 202;
add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) use ( &$envois, &$reponse ) {
		if ( 0 !== strpos( $url, BIGM_INDEXNOW_API ) ) {
			return $pre;
		}
		$envois[] = json_decode( (string) $args['body'], true );
		return array( 'response' => array( 'code' => $reponse, 'message' => '' ), 'body' => '', 'headers' => array(), 'cookies' => array() );
	},
	1,
	3
);

WP_CLI::log( 'Inactif hors production' );
add_filter( 'bigm_indexnow_actif', '__return_false' );
$id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Test IndexNow inactif' ) );
$ok( array() === get_option( 'bigm_indexnow_file', array() ), 'site inactif : rien en file' );
$ok( 'IndexNow inactif.' === bigm_indexnow_envoyer( array( home_url( '/' ) ) )['message'] && ! $envois, 'site inactif : rien n’est envoyé' );
wp_delete_post( $id, true );
remove_filter( 'bigm_indexnow_actif', '__return_false' );

WP_CLI::log( 'File d’attente' );
add_filter( 'bigm_indexnow_actif', '__return_true' );
$cle = bigm_indexnow_cle();
$ok( (bool) preg_match( '/^[a-f0-9]{32}$/', $cle ) && $cle === bigm_indexnow_cle(), 'clé de 32 caractères, créée une fois' );
$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
$ok( bigm_indexnow_servir_cle( $base . '/' . $cle . '.txt' ) && ! bigm_indexnow_servir_cle( $base . '/autre.txt' ), 'fichier de la clé : /' . $cle . '.txt seulement' );

$id   = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Test IndexNow' ) );
$lien = get_permalink( $id );
$file = get_option( 'bigm_indexnow_file', array() );
$ok( isset( $file[ $lien ] ), 'article publié : son adresse est en file' );
$ok( (bool) wp_next_scheduled( 'bigm_indexnow_envoi' ), 'envoi planifié' );
$brouillon = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'Test IndexNow brouillon' ) );
$ok( count( get_option( 'bigm_indexnow_file', array() ) ) === 1, 'brouillon : rien en file' );
$secret = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Test IndexNow secret', 'post_password' => 'x' ) );
$ok( count( get_option( 'bigm_indexnow_file', array() ) ) === 1, 'article protégé par mot de passe : rien en file' );
$ok( 0 === bigm_indexnow_ajouter( array( 'https://exemple.com/autre-site/' ) ), 'adresse d’un autre site refusée' );

WP_CLI::log( 'Envoi' );
$r = bigm_indexnow_envoyer();
$e = end( $envois );
$ok( 202 === $r['code'] && 1 === $r['n'], 'file envoyée : 202, 1 adresse' );
$ok( is_array( $e ) && $e['key'] === $cle && $e['keyLocation'] === bigm_indexnow_url_cle() && $e['urlList'] === array( $lien ) && $e['host'] === wp_parse_url( home_url(), PHP_URL_HOST ), 'corps : host, key, keyLocation, urlList' );
$ok( array() === get_option( 'bigm_indexnow_file', array() ), 'file vidée après l’envoi' );
$ok( 1 === count( get_option( 'bigm_indexnow_journal', array() ) ), 'journal : 1 envoi' );

// Mise à la corbeille : l'ancienne adresse publique est signalée (page retirée).
wp_trash_post( $id );
$file = get_option( 'bigm_indexnow_file', array() );
$ok( isset( $file[ $lien ] ), 'corbeille : l’adresse publique est signalée' );

// Refus temporaire : la file est gardée et un nouvel essai planifié.
$reponse = 429;
wp_clear_scheduled_hook( 'bigm_indexnow_envoi' );
$r = bigm_indexnow_envoyer();
$ok( 429 === $r['code'] && isset( get_option( 'bigm_indexnow_file', array() )[ $lien ] ) && wp_next_scheduled( 'bigm_indexnow_envoi' ) > time() + 5 * MINUTE_IN_SECONDS, '429 : file gardée, nouvel essai dans 10 min' );
$reponse = 403;
$r       = bigm_indexnow_envoyer();
$ok( 403 === $r['code'] && isset( get_option( 'bigm_indexnow_file', array() )[ $lien ] ), 'premier 403 (clé peut-être en vérification) : file gardée, nouvel essai' );
$r = bigm_indexnow_envoyer();
$ok( 403 === $r['code'] && array() === get_option( 'bigm_indexnow_file', array() ), 'deuxième 403 de suite : file vidée, refus au journal' );
$reponse = 202;

// Tâche planifiée : do_action() sans argument
bigm_indexnow_ajouter( array( home_url( '/' ) ) );
$n = count( $envois );
do_action( 'bigm_indexnow_envoi' );
$ok( count( $envois ) === $n + 1, 'tâche bigm_indexnow_envoi : envoi de la file' );

WP_CLI::log( 'API REST' );
$req = new WP_REST_Request( 'POST', '/bigm/v1/indexnow' );
$req->set_param( 'urls', array( home_url( '/carte/' ), 'https://exemple.com/' ) );
$rep = rest_do_request( $req );
$e   = end( $envois );
$ok( 200 === $rep->get_status() && array( home_url( '/carte/' ) ) === $e['urlList'], 'POST bigm/v1/indexnow : adresses du site seulement' );
$etat = rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/indexnow' ) )->get_data();
$ok( true === $etat['actif'] && bigm_indexnow_url_cle() === $etat['cle_url'] && count( $etat['journal'] ) >= 5, 'GET bigm/v1/indexnow : état et journal' );
$toutes = bigm_indexnow_toutes();
$ok( in_array( home_url( '/' ), $toutes, true ) && ! in_array( get_permalink( $brouillon ), $toutes, true ), 'toutes les adresses : accueil, sans brouillon' );
$utilisateur = get_current_user_id();
wp_set_current_user( 0 );
$ok( 401 === rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/indexnow' ) )->get_status(), 'API refusée aux visiteurs' );
wp_set_current_user( $utilisateur );

WP_CLI::log( 'Données structurées des restaurants' );
if ( post_type_exists( 'tfmsl_store' ) && is_callable( array( 'TFMSL_SEO_Handler', 'store_schema' ) ) ) {
	$resto  = wp_insert_post( array( 'post_type' => 'tfmsl_store', 'post_status' => 'publish', 'post_title' => 'Test schéma' ) );
	$schema = TFMSL_SEO_Handler::store_schema( $resto );
	$ok( home_url( '/#organisation' ) === ( $schema['parentOrganization']['@id'] ?? '' ), 'enseigne : organisation Big M de l’accueil (#organisation)' );
	$ok( get_post_type_archive_link( 'bigm_produit' ) === ( $schema['hasMenu'] ?? '' ), 'menu : la carte du site' );
	$ok( 'Restaurant' === ( $schema['@type'] ?? '' ) && 'Burgers halal' === ( $schema['servesCuisine'] ?? '' ), 'type Restaurant, cuisine « Burgers halal »' );
	wp_delete_post( $resto, true );
} else {
	WP_CLI::log( '  (StoreLocatore 2.15 absent : données structurées non testées)' );
}

// Ménage
remove_filter( 'bigm_indexnow_actif', '__return_true' );
foreach ( array( $id, $brouillon, $secret ) as $p ) {
	wp_delete_post( $p, true );
}
wp_clear_scheduled_hook( 'bigm_indexnow_envoi' );
foreach ( $avant as $cle_opt => $valeur ) {
	if ( null === $valeur ) {
		delete_option( 'bigm_indexnow_' . $cle_opt );
	} else {
		update_option( 'bigm_indexnow_' . $cle_opt, $valeur, 'cle' === $cle_opt );
	}
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'IndexNow : toutes les vérifications passent.' );
