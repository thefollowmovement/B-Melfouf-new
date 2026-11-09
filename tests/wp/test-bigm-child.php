<?php
/**
 * Test du thème bigm-child : menus, liens, en-tête, tiroir, barre d'onglets, pied de page.
 * Sur un site où le thème est actif (et bigm-core aussi, de préférence) :
 *
 *   wp eval-file tests/wp/test-bigm-child.php --user=<administrateur>
 *
 * Ne modifie rien de durable : les réglages touchés sont remis en place.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
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

if ( ! function_exists( 'bigm_child_entete_html' ) ) {
	WP_CLI::error( 'Le thème Big M (bigm-child) n’est pas actif.' );
}

WP_CLI::log( 'Menus' );
$emplacements = array_keys( get_registered_nav_menus() );
$ok( ! array_diff( array( 'bigm-principal', 'bigm-tiroir', 'bigm-onglets', 'bigm-pied-produits', 'bigm-pied-entreprise', 'bigm-pied-legal' ), $emplacements ), 'les 6 emplacements Big M' );
$ok( ! in_array( 'menu-1', $emplacements, true ), 'emplacements de Hello retirés' );
$ok( home_url( '/carte/' ) === bigm_child_url( '/carte/' ) && 'https://ubereats.com/x' === bigm_child_url( 'https://ubereats.com/x' ), 'chemins relatifs complétés, liens externes intacts' );

$avant = get_theme_mod( 'nav_menu_locations' );
set_theme_mod( 'nav_menu_locations', array() );
$ok( 7 === count( bigm_child_liens( 'bigm-tiroir' ) ) && 4 === count( bigm_child_liens( 'bigm-principal' ) ), 'emplacement vide : liens du prototype' );
set_theme_mod( 'nav_menu_locations', $avant );

WP_CLI::log( 'Page courante' );
query_posts( array( 'bigm_categorie' => 'burgers' ) );
$_SERVER['REQUEST_URI'] = '/carte/burgers/';
$ok( bigm_child_est_courant( '/carte/' ), '« Notre carte » allumé sur /carte/burgers/' );
$ok( ! bigm_child_est_courant( '/actualites/' ) && ! bigm_child_est_courant( '/' ), 'les autres liens éteints' );
$ok( ! bigm_child_est_courant( '/carte/burgers/#allergenes' ), 'un lien vers une ancre n’est jamais allumé' );
wp_reset_query();

WP_CLI::log( 'En-tête, tiroir, barre d’onglets' );
$reglages = get_option( 'bigm_reglages', null );
if ( function_exists( 'bigm_reglages_nettoyer' ) ) {
	update_option( 'bigm_reglages', bigm_reglages_nettoyer( array_merge( (array) $reglages, array( 'commander_url' => '' ) ) ) );
}
$entete = bigm_child_entete_html();
$ok( str_contains( $entete, 'href="' . esc_url( home_url( '/#appli' ) ) . '"' ), 'sans lien Commander réglé : la bande appli de l’accueil' );
if ( function_exists( 'bigm_reglages_nettoyer' ) ) {
	update_option( 'bigm_reglages', bigm_reglages_nettoyer( array_merge( (array) $reglages, array( 'commander_url' => 'https://commande.example.test/' ) ) ) );
	$ok( str_contains( bigm_child_entete_html(), 'href="https://commande.example.test/"' ) && str_contains( bigm_child_onglets_html(), 'href="https://commande.example.test/"' ), 'lien Commander des Réglages Big M : en-tête et bouton central' );
}
$ok( str_contains( $entete, 'aria-controls="drawer"' ) && str_contains( $entete, 'aria-label="Big M, accueil"' ), 'bouton du tiroir et logo accessibles' );
$ok( 2 === substr_count( $entete, 'href="' . esc_url( home_url( '/#appli' ) ) . '"' ) && (bool) preg_match( '#<a class="header__panier" href="[^"]+" aria-label="Commander"><svg#', $entete ), 'icône panier (Commander, mobile) avec son nom, même lien que le bouton Commander' );
$tiroir = bigm_child_tiroir_html();
$ok( str_contains( $tiroir, 'role="dialog"' ) && 7 === substr_count( $tiroir, '<a ' ), 'tiroir : dialogue et 7 liens' );
$onglets = bigm_child_onglets_html();
$ok( 3 === substr_count( $onglets, 'class="tabbar__item" href' ) && str_contains( $onglets, 'tabbar__fab' ) && str_contains( $onglets, '>Plus</button>' ), 'barre d’onglets : 3 liens, Commander, Plus' );
$ok( 4 === substr_count( $onglets, 'class="tabbar__ico"' ), 'barre d’onglets : 4 icônes' );

WP_CLI::log( 'Pied de page' );
$pied = bigm_child_pied_html();
$ok( str_contains( $pied, 'Pour votre santé, mangez au moins cinq fruits et légumes par jour. www.mangerbouger.fr' ), 'mention mangerbouger.fr' );
$ok( str_contains( $pied, '© ' . wp_date( 'Y' ) ), 'année du copyright' );
$ok( ! str_contains( $pied, 'class="store"' ) && ! str_contains( $pied, 'footer__app' ), 'badges des stores : pas dans le pied de page par défaut (case décochée)' );
if ( function_exists( 'bigm_reglages_nettoyer' ) ) {
	update_option( 'bigm_reglages', bigm_reglages_nettoyer( array_merge( (array) get_option( 'bigm_reglages' ), array( 'pied_stores' => '1', 'appstore_url' => 'https://apps.apple.com/fr/app/big-m/id6444707190', 'tiktok_url' => '', 'youtube_url' => 'https://youtube.com/@bigm', 'googleplay_url' => '' ) ) ) );
	$pied = bigm_child_pied_html();
	$ok( ! str_contains( $pied, 'aria-label="TikTok"' ) && str_contains( $pied, 'aria-label="YouTube"' ), 'réseaux : seuls ceux renseignés' );
	$ok( ! str_contains( $pied, 'Google Play' ) && str_contains( $pied, 'App Store' ) && str_contains( $pied, 'footer__app' ), 'case cochée : badges, sauf celui sans lien' );
}
null === $reglages ? delete_option( 'bigm_reglages' ) : update_option( 'bigm_reglages', $reglages );

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'bigm-child : toutes les vérifications passent.' );
