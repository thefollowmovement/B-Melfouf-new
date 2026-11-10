<?php
/**
 * Test de la galerie commune des restaurants (bigm-core, inc/galerie-restaurants.php). Sur un site
 * de test avec bigm-core, StoreLocatore et au moins deux restaurants et deux images :
 *
 *   wp eval-file tests/wp/test-bigm-galerie.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_galerie_commune' ) || ! post_type_exists( 'tfmsl_store' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.12 ou plus et StoreLocatore.' );
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

$restos = get_posts(
	array(
		'post_type'      => 'tfmsl_store',
		'post_status'    => 'publish',
		'posts_per_page' => 2,
		'fields'         => 'ids',
	)
);
$images = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => 3,
		'fields'         => 'ids',
	)
);
if ( count( $restos ) < 2 || count( $images ) < 3 ) {
	WP_CLI::error( 'Il faut au moins deux restaurants et trois images.' );
}
$avant_option = get_option( 'bigm_galerie_restaurants', null );
$avant_propre = get_metadata_raw( 'post', $restos[0], 'tfmsl_gallery', true );

// La galerie propre d'un restaurant, pour vérifier qu'elle reste intacte.
$GLOBALS['bigm_galerie_brute'] = true;
update_post_meta( $restos[0], 'tfmsl_gallery', array( $images[2] ) );
unset( $GLOBALS['bigm_galerie_brute'] );

WP_CLI::log( 'Sans galerie commune' );
update_option( 'bigm_galerie_restaurants', array() );
$ok( array( $images[2] ) === get_post_meta( $restos[0], 'tfmsl_gallery', true ), 'chaque restaurant garde sa propre galerie' );

WP_CLI::log( 'Avec galerie commune' );
update_option( 'bigm_galerie_restaurants', array( $images[0], $images[1] ) );
$ok( array( (int) $images[0], (int) $images[1] ) === get_post_meta( $restos[0], 'tfmsl_gallery', true ) && get_post_meta( $restos[0], 'tfmsl_gallery', true ) === get_post_meta( $restos[1], 'tfmsl_gallery', true ), 'toutes les fiches lisent la même galerie, dans l’ordre choisi' );
$ok( array( array( (int) $images[0], (int) $images[1] ) ) === get_post_meta( $restos[0], 'tfmsl_gallery', false ), 'lecture « toutes les valeurs » : la galerie commune aussi' );
$ok( array( (int) $images[2] ) === bigm_galerie_du_restaurant( $restos[0] ), 'galerie propre toujours lisible (reprise dans la page de réglage), jamais effacée' );
$page = get_posts( array( 'post_type' => 'page', 'posts_per_page' => 1, 'fields' => 'ids' ) );
$ok( ! $page || '' === get_post_meta( $page[0], 'tfmsl_gallery', true ), 'autres contenus : rien de changé' );

WP_CLI::log( 'Rendu de StoreLocatore' );
if ( class_exists( 'TFMSL_Widget_Store_Gallery' ) || defined( 'TFMSL_PLUGIN_DIR' ) ) {
	$gabarit = file_get_contents( TFMSL_PLUGIN_DIR . 'templates/single-store.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$ok( str_contains( (string) $gabarit, "get_post_meta( \$post_id, 'tfmsl_gallery', true )" ), 'le gabarit de StoreLocatore lit toujours la galerie par get_post_meta (filtre efficace)' );
}

WP_CLI::log( 'Réglage' );
bigm_galerie_reglage();
$propre = sanitize_option( 'bigm_galerie_restaurants', "{$images[1]},0,abc,{$images[1]},{$images[0]}" );
$ok( array( (int) $images[1], (int) $images[0] ) === $propre, 'réglage nettoyé : identifiants d’images, sans doublon, dans l’ordre' );
$non_image = wp_insert_attachment(
	array(
		'post_title'     => 'Pas une image',
		'post_mime_type' => 'application/pdf',
		'post_status'    => 'inherit',
	)
);
$ok( array() === sanitize_option( 'bigm_galerie_restaurants', (string) $non_image ), 'fichier qui n’est pas une image : refusé' );
wp_delete_attachment( $non_image, true );

// Remise en état.
if ( null === $avant_option ) {
	delete_option( 'bigm_galerie_restaurants' );
} else {
	update_option( 'bigm_galerie_restaurants', $avant_option );
}
$GLOBALS['bigm_galerie_brute'] = true;
if ( '' === $avant_propre || null === $avant_propre ) {
	delete_post_meta( $restos[0], 'tfmsl_gallery' );
} else {
	update_post_meta( $restos[0], 'tfmsl_gallery', $avant_propre );
}
unset( $GLOBALS['bigm_galerie_brute'] );

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'Galerie commune : toutes les vérifications passent.' );
