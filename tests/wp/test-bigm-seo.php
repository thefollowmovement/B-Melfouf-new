<?php
/**
 * Test de l'étape 7 : redirections, référencement, performance, reprise des articles, sécurité.
 * Sur un site de test avec Elementor, bigm-core (0.6 ou plus) et le thème Big M :
 *
 *   wp eval-file tests/wp/test-bigm-seo.php --user=<administrateur>
 *
 * Ne contacte pas l'ancien site. Crée une page, un article, un slider et un produit, puis les supprime.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_redirection_cible' ) || ! function_exists( 'bigm_seo_page' ) || ! function_exists( 'bigm_reprise_article' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.6 ou plus actif.' );
}

$echecs = 0;
$posts  = array();
$termes = array();
$ok     = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};
$page   = static function ( array $query ): void {
	$GLOBALS['wp_query']     = new WP_Query( $query ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( $GLOBALS['wp_query']->post ) {
		$GLOBALS['post'] = $GLOBALS['wp_query']->post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
};
$tete   = static function (): string {
	ob_start();
	do_action( 'wp_head' );
	return (string) ob_get_clean();
};
$assets = static function (): array {
	foreach ( array( 'wp_styles', 'wp_scripts' ) as $g ) {
		$GLOBALS[ $g ] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	wp_styles();
	wp_scripts();
	do_action( 'wp_enqueue_scripts' );
	return array_merge( wp_styles()->queue, wp_scripts()->queue );
};
$json   = static function ( string $type ): ?array {
	foreach ( bigm_seo_donnees_structurees() as $objet ) {
		if ( $type === ( $objet['@type'] ?? '' ) ) {
			return $objet;
		}
	}
	return null;
};

WP_CLI::log( 'Redirections' );
$regles = bigm_redirections( "# commentaire\n/nos-restaurants/ /restaurants/\n/boutiques/* /restaurants/*\n/app/ [commander]\n/meme/ /meme/\nn'importe quoi\n/seul\n" );
$ok( 4 === count( $regles ), 'lecture des règles : commentaires et lignes invalides ignorés' );
$ok( home_url( '/restaurants/' ) === bigm_redirection_cible( '/nos-restaurants/', $regles ) && home_url( '/restaurants/' ) === bigm_redirection_cible( '/NOS-Restaurants', $regles ), 'adresse exacte, sans tenir compte des majuscules ni de la barre finale' );
$ok( home_url( '/restaurants/bondy/' ) === bigm_redirection_cible( '/boutiques/bondy/', $regles ) && home_url( '/restaurants/' ) === bigm_redirection_cible( '/boutiques/', $regles ), 'début d’adresse : « * » reprend la suite' );
$ok( '' === bigm_redirection_cible( '/boutiquesx/', $regles ) && '' === bigm_redirection_cible( '/autre/', $regles ), 'adresse voisine ou inconnue : pas de redirection' );
$ok( '' === bigm_redirection_cible( '/meme/', $regles ), 'cible identique à l’adresse : ignorée (pas de boucle)' );
$commander = function_exists( 'bigm_url_commander' ) ? bigm_url_commander() : '';
$ok( $commander === bigm_redirection_cible( '/app/', $regles ), '[commander] : lien Commander des réglages' );
$defaut = bigm_redirections( bigm_redirections_par_defaut() );
$ok( home_url( '/actualites/' ) === bigm_redirection_cible( '/news/', $defaut ) && home_url( '/actualites/presse/' ) === bigm_redirection_cible( '/category/presse/web/', $defaut ) && home_url( '/carte/burgers/' ) === bigm_redirection_cible( '/categorie-de-produit/burger/', $defaut ), 'règles par défaut : anciennes rubriques vers les nouvelles' );
$ok( '' !== (string) bigm_reglage( 'redirections' ), 'règles par défaut dans Réglages Big M' );

WP_CLI::log( 'Référencement' );
$ok( bigm_seo_actif(), 'aucune extension SEO : le référencement Big M est actif' );
add_filter( 'bigm_seo_actif', '__return_false' );
$ok( ! bigm_seo_actif(), 'filtre bigm_seo_actif : le référencement Big M s’efface (Yoast, Rank Math…)' );
remove_filter( 'bigm_seo_actif', '__return_false' );

$test_page = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_title'   => 'Page référencement',
		'post_status'  => 'publish',
		'post_content' => '<p>Un texte de présentation assez long pour servir de description quand aucune n’est saisie dans le bloc Référencement de la page.</p>',
	)
);
$posts[]   = $test_page;
$page( array( 'page_id' => $test_page ) );
$s = bigm_seo_page( true );
$ok( $s && str_starts_with( $s['description'], 'Un texte de présentation' ) && mb_strlen( $s['description'] ) <= 155 && get_permalink( $test_page ) === $s['url'], 'page sans réglage : description tirée du texte (155 caractères maximum)' );
update_post_meta( $test_page, '_bigm_seo_titre', 'Titre choisi | Big M' );
update_post_meta( $test_page, '_bigm_seo_description', 'Description choisie.' );
update_post_meta( $test_page, '_bigm_seo_noindex', '1' );
$s = bigm_seo_page( true );
$ok( 'Titre choisi | Big M' === wp_get_document_title() && 'Description choisie.' === $s['description'] && $s['noindex'], 'bloc Référencement : titre, description et « ne pas indexer »' );
$h = $tete();
$ok( str_contains( $h, '<meta name="description" content="Description choisie.">' ) && str_contains( $h, '<meta property="og:title" content="Titre choisi | Big M">' ) && str_contains( $h, '<meta property="og:locale" content="fr_FR">' ) && str_contains( $h, 'name="twitter:card"' ), 'balises description, Open Graph et Twitter' );
// Comme en production : site indexable, sans la règle « préprod non indexée » du socle.
$public = static fn() => '1';
add_filter( 'pre_option_blog_public', $public, 99 );
$socle_robots = has_filter( 'wp_robots', 'wp_robots_no_robots' );
remove_filter( 'wp_robots', 'wp_robots_no_robots', false === $socle_robots ? 10 : $socle_robots );
$robots_exclue = apply_filters( 'wp_robots', array() );
delete_post_meta( $test_page, '_bigm_seo_noindex' );
bigm_seo_page( true );
$robots_normale = apply_filters( 'wp_robots', array() );
remove_filter( 'pre_option_blog_public', $public, 99 );
if ( false !== $socle_robots ) {
	add_filter( 'wp_robots', 'wp_robots_no_robots', $socle_robots );
}
$ok( ! empty( $robots_exclue['noindex'] ) && empty( $robots_normale['noindex'] ), 'page « ne pas indexer » : balise robots noindex, et seulement elle' );

$front = (int) get_option( 'page_on_front' );
if ( 'page' === get_option( 'show_on_front' ) && $front ) {
	$page( array( 'page_id' => $front ) );
	bigm_seo_page( true );
	$ok( $json( 'Organization' ) && $json( 'WebSite' ) && home_url( '/' ) === $json( 'WebSite' )['url'], 'accueil : données structurées Organization et WebSite' );
} else {
	WP_CLI::log( '  --    pas de page d’accueil statique : Organization non vérifiée' );
}

$produit = wp_insert_post(
	array(
		'post_type'    => 'bigm_produit',
		'post_title'   => 'Produit référencement',
		'post_status'  => 'publish',
		'post_content' => 'Un burger de test.',
	)
);
$posts[] = $produit;
$cat     = get_terms(
	array(
		'taxonomy'   => 'bigm_categorie',
		'hide_empty' => false,
		'parent'     => 0,
		'number'     => 1,
	)
);
if ( $cat && ! is_wp_error( $cat ) ) {
	wp_set_object_terms( $produit, array( (int) $cat[0]->term_id ), 'bigm_categorie' );
}
$page(
	array(
		'p'         => $produit,
		'post_type' => 'bigm_produit',
	)
);
bigm_seo_page( true );
$menu   = $json( 'MenuItem' );
$ariane = $json( 'BreadcrumbList' );
$ok( $menu && 'Produit référencement' === $menu['name'] && str_contains( wp_json_encode( $menu ), 'HalalDiet' ), 'fiche produit : MenuItem, halal' );
$ok( $ariane && 'Accueil' === $ariane['itemListElement'][0]['name'] && end( $ariane['itemListElement'] )['name'] === 'Produit référencement', 'fiche produit : fil d’Ariane Accueil › … › produit' );

$page( array( 'post_type' => 'bigm_produit' ) );
$s = bigm_seo_page( true );
$ok( $s && get_post_type_archive_link( 'bigm_produit' ) === $s['url'], '/carte/ : adresse canonique et description des réglages' );

$ok( ! wp_sitemaps_get_server()->registry->get_provider( 'users' ), 'plan du site : pas de liste des comptes' );
$ok( ! isset( apply_filters( 'wp_sitemaps_taxonomies', array( 'post_format' => 1, 'category' => 1 ) )['post_format'] ), 'plan du site : pas de formats d’articles' );
$ok( 'actualites' === get_option( 'category_base' ), 'catégories d’articles sous /actualites/' );
$regles_ok = get_option( 'rewrite_rules' );
update_option( 'rewrite_rules', array_filter( (array) $regles_ok, static fn( $k ) => ! str_starts_with( (string) $k, 'actualites/' ), ARRAY_FILTER_USE_KEY ) );
$repare = bigm_core_verifier_regles();
$ok( $repare && preg_grep( '#^actualites/\(#', array_keys( (array) get_option( 'rewrite_rules' ) ) ) && ! bigm_core_verifier_regles(), 'règles sans /actualites/… (préprod, filtres News / Presse en 404) : recalculées, une seule fois' );
$pagination = bigm_core_regle_pagination();
if ( $pagination ) {
	$avec = (array) get_option( 'rewrite_rules' );
	$cle  = array_key_first( $pagination );
	$ok( isset( $avec[ $cle ] ) && array_search( $cle, array_keys( $avec ), true ) < array_search( 'actualites/(.+?)/?$', array_keys( $avec ), true ), '/actualites/page/2/ : pagination de la page des articles avant les règles des catégories' );
	unset( $avec[ $cle ] );
	update_option( 'rewrite_rules', $avec );
	$ok( bigm_core_verifier_regles() && isset( get_option( 'rewrite_rules' )[ $cle ] ), 'règles sans cette pagination (préprod) : recalculées' );
} else {
	WP_CLI::log( '  --    pas de page des articles à /actualites/ : pagination non vérifiée' );
}
$vide = wp_insert_term( 'Catégorie test SEO', 'bigm_categorie' );
if ( ! is_wp_error( $vide ) ) {
	$page( array( 'tax_query' => array( array( 'taxonomy' => 'bigm_categorie', 'field' => 'term_id', 'terms' => $vide['term_id'] ) ), 'bigm_categorie' => 'categorie-test-seo' ) );
	$s = bigm_seo_page( true );
	$ok( $s && str_starts_with( $s['description'], 'Catégorie test SEO Big M' ), 'catégorie sans description : phrase générique' );
	wp_delete_term( $vide['term_id'], 'bigm_categorie' );
}
$ok( str_contains( (string) apply_filters( 'embed_oembed_html', '<iframe src="https://www.youtube.com/embed/abc"></iframe>', 'https://youtu.be/abc', array(), 0 ), 'youtube-nocookie.com/embed/abc' ), 'vidéos YouTube en mode confidentialité renforcée (pas de cookie avant la lecture)' );

WP_CLI::log( 'Performance' );
if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
	$f = apply_filters( 'image_editor_output_format', array(), 'a.jpg', 'image/jpeg' );
	$ok( 'image/webp' === ( $f['image/jpeg'] ?? '' ) && 'image/webp' === ( $f['image/png'] ?? '' ), 'tailles d’images en WebP (JPEG et PNG)' );
} else {
	WP_CLI::log( '  --    le serveur ne sait pas produire de WebP : tailles d’images laissées en JPEG / PNG' );
}
$ok( false === has_action( 'wp_head', 'print_emoji_detection_script' ), 'script des emojis retiré' );
require_once ABSPATH . 'wp-admin/includes/admin.php';
add_action( 'admin_print_scripts', 'print_emoji_detection_script' );
do_action( 'admin_init' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- crochet de WordPress.
$ok( false === has_action( 'admin_print_scripts', 'print_emoji_detection_script' ) && ! in_array( 'wpemoji', (array) apply_filters( 'tiny_mce_plugins', array( 'wpemoji', 'lists' ) ), true ), 'script des emojis retiré de l’admin aussi (icônes en emoji des extensions non cassées)' );
$page( array( 'page_id' => $test_page ) );
$a = $assets();
$ok( ! array_intersect( array( 'wp-block-library', 'classic-theme-styles', 'global-styles' ), $a ), 'page sans blocs : CSS des blocs non chargé' );

$slider  = wp_insert_post(
	array(
		'post_type'   => 'bigm_slider',
		'post_title'  => 'Slider programmé',
		'post_status' => 'publish',
	)
);
$posts[] = $slider;
$debut   = ( new DateTimeImmutable( '+2 days', wp_timezone() ) )->setTime( 10, 0 );
$fin     = $debut->modify( '+7 days' );
update_post_meta(
	$slider,
	'_bigm_slides',
	array(
		array(
			'type'  => 'image',
			'debut' => $debut->format( 'Y-m-d H:i' ),
			'fin'   => $fin->format( 'Y-m-d H:i' ),
		),
	)
);
bigm_programmer_vidages( $slider );
$ok( $debut->getTimestamp() + 60 === wp_next_scheduled( 'bigm_slides_changement', array( $slider, $debut->getTimestamp() ) ) && $fin->getTimestamp() + 60 === wp_next_scheduled( 'bigm_slides_changement', array( $slider, $fin->getTimestamp() ) ), 'slide programmée : cache vidé au début et à la fin' );
wp_update_post(
	array(
		'ID'          => $slider,
		'post_status' => 'draft',
	)
);
bigm_programmer_vidages( $slider );
$ok( ! wp_next_scheduled( 'bigm_slides_changement', array( $slider, $debut->getTimestamp() ) ), 'slider dépublié : vidages annulés' );

WP_CLI::log( 'Reprise des articles (sans contacter l’ancien site)' );
$source = 'https://ancien.example';
$n      = 0;
$texte  = bigm_reprise_texte( '<p>Bonjour</p><p>&nbsp;</p><iframe src="https://www.youtube.com/embed/abcdefghijk?rel=0"></iframe><script>alert(1)</script><p><img src="https://ancien.example/logo.png" alt="x"><img src="https://ailleurs.example/a.jpg" alt="y"></p>', $source, 0, $n );
$ok( str_contains( $texte, 'https://www.youtube.com/watch?v=abcdefghijk' ) && ! str_contains( $texte, 'iframe' ) && ! str_contains( $texte, 'alert' ) && ! str_contains( $texte, '&nbsp;' ), 'texte : vidéo YouTube en adresse seule, scripts et paragraphes vides retirés' );
$ok( ! str_contains( $texte, 'ancien.example/logo.png' ) && str_contains( $texte, 'ailleurs.example/a.jpg' ) && 0 === $n, 'images : hors /wp-content/uploads/ de l’ancien site retirées, autres sites laissés' );
$avait_presse = get_term_by( 'slug', 'presse', 'category' );
$article      = array(
	'id'        => 987654,
	'slug'      => 'article-de-reprise-test',
	'date'      => '2021-03-04T12:30:00',
	'date_gmt'  => '2021-03-04T11:30:00',
	'link'      => $source . '/article-de-reprise-test/',
	'title'     => array( 'rendered' => 'Big M &#8211; à la radio' ),
	'excerpt'   => array( 'rendered' => '<p>Résumé.</p>' ),
	'content'   => array( 'rendered' => '<p>Texte.</p>' ),
	'_embedded' => array(
		'wp:term' => array(
			array(
				array(
					'taxonomy' => 'category',
					'slug'     => 'radio',
					'name'     => 'Radio',
				),
			),
			array(
				array(
					'taxonomy' => 'post_tag',
					'slug'     => 'bondy',
					'name'     => 'Bondy',
				),
			),
		),
	),
);
$r            = bigm_reprise_article( $article, $source );
if ( $r['id'] ) {
	$posts[] = $r['id'];
}
$repris = get_post( $r['id'] );
$ok( 'cree' === $r['etat'] && $repris && 'Big M – à la radio' === $repris->post_title && '2021-03-04 12:30:00' === $repris->post_date && 'Résumé.' === $repris->post_excerpt, 'article : titre, date et résumé repris' );
$ok( has_term( 'presse', 'category', $r['id'] ) && has_tag( 'Radio', $r['id'] ) && has_tag( 'Bondy', $r['id'] ) && 987654 === (int) get_post_meta( $r['id'], '_bigm_ancien_id', true ), 'article : rubrique Presse, étiquettes et identifiant d’origine' );
$ok( 'deja' === bigm_reprise_article( $article, $source )['etat'], 'article déjà repris : pas de doublon' );
if ( ! $avait_presse && ( $t = get_term_by( 'slug', 'presse', 'category' ) ) ) {
	$termes[] = (int) $t->term_id;
}

if ( function_exists( 'bigm_demo_textes_legaux' ) ) {
	WP_CLI::log( 'Pages légales et jeu concours (démo)' );
	$textes = bigm_demo_textes_legaux();
	$liens  = function_exists( 'bigm_child_menus' ) ? wp_list_pluck( bigm_child_menus()['bigm-pied-legal']['liens'], 1 ) : array();
	$ok( array( 'mentions-legales', 'politique-de-confidentialite', 'cookies', 'jeu-concours' ) === array_keys( $textes ), 'quatre pages : mentions légales, confidentialité, cookies, jeu concours' );
	$ok( ! $liens || ! array_diff( array( '/mentions-legales/', '/politique-de-confidentialite/', '/cookies/' ), $liens ), 'mêmes adresses que les liens du pied de page' );
	$tout = wp_json_encode( $textes, JSON_UNESCAPED_UNICODE );
	$ok( str_contains( $tout, '884&nbsp;901&nbsp;448' ) && str_contains( $tout, 'Yaniss Ourabia' ) && str_contains( $tout, 'Hostinger' ) && ! str_contains( $tout, 'Gravatar' ), 'mentions de l’ancien site reprises (RCS, publication), hébergeur renseigné, plus de texte WordPress par défaut' );
	$ok( "Qui sommes-nous\u{202F}?" === bigm_demo_typo( 'Qui sommes-nous ?' ) && "«\u{202F}Nous rejoindre\u{202F}»" === bigm_demo_typo( '« Nous rejoindre »' ) && 'https://a.fr' === bigm_demo_typo( 'https://a.fr' ), 'typographie : espaces fines avant « ? » et dans les guillemets, adresses intactes' );
}

if ( defined( 'BIGM_SOCLE_VERSION' ) ) {
	WP_CLI::log( 'Sécurité (socle)' );
	$entetes = apply_filters( 'wp_headers', array( 'X-Pingback' => 'x' ) );
	$ok( 'nosniff' === ( $entetes['X-Content-Type-Options'] ?? '' ) && 'SAMEORIGIN' === ( $entetes['X-Frame-Options'] ?? '' ) && str_contains( $entetes['Permissions-Policy'] ?? '', 'geolocation=(self)' ) && isset( $entetes['Referrer-Policy'] ) && ! isset( $entetes['X-Pingback'] ), 'en-têtes de sécurité, sans X-Pingback' );
	$moi = get_current_user_id();
	wp_set_current_user( 0 );
	$routes_visiteur = rest_get_server()->get_routes();
	wp_set_current_user( $moi );
	$routes_admin = rest_get_server()->get_routes();
	$ok( ! isset( $routes_visiteur['/wp/v2/users'] ) && isset( $routes_admin['/wp/v2/users'] ), 'API : liste des comptes réservée aux personnes connectées' );
	$ok( ! isset( apply_filters( 'oembed_response_data', array( 'author_name' => 'a', 'author_url' => 'b' ), get_post( $test_page ), 600, 400 )['author_name'] ), 'aperçus oEmbed sans nom d’auteur' );
} else {
	WP_CLI::log( '  --    socle absent : en-têtes de sécurité non vérifiés' );
}

wp_reset_postdata();
foreach ( $posts as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $termes as $id ) {
	wp_delete_term( $id, 'category' );
}
foreach ( array( 'Radio', 'Bondy' ) as $nom ) {
	$t = get_term_by( 'name', $nom, 'post_tag' );
	if ( $t && 0 === (int) $t->count ) {
		wp_delete_term( $t->term_id, 'post_tag' );
	}
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'redirections, référencement, performance, reprise et sécurité : toutes les vérifications passent.' );
