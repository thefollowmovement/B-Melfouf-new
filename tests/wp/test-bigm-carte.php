<?php
/**
 * Test de la carte, des catégories et des fiches produit (étape 5) : widgets, filtres,
 * fiche produit, pages du thème. Sur un site de test avec Elementor, bigm-core et le thème Big M :
 *
 *   wp eval-file tests/wp/test-bigm-carte.php --user=<administrateur>
 *
 * Crée des catégories, des produits et une image, puis les supprime.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'bigm_rendu_grille_produits' ) ) {
	WP_CLI::error( 'Il faut Elementor et bigm-core (0.4 ou plus) actifs.' );
}

$echecs  = 0;
$posts   = array();
$termes  = array();
$ok      = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};
$rendu   = static function ( string $type, array $settings ): string {
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
// Simule la page demandée (archive, catégorie, produit).
$page    = static function ( array $query ): void {
	$GLOBALS['wp_query']     = new WP_Query( $query ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( $GLOBALS['wp_query']->is_singular ) {
		$GLOBALS['post'] = $GLOBALS['wp_query']->posts[0] ?? null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $GLOBALS['post'] );
	}
};
$terme   = static function ( string $nom, int $parent = 0, array $meta = array() ) use ( &$termes ): int {
	$t = wp_insert_term( $nom, 'bigm_categorie', array( 'parent' => $parent, 'slug' => sanitize_title( 'test-' . $nom ) ) );
	$id = is_wp_error( $t ) ? 0 : (int) $t['term_id'];
	foreach ( $meta as $k => $v ) {
		update_term_meta( $id, $k, $v );
	}
	$termes[] = $id;
	return $id;
};
$produit = static function ( string $titre, array $cats, array $meta = array(), string $statut = 'publish' ) use ( &$posts ): int {
	$id = wp_insert_post(
		array(
			'post_type'    => 'bigm_produit',
			'post_title'   => $titre,
			'post_status'  => $statut,
			'post_content' => 'Description de ' . $titre,
			'menu_order'   => count( $posts ),
		)
	);
	wp_set_object_terms( $id, $cats, 'bigm_categorie' );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	$posts[] = $id;
	return (int) $id;
};

WP_CLI::log( 'Widgets enregistrés' );
$types = array( 'bigm-barre-categories', 'bigm-entete-carte', 'bigm-grille-categories', 'bigm-bandeau-allergenes', 'bigm-grille-produits', 'bigm-produit-entete', 'bigm-fiche-produit', 'bigm-produits-associes' );
$wm    = \Elementor\Plugin::$instance->widgets_manager;
$ok( ! array_filter( $types, static fn( $t ) => ! $wm->get_widget_types( $t ) ), 'les 8 widgets de la carte et de la fiche produit' );

// Contenus de test : une catégorie « Tests » (titre de page « Nos tests ») avec deux sous-catégories.
$parent = $terme( 'Tests', 0, array( '_bigm_titre' => 'Nos tests', '_bigm_ordre' => 99 ) );
$sous_b = $terme( 'Sous B', $parent, array( '_bigm_ordre' => 2 ) );
$sous_a = $terme( 'Sous A', $parent, array( '_bigm_ordre' => 1 ) );
$vide   = $terme( 'Vide' );
$sans_produit = $terme( 'Sans produit' );
$assoc  = $produit( 'Associé', array( $vide ) );
wp_set_object_terms( $assoc, array(), 'bigm_categorie' );
$brouil = $produit( 'Brouillon', array(), array(), 'draft' );
$p1     = $produit(
	'Burger <b>test</b>',
	array( $sous_a ),
	array(
		'_bigm_viande'               => 'boeuf',
		'_bigm_badge'                => 'Nouveau',
		'_bigm_allergenes_contient'  => array( 'gluten', 'lait' ),
		'_bigm_allergenes_traces'    => array( 'sesame' ),
		'_bigm_ingredients'          => array( 'Pain', 'Steak' ),
		'_bigm_nutrition'            => array(
			'portion_g' => '250',
			'portion'   => array( 'kcal' => '600', 'sel' => '2,1' ),
			'100g'      => array( 'kcal' => '240' ),
		),
		'_bigm_associes'             => array( $assoc, $brouil ),
		'_bigm_lien_commande'        => 'https://commande.example/burger',
	)
);
// Une vraie image (GD) : photo du premier produit, reprise par la vignette de la catégorie.
$im = imagecreatetruecolor( 600, 600 );
imagefill( $im, 0, 0, imagecolorallocate( $im, 250, 92, 25 ) );
ob_start();
imagejpeg( $im );
$bits = wp_upload_bits( 'test-carte.jpg', null, (string) ob_get_clean() );
$img  = wp_insert_attachment(
	array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'test-carte',
	),
	$bits['file']
);
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $img, wp_generate_attachment_metadata( $img, $bits['file'] ) );
set_post_thumbnail( $p1, $img );
$p2     = $produit( 'Poulet test', array( $sous_b ), array( '_bigm_viande' => 'poulet', '_bigm_allergenes_contient' => array( 'oeufs' ) ) );
$p3     = $produit( 'Au parent', array( $parent ), array( '_bigm_viande' => 'boeuf' ) );
$p4     = $produit( 'Nouveau aussi', array( $sous_a, $vide ) );

WP_CLI::log( 'Catégorie principale et lien de retour' );
$ok( $sous_a === bigm_categorie_principale( $p4 )->term_id, 'catégorie principale : la sous-catégorie plutôt qu’une catégorie de premier niveau' );
$entete = bigm_rendu_produit_entete( array( 'produit' => $p1 ) );
$ok( str_contains( $entete, 'href="' . esc_url( get_term_link( $parent ) ) . '"' ) && str_contains( $entete, 'Revenir à nos tests' ), 'retour : vers la catégorie parente, « Revenir à nos tests »' );
$ok( str_contains( bigm_rendu_produit_entete( array( 'produit' => $assoc ) ), 'Revenir à la carte' ), 'retour : « Revenir à la carte » sans catégorie' );
$ok( str_contains( $entete, '<h1>Burger &lt;b&gt;test&lt;/b&gt;</h1>' ), 'nom du produit échappé' );
$ok( str_contains( $entete, 'href="https://commande.example/burger"' ) && str_contains( $entete, 'btn btn--outline' ), 'boutons : lien de commande du produit, restaurants' );
$ok( str_contains( $entete, 'fetchpriority="high"' ) && ! str_contains( $entete, 'loading="lazy"' ), 'photo du produit : chargée tout de suite, en priorité (LCP)' );

WP_CLI::log( 'Fiche produit' );
$fiche = bigm_rendu_fiche_produit( array( 'produit' => $p1 ) );
$ok( 3 === substr_count( $fiche, '<details' ) && 2 === substr_count( $fiche, '<details open>' ), 'trois sections, ingrédients et allergènes ouverts' );
$ok( str_contains( $fiche, '<ul class="ingredients"><li>Pain</li><li>Steak</li></ul>' ), 'ingrédients dans l’ordre' );
$ok( str_contains( $fiche, '<ul class="al-list"><li>Gluten</li><li>Lait</li></ul>' ) && str_contains( $fiche, '<ul class="al-list traces"><li>Sésame</li></ul>' ), 'allergènes : contient et traces' );
$ok( str_contains( $fiche, "1 portion (250\u{202F}g)" ) && 2 === substr_count( $fiche, '<tr><td>' ) && str_contains( $fiche, '<td>2,1</td><td>—</td>' ), 'nutrition : poids de la portion, lignes remplies seulement, tiret si vide' );
$ok( '' === bigm_rendu_fiche_produit( array( 'produit' => $p3 ) ), 'produit sans données : pas de bloc' );
$ok( 1 === substr_count( bigm_rendu_fiche_produit( array( 'produit' => $p2 ) ), '<details' ), 'produit avec allergènes seulement : une section' );
$assoc_html = bigm_rendu_produits_associes( array( 'produit' => $p1 ) );
$ok( 1 === preg_match_all( '/class="tile( has-video)?"/', $assoc_html ) && str_contains( $assoc_html, 'Associé' ), '« Ça va bien avec » : produits publiés seulement' );
$ok( '' === bigm_rendu_produits_associes( array( 'produit' => $p2 ) ), '« Ça va bien avec » : rien sans produit associé' );

WP_CLI::log( 'Grille de produits et filtres' );
$grille = bigm_rendu_grille_produits( array( 'terme' => $parent ) );
$ok( strpos( $grille, 'Au parent' ) < strpos( $grille, '>Sous A</h2>' ) && strpos( $grille, '>Sous A</h2>' ) < strpos( $grille, '>Sous B</h2>' ), 'groupes : produits du parent d’abord, puis les sous-catégories dans l’ordre' );
$ok( 4 === preg_match_all( '/class="product( has-video)?"/', $grille ) && str_contains( $grille, '<span class="badge">Nouveau</span>' ), 'vignettes : 4 produits, badge' );
$ok( str_contains( $grille, 'data-allergens="gluten lait"' ) && str_contains( $grille, 'data-meat="poulet"' ), 'vignette : allergènes contenus (sans les traces) et viande, pour le filtre' );
$ok( str_contains( $grille, 'data-meat-filter="tous"' ) && str_contains( $grille, 'data-meat-filter="boeuf"' ) && str_contains( $grille, 'data-meat-filter="poulet"' ) && ! str_contains( $grille, 'data-meat-filter="poisson"' ), 'filtre viande : Tous et les viandes présentes' );
$ok( 14 === substr_count( $grille, 'data-allergen="' ) && str_contains( $grille, 'data-empty' ) && str_contains( $grille, 'data-reset' ), 'filtre : 14 allergènes, message et réinitialisation' );
$seule = bigm_rendu_grille_produits( array( 'terme' => $sous_b ) );
$ok( ! str_contains( $seule, 'data-meat-filter' ) && str_contains( $seule, 'data-toggle-allergy' ) && ! str_contains( $seule, '<h2 id="t-' ), 'une seule viande : pas de boutons viande ; sans sous-catégorie : pas de titre de groupe' );
$ok( ! str_contains( $seule, 'id="allergenes"' ), 'ancre #allergenes : une seule fois par page' );
$ok( str_contains( bigm_rendu_grille_produits( array( 'terme' => $sans_produit ) ), 'Aucun produit dans cette catégorie' ), 'catégorie sans produit publié : message' );
$sans = bigm_rendu_grille_produits(
	array(
		'terme'   => $parent,
		'grouper' => false,
		'filtres' => false,
	)
);
$ok( ! str_contains( $sans, 'class="filters"' ) && ! str_contains( $sans, '<h2' ) && 4 === preg_match_all( '/class="product( has-video)?"/', $sans ), 'sans filtres ni groupes : une grille simple' );

WP_CLI::log( 'Carte' );
$barre = bigm_rendu_barre_categories(
	array(
		'courante'      => $parent,
		'masquer_vides' => true,
	)
);
$ok( str_contains( $barre, 'href="' . esc_url( get_term_link( $parent ) ) . '" aria-current="page"' ) && str_contains( $barre, 'Toute la carte' ), 'barre : catégorie courante allumée, lien « Toute la carte »' );
$ok( str_contains( $barre, '>Vide</a>' ) && ! str_contains( $barre, '>Sans produit</a>' ), 'barre : catégorie sans produit masquée' );
$grille_cat = $rendu(
	'bigm-grille-categories',
	array(
		'source'        => 'choisies',
		'masquer_vides' => '',
		'tuiles'        => array(
			array(
				'_id'       => 'a',
				'categorie' => (string) $vide,
				'libelle'   => 'Ma vignette',
			),
			array(
				'_id'       => 'b',
				'categorie' => (string) $parent,
				'libelle'   => '',
			),
		),
	)
);
$ok( strpos( $grille_cat, 'Ma vignette' ) < strpos( $grille_cat, '>Tests</span>' ) && str_contains( $grille_cat, 'style="--i:1"' ), 'grille de catégories : ordre choisi, libellé remplacé, décalage des apparitions' );
$ok( str_contains( $grille_cat, 'id="test-tests"' ) && str_contains( $grille_cat, '<img' ), 'vignette de catégorie : ancre, photo du premier produit à défaut d’image' );
$bandeau = $rendu( 'bigm-bandeau-allergenes', array( 'bouton' => 'Filtrer' ) );
$ok( str_contains( $bandeau, 'class="allergy-band"' ) && str_contains( $bandeau, esc_html( (string) bigm_reglage( 'allergenes_titre' ) ) ) && str_contains( $bandeau, '#allergenes"' ), 'bandeau des allergènes : textes des Réglages, lien vers le filtre' );

WP_CLI::log( 'Pages du thème' );
$page( array( 'post_type' => 'bigm_produit' ) );
$assets = bigm_page_assets();
$ok( in_array( 'bigm-carte', $assets['styles'], true ) && ! in_array( 'bigm-produits', $assets['styles'], true ), '/carte/ : styles de la carte, pas ceux des produits' );
$ok( str_starts_with( wp_get_document_title(), esc_html( (string) bigm_reglage( 'carte_titre' ) ) ), '/carte/ : titre de l’onglet « Notre carte »' );
$carte = bigm_page_carte();
$ok( str_contains( $carte, 'aria-current="page"' ) && str_contains( $carte, '<h1>' . esc_html( (string) bigm_reglage( 'carte_titre' ) ) . '</h1>' ) && str_contains( $carte, 'class="cat-grid"' ), '/carte/ : barre, titre des Réglages, grille' );
$page( array( 'bigm_categorie' => 'test-sous-a' ) );
$ok( in_array( 'bigm-filtres', bigm_page_assets()['scripts'], true ), 'catégorie : script des filtres' );
$cat_html = bigm_page_categorie( get_term( $sous_a, 'bigm_categorie' ) );
$ok( str_contains( $cat_html, 'href="' . esc_url( get_term_link( $parent ) ) . '" aria-current="true"' ), 'sous-catégorie : la catégorie parente reste allumée (aria-current="true")' );
$slider = wp_insert_post(
	array(
		'post_type'   => 'bigm_slider',
		'post_title'  => 'Test carte',
		'post_status' => 'publish',
	)
);
$posts[] = $slider;
update_post_meta( $slider, '_bigm_slides', array( array_merge( bigm_slide_vide(), array( 'image' => (int) get_post_thumbnail_id( $p1 ), 'alt' => 'Bannière' ) ) ) );
update_term_meta( $parent, '_bigm_slider', $slider );
$ok( $slider === bigm_slider_de_la_page(), 'bannières d’une sous-catégorie : celles de la catégorie parente' );
$page( array( 'p' => $p1, 'post_type' => 'bigm_produit' ) );
$ok( in_array( 'bigm-fiche', bigm_page_assets()['styles'], true ), 'fiche produit : styles de la fiche' );
$ok( str_contains( $rendu( 'bigm-fiche-produit', array( 'ouvertes' => array( 'nutrition' ) ) ), 'Valeurs nutritionnelles' ), 'widget fiche produit : produit de la page' );

WP_CLI::log( 'Réglages de la carte' );
$net = bigm_reglages_nettoyer(
	array(
		'carte_slider' => '999999',
		'carte_texte'  => "Ligne 1\nLigne <b>2</b>",
	)
);
$ok( 0 === $net['carte_slider'] && "Ligne 1\nLigne 2" === $net['carte_texte'], 'slider inexistant refusé, texte long nettoyé' );

wp_reset_postdata();
wp_delete_attachment( $img, true );
foreach ( $posts as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $termes as $id ) {
	wp_delete_term( $id, 'bigm_categorie' );
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'carte, catégories et fiches produit : toutes les vérifications passent.' );
