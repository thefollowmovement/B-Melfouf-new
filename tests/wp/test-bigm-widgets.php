<?php
/**
 * Test des widgets Elementor de bigm-core (slider et sections de l'accueil).
 * Sur un site de test avec Elementor, bigm-core et le thème Big M actifs :
 *
 *   wp eval-file tests/wp/test-bigm-widgets.php --user=<administrateur>
 *
 * Crée une image, un slider, des produits et des articles, puis les supprime.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'bigm_slides_actives' ) ) {
	WP_CLI::error( 'Il faut Elementor et bigm-core actifs.' );
}

$echecs  = 0;
$nettoie = array();
$ok      = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};

// Rend un widget comme sur une page.
$rendu = static function ( string $type, array $settings ): string {
	$el = \Elementor\Plugin::$instance->elements_manager->create_element_instance(
		array(
			'elType'     => 'widget',
			'widgetType' => $type,
			'id'         => substr( md5( $type . wp_json_encode( $settings ) ), 0, 7 ),
			'settings'   => $settings,
		)
	);
	if ( ! $el ) {
		return '';
	}
	ob_start();
	$el->print_element();
	return (string) ob_get_clean();
};

// Une vraie image (GD), pour que WordPress produise src, srcset et dimensions.
$image = static function ( string $nom ) use ( &$nettoie ): int {
	$im = imagecreatetruecolor( 1200, 600 );
	imagefill( $im, 0, 0, imagecolorallocate( $im, 250, 92, 25 ) );
	ob_start();
	imagejpeg( $im );
	$bits = wp_upload_bits( $nom, null, (string) ob_get_clean() );
	$id   = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $nom,
		),
		$bits['file']
	);
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $bits['file'] ) );
	$nettoie[] = $id;
	return (int) $id;
};

WP_CLI::log( 'Widgets enregistrés' );
$types = array( 'bigm-slider', 'bigm-bande-appli', 'bigm-restaurants', 'bigm-services', 'bigm-classiques', 'bigm-affiches', 'bigm-actualites', 'bigm-rejoindre' );
$wm    = \Elementor\Plugin::$instance->widgets_manager;
$ok( ! array_filter( $types, static fn( $t ) => ! $wm->get_widget_types( $t ) ), 'les 8 widgets Big M' );

WP_CLI::log( 'Big M Slider' );
$img    = $image( 'test-slide.jpg' );
$slider = wp_insert_post(
	array(
		'post_type'   => 'bigm_slider',
		'post_title'  => 'Test widgets',
		'post_status' => 'publish',
	)
);
$nettoie[] = $slider;
update_post_meta(
	$slider,
	'_bigm_slides',
	array(
		array_merge( bigm_slide_vide(), array( 'image' => $img, 'alt' => 'Première', 'lien' => home_url( '/carte/' ) ) ),
		array_merge( bigm_slide_vide(), array( 'image' => $img, 'alt' => 'Seconde', 'cadrage' => '50% 30%' ) ),
		array_merge( bigm_slide_vide(), array( 'image' => $img, 'alt' => 'Plus tard', 'debut' => '2099-01-01 00:00' ) ),
	)
);
$hero = $rendu(
	'bigm-slider',
	array(
		'source'      => 'choisi',
		'slider'      => (string) $slider,
		'format'      => 'hero',
		'libelle'     => 'À la une',
		'titre_cache' => 'Titre caché',
	)
);
$ok( str_contains( $hero, 'class="hs"' ) && str_contains( $hero, 'data-interval="6000"' ) && str_contains( $hero, 'aria-label="À la une"' ), 'hero : 2:1, photo 6 s, nom du carrousel' );
$ok( str_contains( $hero, '<h1 class="sr-only"' ) && str_contains( $hero, 'Titre caché</h1>' ), 'hero : titre h1 caché' );
$ok( 2 === substr_count( $hero, 'class="hs__slide"' ) && ! str_contains( $hero, 'Plus tard' ), 'slide programmée plus tard : absente' );
preg_match_all( '/<img[^>]*>/', $hero, $imgs );
$ok( str_contains( $imgs[0][0], 'fetchpriority="high"' ) && ! str_contains( $imgs[0][0], 'loading="lazy"' ), 'première slide : chargée tout de suite, en priorité' );
$ok( str_contains( $imgs[0][1] ?? '', 'object-position:50% 30%' ) && str_contains( $imgs[0][1] ?? '', 'srcset=' ), 'seconde slide : cadrage et srcset' );
$ok( str_contains( $hero, 'href="' . home_url( '/carte/' ) . '"' ) && str_contains( $hero, '<a draggable="false">' ), 'lien de slide, ou rien sans lien' );
$banniere = $rendu(
	'bigm-slider',
	array(
		'source'  => 'choisi',
		'slider'  => (string) $slider,
		'format'  => 'banniere',
		'libelle' => 'Offres du moment',
	)
);
$ok( str_contains( $banniere, 'hs hs--banner' ) && str_contains( $banniere, 'data-interval="5000"' ) && str_contains( $banniere, 'class="hs-banners" aria-label="Offres du moment"' ), 'bannière : 3:1, photo 5 s, section nommée' );
$ok( '' === trim( $rendu( 'bigm-slider', array( 'source' => 'choisi', 'slider' => '0' ) ) ), 'sans slider : rien sur le site' );

WP_CLI::log( 'Sections de l’accueil' );
$appli = $rendu( 'bigm-bande-appli', array( 'puces' => "Un\n\nDeux\nTrois" ) );
$ok( str_contains( $appli, 'id="appli"' ) && 3 === substr_count( $appli, '<li>' ), 'bande appli : ancre et 3 avantages' );
$ok( str_contains( $appli, 'class="store"' ), 'bande appli : badges des stores' );
$restos = $rendu( 'bigm-restaurants', array() );
$ok( (bool) preg_match( '#<form class="restos__search" action="[^"]*/restaurants/" method="get"#', $restos ) && str_contains( $restos, 'name="q"' ), 'restaurants : la recherche ouvre /restaurants/?q=…' );
$services = $rendu( 'bigm-services', array() );
$ok( 3 === substr_count( $services, 'class="service"' ), 'services : les 3 cartes du prototype' );
$affiches = $rendu( 'bigm-affiches', array() );
$ok( 4 === substr_count( $affiches, 'class="legend"' ) && str_contains( $affiches, '<b>1967</b>' ) && str_contains( $affiches, 'id="legendes"' ), 'affiches : 4 années, ancre' );
$rejoindre = $rendu( 'bigm-rejoindre', array() );
$ok( str_contains( $rejoindre, 'id="franchise"' ) && str_contains( $rejoindre, 'id="recrutement"' ) && str_contains( $rejoindre, esc_html( bigm_reglage_texte( 'franchise_droit_entree' ) ) ), 'nous rejoindre : ancres et chiffres des Réglages Big M' );

