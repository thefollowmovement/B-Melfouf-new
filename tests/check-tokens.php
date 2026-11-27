<?php
/**
 * Vérifie la charte du thème enfant, sans WordPress :
 * - les couleurs de inc/tokens.php et de assets/css/tokens.css concordent ;
 * - chaque police a son @font-face et son fichier woff2 ;
 * - aucun token ne porte un nom qu'Elementor utilise déjà (--display, --text).
 *
 * Usage : php tests/check-tokens.php
 *
 * @package BigM\Tests
 */

define( 'ABSPATH', __DIR__ . '/' ); // Laisse passer la garde des fichiers du thème.

$theme = dirname( __DIR__ ) . '/wp-content/themes/bigm-child';
require $theme . '/inc/tokens.php';

$css    = (string) file_get_contents( $theme . '/assets/css/tokens.css' );
$errors = array();

// Variables déclarées dans :root.
$vars = array();
if ( preg_match( '/:root\s*\{(.*?)\}/s', $css, $root ) ) {
	preg_match_all( '/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $root[1], $matches, PREG_SET_ORDER );
	foreach ( $matches as $m ) {
		$vars[ $m[1] ] = strtolower( trim( $m[2] ) );
	}
} else {
	$errors[] = 'tokens.css : bloc :root introuvable';
}

foreach ( bigm_child_colors() as $name => $hex ) {
	if ( ! isset( $vars[ $name ] ) ) {
		$errors[] = "--$name : absent de tokens.css";
	} elseif ( strtolower( $hex ) !== $vars[ $name ] ) {
		$errors[] = "--$name : tokens.css = {$vars[ $name ]}, tokens.php = $hex";
	}
}

foreach ( array( 'display', 'text' ) as $reserved ) {
	if ( isset( $vars[ $reserved ] ) ) {
		$errors[] = "--$reserved : nom utilisé par Elementor, prendre --font-$reserved";
	}
}

foreach ( array_merge( glob( $theme . '/assets/css/*.css' ), glob( dirname( __DIR__ ) . '/wp-content/plugins/*/assets/css/*.css' ) ) as $file ) {
	$code = (string) preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) ); // Sans les commentaires.
	if ( preg_match( '/var\(--(display|text)\)/', $code ) ) {
		$errors[] = basename( $file ) . ' : var(--display) ou var(--text) à remplacer par var(--font-display) ou var(--font-text)';
	}
}

foreach ( bigm_child_fonts() as $family ) {
	if ( ! str_contains( $css, 'font-family: "' . $family . '"' ) ) {
		$errors[] = "@font-face manquant pour $family";
	}
}

preg_match_all( '#url\("\.\./fonts/([^"]+)"\)#', $css, $files );
foreach ( $files[1] as $file ) {
	if ( ! is_file( $theme . '/assets/fonts/' . $file ) ) {
		$errors[] = "Police introuvable : assets/fonts/$file";
	}
}

if ( $errors ) {
	fwrite( STDERR, "Charte : problèmes trouvés\n- " . implode( "\n- ", $errors ) . "\n" );
	exit( 1 );
}

printf( "Charte OK : %d couleurs, %d fichiers de polices.\n", count( bigm_child_colors() ), count( $files[1] ) );
