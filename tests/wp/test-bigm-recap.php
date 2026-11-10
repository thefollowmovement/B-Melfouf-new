<?php
/**
 * Test du récapitulatif des avis par e-mail (bigm-core, inc/recap-avis.php). Sur un site de test
 * avec bigm-core et StoreLocatore (tables de la collecte d'avis) et au moins deux restaurants :
 *
 *   wp eval-file tests/wp/test-bigm-recap.php --user=<administrateur>
 *
 * Aucun e-mail ne part : pre_wp_mail les intercepte.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_recap_donnees' ) || ! post_type_exists( 'tfmsl_store' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.13 ou plus et StoreLocatore.' );
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
global $wpdb;

WP_CLI::log( 'Période et jour d’envoi' );
$tz    = wp_timezone();
$lundi = ( new DateTimeImmutable( '2026-11-16 00:00', $tz ) )->getTimestamp();
list( $d, $f ) = bigm_recap_periode( 'semaine', $lundi );
$ok( '2026-11-09 00:00' === wp_date( 'Y-m-d H:i', $d ) && $f === $lundi, 'semaine : du lundi au dimanche précédents' );
list( $d ) = bigm_recap_periode( 'mois', ( new DateTimeImmutable( '2026-12-01 00:00', $tz ) )->getTimestamp() );
$ok( '2026-11-01 00:00' === wp_date( 'Y-m-d H:i', $d ), 'mois : le mois écoulé' );
$ok( bigm_recap_jour_d_envoi( 'semaine', $lundi + 9 * HOUR_IN_SECONDS ) && ! bigm_recap_jour_d_envoi( 'semaine', $lundi + DAY_IN_SECONDS ) && bigm_recap_jour_d_envoi( 'mois', ( new DateTimeImmutable( '2026-12-01 09:00', $tz ) )->getTimestamp() ) && ! bigm_recap_jour_d_envoi( 'aucune', $lundi ), 'jour d’envoi : lundi, 1er du mois, jamais' );

WP_CLI::log( 'Données' );
$restos = get_posts(
	array(
		'post_type'      => 'tfmsl_store',
		'post_status'    => 'publish',
		'posts_per_page' => 2,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);
if ( count( $restos ) < 2 ) {
	WP_CLI::error( 'Il faut au moins deux restaurants.' );
}
$fin   = time() + MINUTE_IN_SECONDS; // le retour créé plus bas est dans la période
$debut = $fin - 7 * DAY_IN_SECONDS;
$r1    = (int) $restos[0];
$r2    = (int) $restos[1];
$avant = array(
	'resume' => get_transient( 'tfmsl_place_summary_' . $r1 ),
	'histo'  => get_post_meta( $r1, 'tfmsl_rating_history', true ),
	'email'  => get_post_meta( $r1, 'tfmsl_manager_email', true ),
);
set_transient(
	'tfmsl_place_summary_' . $r1,
	array(
		'rating'     => 4.6,
		'count'      => 212,
		'maps_uri'   => 'https://maps.google.com/?cid=1',
		'fetched_at' => current_time( 'mysql' ),
		'reviews'    => array(
			array(
				'author_name'  => 'Nadia <b>B.</b>',
				'rating'       => 5,
				'text'         => 'Super burger, équipe au top !',
				'publish_time' => gmdate( 'c', $fin - 2 * DAY_IN_SECONDS ),
			),
			array(
				'author_name'  => 'Vieux',
				'rating'       => 3,
				'text'         => 'Avis ancien',
				'publish_time' => gmdate( 'c', $fin - 40 * DAY_IN_SECONDS ),
			),
		),
	),
	HOUR_IN_SECONDS
);
update_post_meta(
	$r1,
	'tfmsl_rating_history',
	array(
		wp_date( 'Y-m-d', $fin - 10 * DAY_IN_SECONDS ) => array(
			'rating' => 4.5,
			'count'  => 205,
		),
		wp_date( 'Y-m-d' )                              => array(
			'rating' => 4.6,
			'count'  => 212,
		),
	)
);
update_post_meta( $r1, 'tfmsl_manager_email', 'resto@exemple.test' );
$table_retours = bigm_recap_table( 'tfmsl_feedback' );
$retour_id     = 0;
if ( $table_retours ) {
	$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_retours,
		array(
			'post_id'    => $r1,
			'rating'     => 2,
			'message'    => 'Attente trop longue <script>x</script>',
			'name'       => 'Karim',
			'contact'    => 'karim@exemple.test',
			'source'     => 'qr',
			'status'     => 'new',
			'created_at' => current_time( 'mysql' ),
		)
	);
	$retour_id = (int) $wpdb->insert_id;
}

$d1 = bigm_recap_donnees( $debut, $fin, array( $r1, $r2 ) );
$a  = $d1[0];
$ok( 4.6 === $a['note'] && 212 === $a['nombre'] && 4.5 === $a['note_avant'], 'note Google, nombre d’avis et note au début de la période (historique)' );
$ok( 1 === count( $a['avis'] ) && 'Super burger, équipe au top !' === $a['avis'][0]['texte'], 'nouveaux avis : seulement ceux publiés dans la période' );
if ( $table_retours ) {
	$ok( 1 === count( $a['retours'] ) && 'Karim' === $a['retours'][0]['name'], 'retours privés de la page d’avis, dans la période' );
}
$ok( 'resto@exemple.test' === $a['email'], 'adresse du restaurant : « Email du responsable » de sa fiche' );
$ok( bigm_recap_actif( $a ) && ! bigm_recap_actif( array_merge( $d1[1], array( 'avis' => array(), 'retours' => array(), 'vues' => 0, 'nombre' => null ) ) ), 'activité : restaurant avec du nouveau, restaurant sans rien' );

WP_CLI::log( 'E-mail' );
$html = bigm_recap_html( $d1, $debut, $fin, true );
$ok( str_contains( $html, 'Récapitulatif des avis' ) && str_contains( $html, '#c8400a' ) && str_contains( $html, '#2b1406' ) && str_contains( $html, '#faf8f5' ), 'aux couleurs de la charte (bouton, encre, fond)' );
$ok( str_contains( $html, 'Nadia &lt;b&gt;B.&lt;/b&gt;' ) && ! str_contains( $html, '<script>x' ), 'contenu des clients échappé' );
$ok( 2 === substr_count( $html, 'href="' . esc_url( get_permalink( $r1 ) ) . '"' ) || str_contains( $html, esc_url( get_permalink( $r1 ) ) ), 'lien vers la fiche du restaurant' );
$ok( str_contains( $html, '+0,1' ) || str_contains( $html, '+0.1' ), 'évolution de la note affichée (+0,1)' );
$seul = bigm_recap_html( array( $a ), $debut, $fin, false );
$ok( str_contains( $seul, 'Les avis de ' . $a['nom'] ) && ! str_contains( $seul, '>Restaurant</th>' ), 'e-mail d’un restaurant : son nom, sans le tableau du réseau' );
$ok( str_contains( bigm_recap_texte( $html ), 'Super burger' ) && ! preg_match( '#<(table|td|tr|a|div|p)\b#', bigm_recap_texte( $html ) ), 'version texte jointe (sans balises)' );

WP_CLI::log( 'Envoi' );
$envoyes = array();
$piege   = static function ( $retour, $atts ) use ( &$envoyes ) {
	$envoyes[] = $atts;
	return true;
};
add_filter( 'pre_wp_mail', $piege, 10, 2 );
$filtre = static fn( $v ) => array_merge( (array) $v, array( 'recap_email' => 'general@exemple.test', 'recap_restaurants' => '1' ) );
add_filter( 'option_bigm_reglages', $filtre );
$bilan = bigm_recap_envoyer( $debut, $fin );
$ok( 'general@exemple.test' === $bilan['general'] && in_array( 'general@exemple.test', array_column( $envoyes, 'to' ), true ), 'e-mail général envoyé à l’adresse des réglages' );
$ok( in_array( 'resto@exemple.test', array_column( $envoyes, 'to' ), true ), 'restaurant avec du nouveau : son e-mail envoyé' );
$ok( str_contains( (string) ( $envoyes[0]['headers'][0] ?? '' ), 'text/html' ), 'e-mail en HTML' );
$envoyes = array();
bigm_recap_envoyer( $debut, $fin, 'moi@exemple.test' );
$ok( array( 'moi@exemple.test' ) === array_values( array_unique( array_column( $envoyes, 'to' ) ) ) && str_starts_with( (string) $envoyes[0]['subject'], '[Essai]' ), 'essai : tout part vers soi, rien aux restaurants' );
remove_filter( 'option_bigm_reglages', $filtre );
remove_filter( 'pre_wp_mail', $piege, 10 );
$ok( (bool) wp_next_scheduled( 'bigm_recap_avis' ), 'envoi planifié chaque jour (9 h)' );

// Remise en état.
false === $avant['resume'] ? delete_transient( 'tfmsl_place_summary_' . $r1 ) : set_transient( 'tfmsl_place_summary_' . $r1, $avant['resume'] );
'' === $avant['histo'] ? delete_post_meta( $r1, 'tfmsl_rating_history' ) : update_post_meta( $r1, 'tfmsl_rating_history', $avant['histo'] );
'' === $avant['email'] ? delete_post_meta( $r1, 'tfmsl_manager_email' ) : update_post_meta( $r1, 'tfmsl_manager_email', $avant['email'] );
if ( $retour_id ) {
	$wpdb->delete( $table_retours, array( 'id' => $retour_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'Récapitulatif des avis : toutes les vérifications passent.' );
