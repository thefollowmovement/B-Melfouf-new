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
$p3     = $produit( 'Au parent', array( $parent ), array( '_bigm_viande' => array( 'boeuf', 'poulet' ) ) );
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
$ok( str_contains( $grille, 'data-meat="boeuf poulet"' ) && str_contains( $grille, 'data-meat="poulet"' ), 'viandes : plusieurs sur un produit (Beef Chicken), une seule écrite en texte avant la 0.6.4' );
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

WP_CLI::log( 'Import de la carte (Produits → Importer la carte)' );
$fichier = bigm_import_donnees();
$ok( $fichier && count( $fichier['produits'] ) > 50 && isset( $fichier['produits'][0]['contient'], $fichier['produits'][0]['viandes'] ), 'fichier data/carte.json : produits avec allergènes et viandes' );
$cats_import = array(
	array(
		'slug'   => 'import-test-sous',
		'nom'    => 'Sous import',
		'parent' => 'import-test',
	),
	array(
		'slug'  => 'import-test',
		'nom'   => 'Import test',
		'titre' => 'Nos tests',
		'ordre' => 6,
		'trace' => 'M4 4h16v16H4z',
	),
);
$ok( 2 === bigm_import_categories( $cats_import ) && 0 === bigm_import_categories( $cats_import ), 'catégories : créées une fois, parents d’abord' );
$t_parent = get_term_by( 'slug', 'import-test', 'bigm_categorie' );
$t_sous   = get_term_by( 'slug', 'import-test-sous', 'bigm_categorie' );
array_push( $termes, (int) $t_sous->term_id, (int) $t_parent->term_id );
$ok( $t_sous->parent === $t_parent->term_id && 'Nos tests' === get_term_meta( $t_parent->term_id, '_bigm_titre', true ) && str_contains( (string) get_term_meta( $t_parent->term_id, '_bigm_icone', true ), '<svg' ), 'catégorie créée : parent, titre de page, icône' );
$fiche_import = array(
	'titre'      => 'Produit import test',
	'slug'       => 'produit-import-test',
	'categories' => array( 'import-test-sous' ),
	'viandes'    => array( 'poulet', 'boeuf', 'inconnue' ),
	'contient'   => array( 'gluten' ),
	'traces'     => array( 'gluten', 'lait' ),
	'statut'     => 'draft',
	'ordre'      => 4,
	'description' => 'Description du fichier.',
	'image'      => 'http://exemple.test/photo.jpg',
	'a_verifier' => 'viande à confirmer',
);
$r1      = bigm_import_produit( $fiche_import );
$posts[] = $r1['id'];
$d1      = bigm_produit( $r1['id'] );
$ok( array() === $d1['associes'], 'produit importé sans « Ça va bien avec » : aucun produit associé (pas lui-même)' );
$ok( 'cree' === $r1['etat'] && 'draft' === get_post_status( $r1['id'] ) && 4 === (int) get_post_field( 'menu_order', $r1['id'] ) && has_term( 'import-test-sous', 'bigm_categorie', $r1['id'] ), 'produit créé : brouillon, ordre, catégorie' );
$ok( array( 'boeuf', 'poulet' ) === $d1['viandes'] && array( 'gluten' ) === $d1['allergenes']['contient'] && array( 'lait' ) === $d1['allergenes']['traces'] && ! $r1['image'], 'viandes et allergènes (traces sans ce qui est déjà « contient »), photo refusée hors https' );
wp_update_post(
	array(
		'ID'           => $r1['id'],
		'post_title'   => 'Nom retouché',
		'post_content' => 'Texte retouché.',
	)
);
wp_set_object_terms( $r1['id'], array( (int) $t_parent->term_id ), 'bigm_categorie' );
$r2 = bigm_import_produit( array_merge( $fiche_import, array( 'contient' => array( 'soja' ), 'traces' => array(), 'description' => 'Autre description.' ) ) );
$d2 = bigm_produit( $r1['id'] );
$ok( 'maj' === $r2['etat'] && $r2['id'] === $r1['id'] && 'Nom retouché' === $d2['titre'] && str_contains( $d2['description'], 'Texte retouché.' ) && has_term( 'import-test', 'bigm_categorie', $r1['id'] ) && ! has_term( 'import-test-sous', 'bigm_categorie', $r1['id'] ), 'nouvel import : nom, texte et catégories retouchés gardés' );
$ok( array( 'soja' ) === $d2['allergenes']['contient'] && array() === $d2['allergenes']['traces'], 'nouvel import : allergènes du fichier (ils font foi)' );
$fiche_d = array(
	'titre'       => 'Produit description test',
	'slug'        => 'produit-description-test',
	'categories'  => array( 'import-test' ),
	'description' => 'Bacon de dinde.',
);
$rd      = bigm_import_produit( $fiche_d );
$posts[] = $rd['id'];
bigm_import_produit( array_merge( $fiche_d, array( 'description' => 'Bacon de bœuf.' ) ) );
$ok( 'Bacon de bœuf.' === get_post_field( 'post_content', $rd['id'] ), 'description d’un import précédent, pas retouchée : remplacée par celle du fichier' );
wp_update_post(
	array(
		'ID'           => $rd['id'],
		'post_content' => 'Texte écrit dans l’admin.',
	)
);
bigm_import_produit( array_merge( $fiche_d, array( 'description' => 'Autre texte du fichier.' ) ) );
$ok( 'Texte écrit dans l’admin.' === get_post_field( 'post_content', $rd['id'] ), 'description retouchée dans l’admin : gardée' );
bigm_import_produit( array_merge( $fiche_d, array( 'description' => 'Texte corrigé.', 'anciennes_descriptions' => array( 'Texte écrit dans l’admin.' ) ) ) );
$ok( 'Texte corrigé.' === get_post_field( 'post_content', $rd['id'] ), 'description listée dans « anciennes_descriptions » (import d’une version précédente) : remplacée' );
bigm_import_produit( array_merge( $fiche_d, array( 'ingredients' => array( 'Pain', '', 'Steak' ) ) ) );
$ok( array( 'Pain', 'Steak' ) === bigm_produit( $rd['id'] )['ingredients'], 'ingrédients du fichier : repris (lignes vides retirées)' );
bigm_import_produit( array_merge( $fiche_d, array( 'ingredients' => array( 'Pain puccia', 'Steak' ) ) ) );
$ok( array( 'Pain puccia', 'Steak' ) === bigm_produit( $rd['id'] )['ingredients'], 'ingrédients d’un import précédent, pas retouchés : remplacés' );
update_post_meta( $rd['id'], '_bigm_ingredients', array( 'Liste écrite dans l’admin' ) );
bigm_import_produit( array_merge( $fiche_d, array( 'ingredients' => array( 'Autre liste' ) ) ) );
$ok( array( 'Liste écrite dans l’admin' ) === bigm_produit( $rd['id'] )['ingredients'], 'ingrédients retouchés dans l’admin : gardés' );
$ok( ! array_key_exists( 'dinde', bigm_viandes() ) && array( 'boeuf', 'poulet', 'poisson', 'vegetarien' ) === array_keys( bigm_viandes() ), 'viandes : bœuf, poulet, poisson, végétarien (bacon de bœuf)' );
// Photos (sans réseau) : des images déjà dans la médiathèque, retrouvées par leur adresse d'origine.
$image_test = static function ( string $nom, string $origine ) use ( &$posts ): int {
	$gd = imagecreatetruecolor( 40, 40 );
	ob_start();
	imagejpeg( $gd );
	$b  = wp_upload_bits( $nom . '.jpg', null, (string) ob_get_clean() );
	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $nom,
		),
		$b['file']
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $b['file'] ) );
	if ( '' !== $origine ) {
		update_post_meta( $id, '_bigm_source_image', $origine );
	}
	return (int) $id;
};
$photo_a = $image_test( 'import-photo-a', 'https://images.example/a.jpg' );
$photo_b = $image_test( 'import-photo-b', 'https://images.example/b.jpg' );
$photo_c = $image_test( 'photo-du-client', '' );
$fiche_p = array(
	'titre'      => 'Produit photo test',
	'slug'       => 'produit-photo-test',
	'categories' => array( 'import-test' ),
	'image'      => 'https://images.example/a.jpg',
);
$rp      = bigm_import_produit( $fiche_p );
$posts[] = $rp['id'];
$ok( $rp['image'] && $photo_a === (int) get_post_thumbnail_id( $rp['id'] ), 'photo : image déjà importée retrouvée par son adresse d’origine, pas retéléchargée' );
bigm_import_produit( array_merge( $fiche_p, array( 'image' => 'https://images.example/b.jpg' ) ) );
$ok( $photo_b === (int) get_post_thumbnail_id( $rp['id'] ) && ! get_post( $photo_a ), 'nouvelle photo (Argenteuil au lieu de Bondy) : remplacée, l’ancienne supprimée car plus utilisée' );
set_post_thumbnail( $rp['id'], $photo_c );
bigm_import_produit( array_merge( $fiche_p, array( 'image' => 'https://images.example/b.jpg' ) ) );
$ok( $photo_c === (int) get_post_thumbnail_id( $rp['id'] ), 'photo choisie dans l’admin : gardée' );
wp_delete_attachment( $photo_b, true );
wp_delete_attachment( $photo_c, true );

