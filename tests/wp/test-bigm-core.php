<?php
/**
 * Test de bout en bout du plugin bigm-core, sur un WordPress où il est actif.
 * À lancer sur un site de test (il crée des contenus puis les supprime) :
 *
 *   wp eval-file tests/wp/test-bigm-core.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}

$echecs  = 0;
$nettoie = array(
	'posts' => array(),
	'terms' => array(),
);

$ok = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};

$piece_jointe = static function ( string $nom, string $mime ) use ( &$nettoie ): int {
	$id                  = wp_insert_attachment(
		array(
			'post_title'     => $nom,
			'post_mime_type' => $mime,
			'guid'           => 'https://example.test/' . $nom,
		),
		$nom
	);
	$nettoie['posts'][] = $id;
	return (int) $id;
};

// Simule l'envoi d'un formulaire de l'admin.
$envoyer = static function ( array $post ): void {
	$_POST = wp_slash( $post );
};

if ( ! current_user_can( 'manage_options' ) ) {
	WP_CLI::error( 'Lancer avec --user=<administrateur>.' );
}

WP_CLI::log( 'Types de contenu et adresses' );
$ok( post_type_exists( 'bigm_produit' ) && post_type_exists( 'bigm_slider' ), 'bigm_produit et bigm_slider existent' );
$ok( taxonomy_exists( 'bigm_categorie' ) && is_taxonomy_hierarchical( 'bigm_categorie' ), 'bigm_categorie existe et est hiérarchique' );
$rules = (array) get_option( 'rewrite_rules' );
$ok( isset( $rules['carte/?$'] ), 'archive /carte/' );
$ok( (bool) preg_grep( '#^carte/\(\.\+\?\)/\?\$$#', array_keys( $rules ) ), 'catégories /carte/burgers/' );
$ok( (bool) preg_grep( '#^produit/#', array_keys( $rules ) ), 'fiches /produit/…/' );

WP_CLI::log( 'Catégories créées à l’activation' );
$top = bigm_categories();
$prototype = array( 'nouveautes', 'menus', 'burgers', 'poulet', 'tex-mex', 'petite-faim', 'desserts', 'boissons' );
$ok( array_values( array_intersect( wp_list_pluck( $top, 'slug' ), $prototype ) ) === $prototype, 'les 8 catégories, dans l’ordre du prototype (d’autres peuvent s’y ajouter, comme Wraps à l’import de la carte)' );
$burgers = get_term_by( 'slug', 'burgers', 'bigm_categorie' );
$ok( $burgers && array( 'boeuf', 'burgers-poulet' ) === wp_list_pluck( bigm_categories( (int) $burgers->term_id ), 'slug' ), 'Burgers a Bœuf et Poulet' );
$cat = $burgers ? bigm_categorie( $burgers ) : null;
$ok( $cat && 'Nos burgers' === $cat['titre'] && str_starts_with( $cat['icone'], '<svg' ), 'Burgers : titre « Nos burgers » et icône SVG' );
$ok( $cat && str_ends_with( untrailingslashit( $cat['url'] ), '/carte/burgers' ), 'adresse de Burgers : ' . ( $cat['url'] ?? '' ) );
$ok( (bool) get_term_by( 'slug', 'news', 'category' ) && (bool) get_term_by( 'slug', 'presse', 'category' ), 'catégories d’articles News et Presse' );
$ok( array() === bigm_core_seed(), 'pas de nouvelle création au second passage' );

WP_CLI::log( 'Icônes SVG' );
$svg = bigm_nettoyer_svg( '<svg onload="alert(1)" viewBox="0 0 24 24"><script>alert(1)</script><foreignObject><img src=x onerror=alert(1)></foreignObject><path d="M0 0h24" onclick="alert(1)"/></svg>' );
$ok( ! str_contains( $svg, 'alert' ) && str_contains( $svg, '<path d="M0 0h24"' ), 'script, foreignObject et attributs on* retirés, tracé gardé' );
$ok( str_contains( bigm_nettoyer_svg( 'M4 10a8 6 0 0 1 16 0z' ), 'stroke="currentColor"' ), 'un tracé seul est mis au format du prototype' );
$ok( '' === bigm_nettoyer_svg( 'javascript:alert(1)' ), 'texte qui n’est ni un SVG ni un tracé : refusé' );

WP_CLI::log( 'Fiche produit' );
$mp4    = $piece_jointe( 'test-tile.mp4', 'video/mp4' );
$webm   = $piece_jointe( 'test-tile.webm', 'video/webm' );
$poster = $piece_jointe( 'test-poster.jpg', 'image/jpeg' );
$frites = wp_insert_post(
	array(
		'post_type'   => 'bigm_produit',
		'post_title'  => 'Frites (test)',
		'post_status' => 'publish',
	)
);
$brouillon = wp_insert_post(
	array(
		'post_type'   => 'bigm_produit',
		'post_title'  => 'Brouillon (test)',
		'post_status' => 'draft',
	)
);
array_push( $nettoie['posts'], $frites, $brouillon );

$envoyer(
	array(
		'bigm_produit_nonce'       => wp_create_nonce( 'bigm_produit_save' ),
		'bigm_badge'               => ' Nouveau ',
		'bigm_viandes'             => array( 'poulet', 'boeuf', 'inconnue' ),
		'bigm_lien_commande'       => '',
		'bigm_ingredients'         => "Naan grillé\r\n\r\nSteak de bœuf smashé ×2\n<b>Cheddar</b> fondu ×2",
		'bigm_allergenes_contient' => array( 'gluten', 'lait', 'inconnu' ),
		'bigm_allergenes_traces'   => array( 'lait', 'sesame' ),
		'bigm_nutrition'           => array(
			'portion_g' => '210',
			'portion'   => array(
				'kcal' => '612',
				'sel'  => '2,4 g',
			),
			'100g'      => array( 'kcal' => '291' ),
		),
		'bigm_associes'            => array( $frites, $brouillon, 999999 ),
		'bigm_video'               => array(
			'mp4'    => $mp4,
			'webm'   => $mp4,
			'poster' => $poster,
		),
	)
);
$produit = wp_insert_post(
	array(
		'post_type'    => 'bigm_produit',
		'post_title'   => 'Smash Naan (test)',
		'post_status'  => 'publish',
		'post_content' => 'Deux steaks smashés.',
		'tax_input'    => array( 'bigm_categorie' => array( get_term_by( 'slug', 'boeuf', 'bigm_categorie' )->term_id ) ),
	)
);
$nettoie['posts'][] = $produit;
$p                  = bigm_produit( $produit );

$ok( 'Nouveau' === $p['badge'] && 'boeuf' === $p['viande'] && array( 'boeuf', 'poulet' ) === $p['viandes'], 'badge et viandes (plusieurs, dans l’ordre de la liste, sans valeur inconnue)' );
$ok( array( 'Naan grillé', 'Steak de bœuf smashé ×2', 'Cheddar fondu ×2' ) === $p['ingredients'], 'ingrédients : lignes vides et balises retirées, ordre gardé' );
$ok( array( 'gluten', 'lait' ) === $p['allergenes']['contient'], 'allergènes inconnus ignorés' );
$ok( array( 'sesame' ) === $p['allergenes']['traces'], 'un allergène coché deux fois reste dans « Contient »' );
$ok( array( 'Gluten', 'Lait' ) === $p['allergenes']['contient_labels'], 'libellés des allergènes' );
$ok( array( 'kcal', 'sel' ) === array_keys( $p['nutrition']['lignes'] ) && '2,4' === $p['nutrition']['lignes']['sel']['portion'], 'nutrition : lignes remplies seulement, unité retirée' );
$ok( array( $frites ) === $p['associes'], 'produits associés : publiés et existants seulement' );
$ok( $p['video'] && str_ends_with( $p['video']['mp4'], 'test-tile.mp4' ) && '' === $p['video']['webm'], 'vidéo : un MP4 dans le champ WebM est refusé' );
$ok( in_array( get_term_by( 'slug', 'boeuf', 'bigm_categorie' )->term_id, $p['categories_ids'], true ), 'catégorie Bœuf' );
$ok( in_array( $produit, wp_list_pluck( bigm_produits_de( (int) $burgers->term_id ), 'ID' ), true ), 'le produit de Bœuf apparaît dans Burgers' );

WP_CLI::log( 'Réglages et lien de commande' );
$avant = get_option( 'bigm_reglages', null );
update_option(
	'bigm_reglages',
	bigm_reglages_nettoyer(
		array(
			'commander_url'          => 'https://commande.example.test',
			'franchise_droit_entree' => '30 000',
			'franchise_duree'        => '5',
			'franchise_redevance_enseigne' => '5,5',
			'contact_email'          => 'pas un email',
		)
	)
);
$ok( 'https://commande.example.test' === bigm_lien_commande( $produit ), 'sans lien propre, le produit prend le lien général' );
update_post_meta( $produit, '_bigm_lien_commande', 'https://ubereats.example.test/smash' );
$ok( 'https://ubereats.example.test/smash' === bigm_lien_commande( $produit ), 'un lien propre au produit l’emporte' );
$ok( "30\u{202F}000\u{202F}€\u{00A0}HT" === bigm_reglage_texte( 'franchise_droit_entree' ), '« 30 000 » devient 30 000 € HT : ' . bigm_reglage_texte( 'franchise_droit_entree' ) );
$ok( "5,5\u{202F}%" === bigm_reglage_texte( 'franchise_redevance_enseigne' ), 'virgule décimale : ' . bigm_reglage_texte( 'franchise_redevance_enseigne' ) );
update_option( 'bigm_reglages', array_merge( (array) get_option( 'bigm_reglages' ), array( 'franchise_redevance_communication' => 2.3, 'franchise_formation' => 100 ) ) );
$ok( "2,3\u{202F}%" === bigm_reglage_texte( 'franchise_redevance_communication' ) && "100\u{202F}€\u{00A0}HT" === bigm_reglage_texte( 'franchise_formation' ), '2,3 % et 100 € HT, sans zéros en trop' );
$ok( "5\u{00A0}ans" === do_shortcode( '[bigm_reglage cle="franchise_duree"]' ), 'shortcode [bigm_reglage] : ' . do_shortcode( '[bigm_reglage cle="franchise_duree"]' ) );
$ok( '' === bigm_reglage( 'contact_email' ), 'e-mail invalide refusé' );
$ok( '' === do_shortcode( '[bigm_reglage cle="inconnu"]' ), 'clé inconnue : rien' );
null === $avant ? delete_option( 'bigm_reglages' ) : update_option( 'bigm_reglages', $avant );

WP_CLI::log( 'Slider et dates de programmation' );
$img = $piece_jointe( 'test-banniere.jpg', 'image/jpeg' );
$envoyer(
	array(
		'bigm_slider_nonce' => wp_create_nonce( 'bigm_slider_save' ),
		'bigm_slides'       => array(
			'0'       => array(
				'type'    => 'image',
				'image'   => $img,
				'alt'     => 'Toujours visible',
				'cadrage' => '50% 40%',
			),
			'n123'    => array(
				'type'  => 'image',
				'image' => $img,
				'alt'   => 'Programmée',
				'debut' => '2099-01-01T00:00',
			),
			'n456'    => array(
				'type' => 'image',
				'image' => $img,
				'alt'  => 'Terminée',
				'fin'  => '2020-01-01T00:00',
			),
			'n789'    => array(
				'type'   => 'video',
				'mp4'    => $mp4,
				'webm'   => $webm,
				'poster' => $poster,
				'alt'    => 'Vidéo',
				'lien'   => 'javascript:alert(1)',
				'cadrage' => 'n’importe quoi',
			),
			'vide'    => array(
				'type' => 'image',
				'alt'  => 'Sans média',
			),
			'__KEY__' => array(
				'type'  => 'image',
				'image' => $img,
			),
		),
	)
);
$slider             = wp_insert_post(
	array(
		'post_type'   => 'bigm_slider',
		'post_title'  => 'Slider (test)',
		'post_status' => 'publish',
	)
);
$nettoie['posts'][] = $slider;
$_POST              = array();
$enregistrees       = (array) get_post_meta( $slider, '_bigm_slides', true );
$actives            = bigm_slides_actives( $slider );

$ok( 4 === count( $enregistrees ), 'slide sans média et modèle __KEY__ non enregistrés' );
$ok( array( 'Toujours visible', 'Vidéo' ) === wp_list_pluck( $actives, 'alt' ), 'programmée et terminée masquées, ordre gardé' );
$ok( 'Programmée' === ( bigm_slides_actives( $slider, '2099-06-01 12:00' )[1]['alt'] ?? '' ), 'la slide programmée apparaît à sa date' );
$ok( '2099-01-01 00:00' === $enregistrees[1]['debut'], 'date enregistrée à l’heure du site' );
$ok( '' === $actives[1]['lien'], 'lien javascript: refusé' );
$ok( '50% 40%' === $actives[0]['cadrage'] && '50% 50%' === $actives[1]['cadrage'], 'cadrage gardé, ou 50% 50% par défaut' );
$ok( str_ends_with( $actives[1]['webm'], 'test-tile.webm' ) && str_ends_with( $actives[1]['poster'], 'test-poster.jpg' ), 'vidéo : WebM et image d’aperçu' );

WP_CLI::log( 'Champs d’une catégorie' );
$envoyer(
	array(
		'bigm_categorie_nonce' => wp_create_nonce( 'bigm_categorie_save' ),
		'bigm_cat'             => array(
			'titre'  => 'Nos tests',
			'ordre'  => '12',
			'icone'  => 'M3 3h18v18H3z',
			'image'  => $mp4,
			'slider' => $slider,
		),
	)
);
$t = wp_insert_term( 'Catégorie (test)', 'bigm_categorie' );
$_POST = array();
if ( ! is_wp_error( $t ) ) {
	$nettoie['terms'][] = (int) $t['term_id'];
	$c                  = bigm_categorie( (int) $t['term_id'] );
	$ok( 'Nos tests' === $c['titre'] && 12 === $c['ordre'] && $slider === $c['slider_id'], 'titre, ordre et bannières' );
	$ok( 0 === $c['image_id'], 'une vidéo dans le champ image est refusée' );
	$ok( str_contains( $c['icone'], 'M3 3h18v18H3z' ), 'icône à partir d’un tracé' );
} else {
	$ok( false, 'création de la catégorie de test : ' . $t->get_error_message() );
}

// Nettoyage.
foreach ( $nettoie['posts'] as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $nettoie['terms'] as $id ) {
	wp_delete_term( $id, 'bigm_categorie' );
}

WP_CLI::log( 'Réparation des données sérialisées (changement d’adresse)' );
global $wpdb;
$hote_rep  = (string) preg_replace( '/^(www|preprod|staging)\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
$slides_ok = array(
	array( 'type' => 'image', 'image' => 12, 'alt' => 'Boissons « fraîches » ; été', 'lien' => 'https://preprod.' . $hote_rep . '/carte/boissons/' ),
	array( 'type' => 'video', 'mp4' => 40, 'lien' => 'https://preprod.' . $hote_rep . '/produit/smash-naan/' ),
);
$casse_rep = str_replace( 'https://preprod.' . $hote_rep, 'https://' . $hote_rep, serialize( $slides_ok ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
$slider_rep = wp_insert_post( array( 'post_type' => 'bigm_slider', 'post_status' => 'publish', 'post_title' => 'Slider réparation test' ) );
$wpdb->insert( $wpdb->postmeta, array( 'post_id' => $slider_rep, 'meta_key' => '_bigm_slides', 'meta_value' => $casse_rep ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery
$wpdb->insert( $wpdb->postmeta, array( 'post_id' => $slider_rep, 'meta_key' => '_bigm_test_lisible', 'meta_value' => serialize( array( 'lien' => 'https://' . $hote_rep . '/' ) ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.SlowDBQuery, WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
wp_cache_flush();
$ok( ! is_array( get_post_meta( $slider_rep, '_bigm_slides', true ) ), 'slides abîmées par un remplacement d’adresse brut : illisibles' );
$essai = bigm_reparer_serialisations( false );
$ok( in_array( 'postmeta _bigm_slides (' . $slider_rep . ')', $essai['reparees'], true ) && ! is_array( get_post_meta( $slider_rep, '_bigm_slides', true ) ), 'essai : signalée, rien d’écrit' );
$cr_rep  = bigm_reparer_serialisations();
$lues    = get_post_meta( $slider_rep, '_bigm_slides', true );
$ok( is_array( $lues ) && 'https://' . $hote_rep . '/carte/boissons/' === $lues[0]['lien'] && 'Boissons « fraîches » ; été' === $lues[0]['alt'] && 40 === $lues[1]['mp4'], 'réparation : slides relues, liens et textes intacts' );
$ok( ! in_array( 'postmeta _bigm_test_lisible (' . $slider_rep . ')', $cr_rep['reparees'], true ) && array() === bigm_reparer_serialisations( false )['reparees'], 'valeur lisible jamais touchée ; une seconde passe ne trouve plus rien' );
$ok( null === bigm_serialise_repare( 'a:1:{s:3:"abc' ) && null === bigm_serialise_repare( 'texte simple' ) && null === bigm_serialise_repare( 'b:0;' ), 'valeur irréparable, non sérialisée ou « faux » : laissée telle quelle' );
wp_delete_post( $slider_rep, true );

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'bigm-core : toutes les vérifications passent.' );
