<?php
/**
 * Test de l'habillage Big M de StoreLocatore (bigm-core, inc/marque-storelocatore.php) : page
 * d'avis, affiches QR, réseaux par défaut des restaurants. Sur un site de test avec bigm-core :
 *
 *   wp eval-file tests/wp/test-bigm-marque.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_marque_polices_css' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.14 ou plus.' );
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

WP_CLI::log( 'Page d’avis' );
ob_start();
do_action( 'tfmsl_rb_page_head', 0 );
$tete = (string) ob_get_clean();
$ok( str_contains( $tete, '@font-face{font-family:"Fredoka"' ) && str_contains( $tete, 'fredoka-latin-600-normal.woff2' ) && str_contains( $tete, 'figtree-latin-400-normal.woff2' ), 'polices auto-hébergées du thème (pas de Google Fonts)' );
$ok( str_contains( $tete, '--tfmsl-rb-accent:#c8400a' ) && str_contains( $tete, '--tfmsl-rb-ink:#2b1406' ) && str_contains( $tete, '--tfmsl-rb-bg:#faf8f5' ) && str_contains( $tete, '--tfmsl-rb-muted:#6b4a3a' ), 'couleurs de la charte (bouton, encre, texte secondaire, fond)' );

WP_CLI::log( 'Affiches QR' );
$defaut = apply_filters( 'tfmsl_poster_default_colors', array( 'band' => '#e94560', 'accent' => '#e94560', 'ink' => '#0f172a', 'paper' => '#ffffff', 'star' => '#fbbf24' ) );
$ok( '#2b1406' === $defaut['band'] && '#c8400a' === $defaut['accent'] && '#faf8f5' === $defaut['paper'] && '#ffc21a' === $defaut['star'], 'couleurs par défaut de la charte (bandeau brun, accent orange, papier blanc cassé, étoiles jaunes)' );
if ( class_exists( 'TFMSL_Review_Booster' ) && method_exists( 'TFMSL_Review_Booster', 'poster_data' ) ) {
	$reglages = get_option( 'tfmsl_rb_settings' );
	$affiche  = TFMSL_Review_Booster::poster_data();
	$ok( empty( $reglages['poster_band'] ) ? '#2b1406' === $affiche['colors']['band'] : true, 'affiche sans couleur choisie : bandeau brun' );
	$ok( '#2b1406' === TFMSL_Review_Booster::poster_default_colors()['ink'], 'sélecteurs de l’admin : couleurs de la charte' );
}
$affiche = apply_filters(
	'tfmsl_poster_style',
	array(
		'title'    => 'Votre avis compte !',
		'font_css' => '',
	)
);
$ok( str_contains( (string) $affiche['font_css'], 'Fredoka' ) && str_contains( (string) $affiche['font_title'], 'Fredoka' ) && str_contains( (string) $affiche['font_text'], 'Figtree' ) && 'Votre avis compte !' === $affiche['title'], 'polices de la charte, textes de l’affiche gardés' );

WP_CLI::log( 'Pastille « Nouveau » et fenêtre « Ouvrir avec »' );
if ( ! wp_style_is( 'tfmsl-public', 'registered' ) ) {
	wp_register_style( 'tfmsl-public', 'https://exemple.test/store-public.css', array(), '1' );
}
// Seulement les crochets de priorité 31 (habillage) : tout wp_enqueue_scripts réveille Elementor.
foreach ( (array) ( $GLOBALS['wp_filter']['wp_enqueue_scripts']->callbacks[31] ?? array() ) as $crochet ) {
	call_user_func( $crochet['function'] );
}
$en_ligne = implode( '', (array) wp_styles()->get_data( 'tfmsl-public', 'after' ) );
$ok( str_contains( $en_ligne, '--tfmsl-new-bg:#ffc21a' ) && str_contains( $en_ligne, '--tfmsl-new-fg:#2b1406' ), 'pastille « Nouveau » : jaune de la charte, texte brun' );
$ok( str_contains( $en_ligne, '--tfmsl-dir-tile:#f2ede7' ) && str_contains( $en_ligne, '--tfmsl-dir-muted:#6b4a3a' ) && str_contains( $en_ligne, '--tfmsl-dir-accent:#c8400a' ), 'fenêtre « Ouvrir avec » : cases beiges, texte secondaire brun, accent orange' );

WP_CLI::log( 'Réseaux des restaurants' );
$propres = array(
	'facebook' => array(
		'url'   => 'https://www.facebook.com/bigm.bondy',
		'label' => 'Facebook',
	),
);
$ok( $propres === apply_filters( 'tfmsl_store_socials', $propres, 1 ), 'restaurant avec ses réseaux : gardés tels quels' );
$marque = apply_filters( 'tfmsl_store_socials', array(), 1 );
$ok( isset( $marque['instagram'] ) && (string) bigm_reglage( 'instagram_url' ) === $marque['instagram']['url'] && 'Instagram' === $marque['instagram']['label'], 'restaurant sans réseau : ceux de Big M (Réglages Big M)' );

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'Habillage de StoreLocatore : toutes les vérifications passent.' );