$code    = bigm_import_produit(
	array(
		'titre'      => 'Z9 (nom à compléter)',
		'slug'       => 'z9-test',
		'categories' => array( 'import-test' ),
		'statut'     => 'draft',
	)
);
$posts[] = $code['id'];
bigm_import_produit(
	array(
		'titre'      => 'Z9 – Menu test',
		'slug'       => 'z9-test',
		'categories' => array( 'import-test-sous' ),
		'statut'     => 'publish',
		'badge'      => 'Nouveau',
	)
);
$ok( 'Z9 – Menu test' === get_the_title( $code['id'] ) && 'publish' === get_post_status( $code['id'] ) && has_term( 'import-test-sous', 'bigm_categorie', $code['id'] ) && 'Nouveau' === get_post_meta( $code['id'], '_bigm_badge', true ), 'nom provisoire (codes M1…) : nom, catégorie, statut et badge du nouveau fichier' );

WP_CLI::log( 'Import ciblé (POST bigm/v1/carte/importer)' );
$box_fichier = array_values( array_filter( $fichier['produits'], static fn( $p ): bool => in_array( 'box', (array) ( $p['categories'] ?? array() ), true ) ) );
$ok( array( 'big-box', 'm-box', 'box-noxx' ) === array_column( $box_fichier, 'slug' ) && in_array( 'box', array_column( $fichier['categories'], 'slug' ), true ), 'fichier : catégorie Box et ses 3 produits' );
$mamamia = array_column( array_filter( $fichier['produits'], static fn( $p ): bool => str_contains( (string) $p['slug'], 'mamamia' ) ), null, 'slug' );
$ok( 6 === count( $mamamia ) && 6 === count( $mamamia['mamamia-spicy']['ingredients'] ?? array() ) && array() === array_diff( $mamamia['mamamia-original']['contient'], $mamamia['menu-mamamia-original']['contient'] ) && array( 'menus-simples' ) === $mamamia['menu-mamamia-chicken']['categories'], 'fichier : gamme Mamamia, 3 burgers avec ingrédients et 3 menus simples (allergènes du burger repris)' );
$termes_avant = get_terms(
	array(
		'taxonomy'   => 'bigm_categorie',
		'hide_empty' => false,
		'fields'     => 'ids',
	)
);
$noxx_avant   = get_page_by_path( 'box-noxx', OBJECT, 'bigm_produit' );
$nb_avant     = array_sum( (array) wp_count_posts( 'bigm_produit' ) );
$admin_id     = get_current_user_id();
$requete      = new WP_REST_Request( 'POST', '/bigm/v1/carte/importer' );
$requete->set_body_params( array( 'produits' => array( 'box-noxx', 'produit-absent' ) ) );
wp_set_current_user( 0 );
$ok( 401 === rest_do_request( $requete )->get_status(), 'import ciblé : refusé sans compte administrateur' );
wp_set_current_user( $admin_id );
$reponse = rest_do_request( $requete );
$res     = (array) $reponse->get_data();
$noxx    = (int) ( $res['produits']['box-noxx']['id'] ?? 0 );
$d_noxx  = $noxx ? bigm_produit( $noxx ) : array();
$t_box   = get_term_by( 'slug', 'box', 'bigm_categorie' );
$ok( 200 === $reponse->get_status() && array( 'produit-absent' ) === $res['absents'] && 1 === count( $res['produits'] ) && $nb_avant + ( $noxx_avant ? 0 : 1 ) === array_sum( (array) wp_count_posts( 'bigm_produit' ) ), 'import ciblé : seulement le produit demandé, adresse inconnue signalée' );
$ok( $d_noxx && has_term( 'box', 'bigm_categorie', $noxx ) && has_term( 'nouveautes', 'bigm_categorie', $noxx ) && array( 'boeuf' ) === $d_noxx['viandes'] && array() === array_diff( $box_fichier[2]['contient'], $d_noxx['allergenes']['contient'] ) && count( $box_fichier[2]['contient'] ) === count( $d_noxx['allergenes']['contient'] ) && 'Nouveau' === get_post_meta( $noxx, '_bigm_badge', true ), 'Box Noxx : catégories Box et Nouveautés, viande, allergènes du fichier, badge' );
$ok( $t_box && 3 === (int) get_term_meta( $t_box->term_id, '_bigm_ordre', true ) && str_contains( (string) get_term_meta( $t_box->term_id, '_bigm_icone', true ), '<svg' ), 'catégorie Box créée : ordre 3, icône' );
if ( $noxx && ! $noxx_avant ) {
	$posts[] = $noxx;
}
$termes = array_merge(
	$termes,
	array_diff(
		get_terms(
			array(
				'taxonomy'   => 'bigm_categorie',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		),
		$termes_avant
	)
);

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