WP_CLI::log( 'Classiques et actualités' );
$p1 = wp_insert_post( array( 'post_type' => 'bigm_produit', 'post_title' => 'Test B', 'post_status' => 'publish' ) );
$p2 = wp_insert_post( array( 'post_type' => 'bigm_produit', 'post_title' => 'Test A', 'post_status' => 'publish' ) );
set_post_thumbnail( $p1, $img );
array_push( $nettoie, $p1, $p2 );
$classiques = $rendu(
	'bigm-classiques',
	array(
		'source'   => 'choix',
		'produits' => array( array( '_id' => 'a', 'produit' => (string) $p1 ), array( '_id' => 'b', 'produit' => (string) $p2 ) ),
	)
);
$ok( 2 === preg_match_all( '/class="tile( has-video)?"/', $classiques ) && strpos( $classiques, 'Test B' ) < strpos( $classiques, 'Test A' ), 'classiques : produits choisis, dans l’ordre choisi' );
$ok( str_contains( $classiques, 'data-carousel' ) && str_contains( $classiques, 'btn btn--outline' ), 'classiques : carrousel et bouton « Voir la carte »' );
$presse = get_term_by( 'slug', 'presse', 'category' );
$art    = wp_insert_post(
	array(
		'post_title'    => 'Article de test',
		'post_status'   => 'publish',
		'post_date'     => '2026-10-09 10:00:00',
		'post_category' => $presse ? array( $presse->term_id ) : array(),
	)
);
$nettoie[] = $art;
$actus     = $rendu( 'bigm-actualites', array( 'nombre' => 1 ) );
$ok( 1 === substr_count( $actus, 'class="news-card"' ) && str_contains( $actus, 'Presse · ' . get_the_date( 'j F Y', $art ) ), 'actualités : catégorie et date (« Presse · 9 octobre 2026 » en français)' );

foreach ( $nettoie as $id ) {
	'attachment' === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'widgets Big M : toutes les vérifications passent.' );
