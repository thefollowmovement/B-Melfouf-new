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
$ok( str_contains( $tete, '--tfmsl-rb-accent:#c8400a' ) && str_contains( $tete, '--tfmsl-rb-ink:#2b1406' ) && str_contains( $tete, '--tfmsl-rb-bg:#faf8f5' ), 'couleurs de la charte (bouton, encre, fond)' );

WP_CLI::log( 'Affiches QR' );
$affiche = apply_filters(
	'tfmsl_poster_style',
	array(
		'title'    => 'Votre avis compte !',
		'font_css' => '',
	)
);
$ok( isset( $affiche['colors'] ) && '#2b1406' === $affiche['colors']['band'] && '#ffc21a' === $affiche['colors']['star'] && '#faf8f5' === $affiche['colors']['paper'], 'couleurs de la charte quand aucune n’est choisie (bandeau brun, étoiles jaunes)' );
$ok( str_contains( (string) $affiche['font_css'], 'Fredoka' ) && str_contains( (string) $affiche['font_title'], 'Fredoka' ) && str_contains( (string) $affiche['font_text'], 'Figtree' ) && 'Votre avis compte !' === $affiche['title'], 'polices de la charte, textes de l’affiche gardés' );

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
