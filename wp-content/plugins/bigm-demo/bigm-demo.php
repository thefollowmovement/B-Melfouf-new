<?php
/**
 * Plugin Name:  Big M — contenu de démonstration
 * Description:  Importe les visuels et les contenus du prototype (sliders, produits, articles) et crée la page d'accueil Elementor. Temporaire : à supprimer avant la mise en ligne. Outils → Démo Big M.
 * Author:       The Follow Movement
 * Version:      0.4.0
 * Requires PHP: 8.2
 * Requires Plugins: bigm-core, elementor
 *
 * @package BigM\Demo
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_DEMO_VERSION', '0.4.0' );
define( 'BIGM_DEMO_MEDIAS', __DIR__ . '/medias/' );

/**
 * Textes alternatifs des visuels du prototype.
 *
 * @return array<string, string>
 */
function bigm_demo_alts(): array {
	return array(
		'app-phone.webp'              => 'L\'application Big M sur un iPhone',
		'b-restaurant-saint-ouen.jpg' => 'Le comptoir du Big M de Saint-Ouen',
		'b-franchise-poignee.jpg'     => 'Deux associés se serrent la main dans un restaurant Big M',
		'b-street-cola.jpg'           => 'Le Street Cola de Big M',
		'b-mhsc-maillot.jpg'          => 'Le logo Big M sur le maillot du MHSC',
		'b-chicken-gants.jpg'         => 'Un burger au poulet tenu par des mains gantées',
		'poster-grand.jpg'            => 'Affiche : certains les préfèrent grand',
		'poster-geant.jpg'            => 'Affiche : d\'autres les aiment géant',
		'poster-poulet.jpg'           => 'Affiche : la plupart au poulet',
		'poster-flamme.jpg'           => 'Affiche : et pour d\'autres à la flamme',
		'p-smashnaan.jpg'             => 'Le Smash Naan : deux steaks smashés, cheddar fondu et cornichons dans un naan grillé',
	);
}

/**
 * Importe un fichier du dossier medias/ dans la médiathèque (une seule fois).
 *
 * @param string $nom Nom du fichier.
 * @return int Identifiant du média, 0 en cas d'échec.
 */
function bigm_demo_media( string $nom ): int {
	$deja = (array) get_option( 'bigm_demo_medias', array() );
	if ( ! empty( $deja[ $nom ] ) && get_post( (int) $deja[ $nom ] ) ) {
		return (int) $deja[ $nom ];
	}
	$source = BIGM_DEMO_MEDIAS . $nom;
	if ( ! is_file( $source ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $nom );
	copy( $source, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => $nom,
			'tmp_name' => $tmp,
		),
		0,
		'[Démo] ' . $nom
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_bigm_demo', 1 );
	$alt = bigm_demo_alts()[ $nom ] ?? '';
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	$deja[ $nom ] = $id;
	update_option( 'bigm_demo_medias', $deja, false );
	return (int) $id;
}

/**
 * Réglage « média » d'Elementor pour un fichier importé.
 *
 * @param string $nom Nom du fichier.
 * @return array{id: int, url: string}
 */
function bigm_demo_el_media( string $nom ): array {
	$id = bigm_demo_media( $nom );
	return array(
		'id'  => $id,
		'url' => $id ? (string) wp_get_attachment_url( $id ) : '',
	);
}

/**
 * Crée un contenu marqué « démo ».
 *
 * @param array<string, mixed> $post Données de wp_insert_post.
 */
function bigm_demo_post( array $post ): int {
	$id = wp_insert_post( $post + array( 'post_status' => 'publish' ) );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	update_post_meta( $id, '_bigm_demo', 1 );
	return (int) $id;
}

/**
 * Slide au format de bigm-core.
 *
 * @param array<string, mixed> $s Valeurs.
 * @return array<string, mixed>
 */
function bigm_demo_slide( array $s ): array {
	return array_merge( bigm_slide_vide(), $s );
}

/**
 * Identifiant court pour Elementor.
 */
function bigm_demo_eid(): string {
	return substr( md5( uniqid( '', true ) ), 0, 7 );
}

/**
 * Importe tout. Renvoie le compte rendu.
 *
 * @return string[]
 */
function bigm_demo_importer(): array {
	if ( ! function_exists( 'bigm_slide_vide' ) || ! did_action( 'elementor/loaded' ) ) {
		return array( 'Il faut les extensions Big M — cœur et Elementor actives.' );
	}
	if ( ! is_dir( BIGM_DEMO_MEDIAS ) ) {
		return array( 'Dossier medias/ introuvable : utiliser le zip fabriqué par ops/build-zips.sh.' );
	}
	if ( get_option( 'bigm_demo_import' ) ) {
		return array( 'Le contenu de démonstration est déjà importé.' );
	}
	set_time_limit( 300 );
	$rapport = array();
	$url     = static fn( string $chemin ): string => home_url( $chemin );
	$cat     = static fn( string $slug ): int => ( $t = get_term_by( 'slug', $slug, 'bigm_categorie' ) ) ? (int) $t->term_id : 0; // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition

	// 1. Médias.
	$fichiers = array_merge( glob( BIGM_DEMO_MEDIAS . '*.{jpg,webp,png}', GLOB_BRACE ), glob( BIGM_DEMO_MEDIAS . '*.{mp4,webm}', GLOB_BRACE ) );
	$m        = array();
	foreach ( $fichiers as $f ) {
		$m[ basename( $f ) ] = bigm_demo_media( basename( $f ) );
	}
	$rapport[] = count( array_filter( $m ) ) . ' médias importés';
	$video     = static fn( string $base ): array => array(
		'mp4'    => $m[ "$base.mp4" ] ?? 0,
		'webm'   => $m[ "$base.webm" ] ?? 0,
		'poster' => $m[ "$base-poster.jpg" ] ?? 0,
	);

	// 2. Sliders.
	$nnbsp   = "\u{202F}";
	$sliders = array(
		'une'     => array(
			'Accueil — À la une',
			array(
				array( 'type' => 'video' ) + $video( 'hero-streetcola' ) + array(
					'alt'  => 'Big M, c\'est ça qu\'on aime : trois burgers au naan et le Street Cola',
					'lien' => $url( '/carte/boissons/' ),
				),
				array(
					'image'   => $m['b-offre-doubles.jpg'] ?? 0,
					'alt'     => 'Menu Doublés : deux burgers, frites et boisson',
					'lien'    => $url( '/carte/burgers/' ),
					'cadrage' => '50% 30%',
				),
				array(
					'image'   => $m['b-offre-texmex.jpg'] ?? 0,
					'alt'     => 'Tex-Mex : tenders, nuggets et frites',
					'lien'    => $url( '/carte/tex-mex/' ),
					'cadrage' => '50% 30%',
				),
				array(
					'image'   => $m['b-offre-menu-simple.jpg'] ?? 0,
					'alt'     => 'Menu Simple : smash naan, frites et boisson',
					'lien'    => $url( '/produit/smash-naan/' ),
					'cadrage' => '50% 30%',
				),
				array(
					'image'   => $m['b-franchise-poignee.jpg'] ?? 0,
					'alt'     => 'Nouveaux restaurants Big M ouverts en co-franchise',
					'lien'    => $url( '/#actus' ),
					'cadrage' => '50% 40%',
				),
				array(
					'image'   => $m['b-mhsc-maillot.jpg'] ?? 0,
					'alt'     => 'Big M, partenaire officiel du Montpellier Hérault SC',
					'lien'    => $url( '/#actus' ),
					'cadrage' => '50% 40%',
				),
			),
		),
		'carte'   => array(
			'Carte — Offres du moment',
			array(
				array( 'type' => 'video' ) + $video( 'banner-doubles' ) + array(
					'alt'  => 'Menu Doublés : deux burgers, frites et boisson',
					'lien' => $url( '/carte/burgers/' ),
				),
				array(
					'image' => $m['b-street-cola.jpg'] ?? 0,
					'alt'   => 'Le Street Cola de Big M',
					'lien'  => $url( '/carte/boissons/' ),
				),
				array(
					'image'   => $m['b-offre-texmex.jpg'] ?? 0,
					'alt'     => 'Nos Tex-Mex',
					'lien'    => $url( '/carte/tex-mex/' ),
					'cadrage' => '50% 55%',
				),
			),
		),
		'burgers' => array(
			'Burgers — bannières',
			array(
				array( 'type' => 'video' ) + $video( 'banner-doubles' ) + array(
					'alt'  => 'Menu Doublés : deux burgers, frites et boisson',
					'lien' => $url( '/carte/burgers/' ),
				),
				array(
					'image'   => $m['b-offre-menu-simple.jpg'] ?? 0,
					'alt'     => 'Menu Simple : smash naan, frites et boisson',
					'lien'    => $url( '/produit/smash-naan/' ),
					'cadrage' => '50% 60%',
				),
				array(
					'image' => $m['b-street-cola.jpg'] ?? 0,
					'alt'   => 'Le Street Cola',
					'lien'  => $url( '/carte/boissons/' ),
				),
			),
		),
	);
	$s_ids = array();
	foreach ( $sliders as $cle => $sl ) {
		$s_ids[ $cle ] = bigm_demo_post(
			array(
				'post_type'  => 'bigm_slider',
				'post_title' => $sl[0],
			)
		);
		update_post_meta( $s_ids[ $cle ], '_bigm_slides', array_map( 'bigm_demo_slide', $sl[1] ) );
	}
	if ( $cat( 'burgers' ) ) {
		update_term_meta( $cat( 'burgers' ), '_bigm_slider', $s_ids['burgers'] );
	}
	$rapport[] = '3 sliders (accueil, carte, Burgers)';

	// 3. Produits : ceux du prototype, avec leurs allergènes.
	$produits = array(
		array( 'Le Big M', 'p-bigm.jpg', 'boeuf', array( 'boeuf' ), 'gluten lait moutarde sesame oeufs', 'tile-bigm' ),
		array( 'Le Giant', 'p-giant.jpg', 'boeuf', array( 'boeuf' ), 'gluten lait moutarde sesame', '' ),
		array( 'Le Double Jumbo', 'p-jumbo.jpg', 'boeuf', array( 'boeuf' ), 'gluten lait moutarde sesame', 'tile-jumbo' ),
		array( 'Smash Naan', 'p-smashnaan.jpg', 'boeuf', array( 'boeuf', 'nouveautes' ), 'gluten lait moutarde', '' ),
		array( 'Le Tower', 'p-tower.jpg', 'poulet', array( 'burgers-poulet' ), 'gluten oeufs lait sesame moutarde', 'tile-tower' ),
		array( 'Chicken Burger', 'p-chicken.jpg', 'poulet', array( 'burgers-poulet' ), 'gluten oeufs lait sesame', '' ),
		array( 'Tenders', 'p-tenders.jpg', 'poulet', array( 'poulet', 'tex-mex' ), 'gluten', '' ),
		array( 'Nuggets', 'p-nuggets.jpg', 'poulet', array( 'poulet', 'petite-faim' ), 'gluten', '' ),
		array( 'Street Cola', 'p-streetcola.jpg', '', array( 'boissons' ), '', '' ),
		array( 'Frites', 'p-frites.jpg', '', array( 'petite-faim' ), '', '' ),
	);
	$p_ids = array();
	foreach ( $produits as $i => $p ) {
		$id = bigm_demo_post(
			array(
				'post_type'    => 'bigm_produit',
				'post_title'   => $p[0],
				'post_content' => '[Description à valider]',
				'menu_order'   => $i + 1,
			)
		);
		set_post_thumbnail( $id, $m[ $p[1] ] ?? 0 );
		wp_set_object_terms( $id, array_values( array_filter( array_map( $cat, $p[3] ) ) ), 'bigm_categorie' );
		update_post_meta( $id, '_bigm_viande', $p[2] );
		update_post_meta( $id, '_bigm_allergenes_contient', array_values( array_filter( explode( ' ', $p[4] ) ) ) );
		update_post_meta( $id, '_bigm_allergenes_traces', array() );
		if ( $p[5] ) {
			update_post_meta( $id, '_bigm_video', $video( $p[5] ) );
		}
		$p_ids[ $p[0] ] = $id;
	}
	$naan = $p_ids['Smash Naan'];
	wp_update_post(
		array(
			'ID'           => $naan,
			'post_content' => 'Deux steaks smashés sur la plaque, double cheddar fondu, cornichons et salade, le tout dans un naan grillé. [Description à valider]',
		)
	);
	update_post_meta( $naan, '_bigm_badge', 'Nouveau' );
	update_post_meta( $naan, '_bigm_ingredients', array( 'Naan grillé', 'Steak de bœuf smashé ×2', 'Cheddar fondu ×2', 'Cornichons', 'Salade', 'Sauce maison' ) );
	update_post_meta( $naan, '_bigm_allergenes_traces', array( 'sesame', 'oeufs', 'soja' ) );
	update_post_meta( $naan, '_bigm_associes', array( $p_ids['Frites'], $p_ids['Nuggets'], $p_ids['Street Cola'] ) );
	$rapport[] = count( $p_ids ) . ' produits';
	$rapport   = array_merge( $rapport, bigm_demo_carte( $s_ids['carte'] ), bigm_demo_restaurants() );
	if ( post_type_exists( 'tfmsl_store' ) ) {
		update_option( 'bigm_demo_restaurants', 1, false );
	}

	// 4. Vignettes des catégories (grille de la carte).
	foreach ( array(
		'nouveautes'  => array( 'p-smashnaan.jpg', '' ),
		'menus'       => array( 'p-menus.jpg', '' ),
		'burgers'     => array( 'p-bigm.jpg', 'tile-bigm' ),
		'poulet'      => array( 'p-tower.jpg', 'tile-tower' ),
		'tex-mex'     => array( 'p-tenders.jpg', '' ),
		'petite-faim' => array( 'p-nuggets.jpg', '' ),
		'boissons'    => array( 'p-streetcola.jpg', '' ),
	) as $slug => $v ) {
		if ( $cat( $slug ) ) {
			update_term_meta( $cat( $slug ), '_bigm_image', $m[ $v[0] ] ?? 0 );
			if ( $v[1] ) {
				update_term_meta( $cat( $slug ), '_bigm_video', $video( $v[1] ) );
			}
		}
	}

	// 5. Articles.
	foreach ( array(
		array( "France Snacking{$nnbsp}: «{$nnbsp}Ça vient d'ouvrir{$nnbsp}!{$nnbsp}»", 'presse', '2026-10-09 10:00:00', 'b-franchise-poignee.jpg' ),
		array( 'Big M révolutionne la street food avec son Street Cola', 'news', '2024-09-20 10:00:00', 'b-street-cola.jpg' ),
		array( 'Big M devient partenaire officiel du Montpellier HSC', 'news', '2023-11-15 10:00:00', 'b-mhsc-maillot.jpg' ),
	) as $a ) {
		$term = get_term_by( 'slug', $a[1], 'category' );
		$id   = bigm_demo_post(
			array(
				'post_type'     => 'post',
				'post_title'    => $a[0],
				'post_content'  => '[Article de démonstration : reprendre l’article de l’ancien site.]',
				'post_date'     => $a[2],
				'post_category' => $term ? array( (int) $term->term_id ) : array(),
			)
		);
		set_post_thumbnail( $id, $m[ $a[3] ] ?? 0 );
	}
	$rapport[] = '3 articles';

	// 6. Pages : accueil Elementor et page des articles.
	$item = static fn( array $s ): array => array( '_id' => bigm_demo_eid() ) + $s;
	$w    = static fn( string $type, array $s ): array => array(
		'id'         => bigm_demo_eid(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => $s,
		'elements'   => array(),
	);
	$zero = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '0',
		'bottom'   => '0',
		'left'     => '0',
		'isLinked' => true,
	);
	$data = array(
		array(
			'id'       => bigm_demo_eid(),
			'elType'   => 'container',
			'settings' => array(
				'content_width'  => 'full',
				'flex_direction' => 'column',
				'padding'        => $zero,
				'flex_gap'       => array(
					'unit'     => 'px',
					'size'     => 0,
					'column'   => '0',
					'row'      => '0',
					'isLinked' => true,
				),
			),
			'elements' => array(
				$w(
					'bigm-slider',
					array(
						'source'      => 'choisi',
						'slider'      => (string) $s_ids['une'],
						'format'      => 'hero',
						'libelle'     => 'L’actualité Big M',
						'titre_cache' => 'L’actualité Big M',
					)
				),
				$w( 'bigm-bande-appli', array( 'image' => bigm_demo_el_media( 'app-phone.webp' ) ) ),
				$w( 'bigm-restaurants', array( 'photo' => bigm_demo_el_media( 'b-restaurant-saint-ouen.jpg' ) ) ),
				$w(
					'bigm-services',
					array(
						'cartes' => array(
							$item(
								array(
									'image'      => bigm_demo_el_media( 'b-restaurant-saint-ouen.jpg' ),
									'cadrage'    => '30% 50%',
									'titre'      => 'Sur place',
									'texte'      => 'Installez-vous dans le Big M le plus proche.',
									'lien_texte' => 'Trouver un restaurant',
									'lien'       => array( 'url' => '/restaurants/' ),
								)
							),
							$item(
								array(
									'image'      => bigm_demo_el_media( 'b-offre-menu-simple.jpg' ),
									'titre'      => 'Click & collect',
									'texte'      => 'Commandez sur l\'appli, récupérez votre menu sans attendre.',
									'lien_texte' => 'Commander',
									'lien'       => array( 'url' => '/#appli' ),
								)
							),
							$item(
								array(
									'image'      => bigm_demo_el_media( 'b-street-cola.jpg' ),
									'titre'      => 'Livraison',
									'texte'      => 'Vos burgers livrés chez vous, avec les offres de l\'appli.',
									'lien_texte' => 'Se faire livrer',
									'lien'       => array( 'url' => '/#appli' ),
								)
							),
						),
					)
				),
				$w(
					'bigm-classiques',
					array(
						'source'   => 'choix',
						'produits' => array_map(
							static fn( string $nom ): array => $item( array( 'produit' => (string) $p_ids[ $nom ] ) ),
							array( 'Le Big M', 'Le Giant', 'Le Tower', 'Le Double Jumbo', 'Smash Naan', 'Chicken Burger', 'Tenders', 'Nuggets', 'Street Cola' )
						),
					)
				),
				$w(
					'bigm-affiches',
					array(
						'affiches' => array(
							$item(
								array(
									'annee'   => '1967',
									'image'   => bigm_demo_el_media( 'poster-grand.jpg' ),
									'legende' => 'Certains les préfèrent grand',
								)
							),
							$item(
								array(
									'annee'   => '1971',
									'image'   => bigm_demo_el_media( 'poster-geant.jpg' ),
									'legende' => 'D\'autres les aiment géant',
								)
							),
							$item(
								array(
									'annee'   => '1993',
									'image'   => bigm_demo_el_media( 'poster-poulet.jpg' ),
									'legende' => 'La plupart au poulet',
								)
							),
							$item(
								array(
									'annee'   => '1957',
									'image'   => bigm_demo_el_media( 'poster-flamme.jpg' ),
									'legende' => 'Et pour d\'autres, à la flamme',
								)
							),
						),
					)
				),
				$w( 'bigm-actualites', array() ),
				$w(
					'bigm-rejoindre',
					array(
						'franchise_image'   => bigm_demo_el_media( 'b-franchise-poignee.jpg' ),
						'recrutement_image' => bigm_demo_el_media( 'b-chicken-gants.jpg' ),
					)
				),
			),
		),
	);
	// Page d'accueil : celle d'un import précédent si elle existe encore (gardée par la suppression).
	$existante = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_demo_accueil', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	$accueil   = $existante ? (int) $existante[0] : wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'Accueil',
			'post_status' => 'publish',
		)
	);
	update_post_meta( $accueil, '_bigm_demo_accueil', 1 );
	update_post_meta( $accueil, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $accueil, '_elementor_edit_mode', 'builder' );
	update_post_meta( $accueil, '_elementor_template_type', 'wp-page' );
	update_post_meta( $accueil, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
	update_post_meta( $accueil, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	$page_actus = get_page_by_path( 'actualites' );
	$articles   = $page_actus ? (int) $page_actus->ID : wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => 'Actualités',
			'post_name'   => 'actualites',
			'post_status' => 'publish',
		)
	);
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $accueil );
	update_option( 'page_for_posts', $articles );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	$rapport[] = 'page d’accueil Elementor (page d’accueil du site) et page Actualités (page des articles)';
	$rapport   = array_merge( $rapport, bigm_demo_actualites() );
	update_option( 'bigm_demo_actualites', 1, false );

	update_option( 'bigm_demo_version', BIGM_DEMO_VERSION, false );
	update_option(
		'bigm_demo_import',
		array(
			'date'    => current_time( 'mysql' ),
			'accueil' => $accueil,
		),
		false
	);
	return $rapport;
}

/**
 * Carte (étape 5) : bannières de /carte/ dans Réglages Big M et chapeau de la catégorie Burgers,
 * repris du prototype. Ne remplace pas un réglage ou un texte déjà saisi.
 *
 * @param int $slider_carte Slider « Carte — Offres du moment » (0 : le retrouver).
 * @return string[] Rapport.
 */
function bigm_demo_carte( int $slider_carte = 0 ): array {
	$rapport = array();
	if ( ! $slider_carte ) {
		$ids          = get_posts(
			array(
				'post_type'      => 'bigm_slider',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'title'          => 'Carte — Offres du moment',
				'meta_key'       => '_bigm_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		$slider_carte = (int) ( $ids[0] ?? 0 );
	}
	if ( $slider_carte && ! (int) bigm_reglage( 'carte_slider' ) ) {
		// Tous les réglages sont renvoyés : le nettoyage vide ceux qui manquent.
		$reglages = array();
		foreach ( array_keys( bigm_reglages_definitions() ) as $cle ) {
			$reglages[ $cle ] = bigm_reglage( $cle );
		}
		$reglages['carte_slider'] = $slider_carte;
		update_option( 'bigm_reglages', $reglages );
		$rapport[] = 'bannières de la carte (Réglages Big M)';
	}
	// Un menu, pour que la vignette Menus du prototype apparaisse sur la carte.
	$menus  = get_term_by( 'slug', 'menus', 'bigm_categorie' );
	$medias = (array) get_option( 'bigm_demo_medias', array() );
	if ( $menus instanceof WP_Term && ! bigm_produits_de( $menus->term_id ) && ! empty( $medias['p-menus.jpg'] ) ) {
		$id = bigm_demo_post(
			array(
				'post_type'    => 'bigm_produit',
				'post_title'   => 'Menu Simple',
				'post_content' => 'Un smash naan, des frites et une boisson. [Description à valider]',
				'menu_order'   => 11,
			)
		);
		set_post_thumbnail( $id, (int) $medias['p-menus.jpg'] );
		wp_set_object_terms( $id, array( $menus->term_id ), 'bigm_categorie' );
		update_post_meta( $id, '_bigm_viande', 'boeuf' );
		$rapport[] = 'produit Menu Simple (catégorie Menus)';
	}
	$burgers = get_term_by( 'slug', 'burgers', 'bigm_categorie' );
	if ( $burgers instanceof WP_Term && '' === trim( $burgers->description ) ) {
		wp_update_term( $burgers->term_id, 'bigm_categorie', array( 'description' => "Bœuf ou poulet, simple ou double\u{202F}: les légendes du burger, 100\u{202F}% halal." ) );
		$rapport[] = 'texte de la catégorie Burgers';
	}
	return $rapport;
}

/**
 * Page Elementor créée une fois (retrouvée par son adresse), gardée par la suppression de la démo.
 *
 * @param string                           $slug     Adresse.
 * @param string                           $titre    Titre.
 * @param array<int, array<string, mixed>> $elements Widgets du conteneur : [ type, réglages ], ou éléments Elementor complets.
 * @return int Page (0 si elle existait déjà).
 */
function bigm_demo_page_elementor( string $slug, string $titre, array $elements ): int {
	if ( get_page_by_path( $slug ) ) {
		return 0;
	}
	$zero = array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => '0',
		'bottom'   => '0',
		'left'     => '0',
		'isLinked' => true,
	);
	$data = array(
		array(
			'id'       => bigm_demo_eid(),
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => array(
				'content_width' => 'full',
				'padding'       => $zero,
				'flex_gap'      => array(
					'unit'     => 'px',
					'size'     => 0,
					'column'   => '0',
					'row'      => '0',
					'isLinked' => true,
				),
			),
			'elements' => array_map(
				static fn( array $w ): array => isset( $w['elType'] ) ? $w : array(
					'id'         => bigm_demo_eid(),
					'elType'     => 'widget',
					'widgetType' => $w[0],
					'settings'   => $w[1],
					'elements'   => array(),
				),
				$elements
			),
		),
	);
	$id   = wp_insert_post(
		array(
			'post_type'   => 'page',
			'post_title'  => $titre,
			'post_name'   => $slug,
			'post_status' => 'publish',
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	return (int) $id;
}

/**
 * Restaurants (étape 6) : page « Restaurants » avec le store locator du plugin StoreLocatore,
 * et trois restaurants d'exemple, si le plugin est actif. Les adresses sont à fournir par le client.
 *
 * @return string[] Rapport.
 */
function bigm_demo_restaurants(): array {
	if ( ! post_type_exists( 'tfmsl_store' ) ) {
		return array();
	}
	$rapport = array();
	$deja    = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $deja ) {
		$medias = (array) get_option( 'bigm_demo_medias', array() );
		$photo  = (int) ( $medias['b-restaurant-saint-ouen.jpg'] ?? 0 );
		// Villes connues de Big M (Bondy, Saint-Ouen, siège à Villeneuve-la-Garenne), au centre-ville.
		foreach ( array(
			array( 'Big M Bondy', 'Bondy', '93140', '48.9022', '2.4828' ),
			array( 'Big M Saint-Ouen', 'Saint-Ouen-sur-Seine', '93400', '48.9119', '2.3337' ),
			array( 'Big M Villeneuve-la-Garenne', 'Villeneuve-la-Garenne', '92390', '48.9367', '2.3253' ),
		) as $r ) {
			$id = bigm_demo_post(
				array(
					'post_type'    => 'tfmsl_store',
					'post_title'   => $r[0] . ' [Démo]',
					'post_content' => '[Présentation du restaurant à fournir par le client]',
				)
			);
			foreach ( array(
				'tfmsl_address'      => '[Adresse à fournir]',
				'tfmsl_city'         => $r[1],
				'tfmsl_zip'          => $r[2],
				'tfmsl_country'      => 'France',
				'tfmsl_lat'          => $r[3],
				'tfmsl_lng'          => $r[4],
				'tfmsl_services'     => 'Sur place, Click & collect, Livraison',
				'tfmsl_cuisine_type' => 'Burgers halal',
			) as $cle => $valeur ) {
				update_post_meta( $id, $cle, $valeur );
			}
			if ( $photo ) {
				update_post_meta( $id, 'tfmsl_store_photo', $photo );
			}
		}
		$rapport[] = '3 restaurants d’exemple (adresses et horaires à fournir)';
	}
	$page = bigm_demo_page_elementor(
		'restaurants',
		'Nos restaurants',
		array(
			array(
				'bigm-entete-carte',
				array(
					'source' => 'manuel',
					'titre'  => 'Nos restaurants',
					'texte'  => "Sur place, en click & collect ou en livraison\u{202F}: trouvez le Big M le plus proche.",
					'bouton' => 'Commander',
				),
			),
			// Dans la largeur du site. Couleurs : habillage « Aux couleurs du site » du plugin (kit Elementor).
			bigm_demo_boite(
				array(
					'id'         => bigm_demo_eid(),
					'elType'     => 'widget',
					'widgetType' => 'tfmsl_store_locator',
					'settings'   => array(
						'title'              => '',
						'search_placeholder' => 'Ville, code postal…',
						'locator_height'     => array(
							'unit' => 'px',
							'size' => 640,
						),
						'popup_link_text'    => 'Voir le restaurant',
					),
					'elements'   => array(),
				)
			),
		)
	);
	if ( $page ) {
		$rapport[] = 'page Restaurants (store locator)';
	}
	return $rapport;
}

/**
 * Actualités (étape 6) : chapeau de la page Actualités, s'il est vide. Les catégories sous
 * /actualites/ sont réglées par bigm-core (inc/activation.php).
 *
 * @return string[] Rapport.
 */
function bigm_demo_actualites(): array {
	$rapport = array();
	$page    = (int) get_option( 'page_for_posts' );
	if ( $page && '' === trim( (string) get_post_field( 'post_content', $page ) ) ) {
		wp_update_post(
			array(
				'ID'           => $page,
				'post_content' => 'Ouvertures de restaurants, nouveautés de la carte et articles de presse : toute l’actualité de Big M.',
			)
		);
		$rapport[] = 'chapeau de la page Actualités';
	}
	return $rapport;
}

/**
 * Champ d'un formulaire Elementor Pro (repeater form_fields).
 *
 * @param string               $id    Identifiant (custom_id).
 * @param string               $type  Type (text, email, tel, textarea, radio, upload, honeypot…).
 * @param string               $label Libellé.
 * @param array<string, mixed> $extra Autres réglages (required, width, placeholder…).
 * @return array<string, mixed>
 */
function bigm_demo_champ( string $id, string $type, string $label, array $extra = array() ): array {
	return array_merge(
		array(
			'_id'          => bigm_demo_eid(),
			'custom_id'    => $id,
			'field_type'   => $type,
			'field_label'  => $label,
			'placeholder'  => '',
			'required'     => '',
			'width'        => '100',
			'width_mobile' => '100',
		),
		$extra
	);
}

/**
 * Formulaire Elementor Pro (widget « form ») : envoi par e-mail et enregistrement dans
 * Elementor → Soumissions, anti-spam (champ piège, reCAPTCHA v3 dès que ses clés sont saisies),
 * consentement obligatoire.
 *
 * @param string                           $nom    Nom du formulaire.
 * @param string                           $sujet  Sujet de l'e-mail.
 * @param string                           $bouton Texte du bouton.
 * @param array<int, array<string, mixed>> $champs Champs propres au formulaire.
 * @return array<string, mixed> Élément Elementor.
 */
function bigm_demo_formulaire( string $nom, string $sujet, string $bouton, array $champs ): array {
	$requis = array( 'required' => 'true' );
	$champs = array_merge(
		$champs,
		array(
			bigm_demo_champ( 'site_web', 'honeypot', 'Site web' ),
			bigm_demo_champ( 'recaptcha', 'recaptcha_v3', 'reCAPTCHA', array( 'recaptcha_badge' => 'bottomleft' ) ),
			bigm_demo_champ(
				'consentement',
				'acceptance',
				'Consentement',
				$requis + array( 'acceptance_text' => 'J’accepte que Big M utilise ces informations pour étudier ma candidature et me recontacter. En savoir plus : <a href="' . esc_url( home_url( '/politique-de-confidentialite/' ) ) . '">politique de confidentialité</a>.' )
			),
		)
	);
	$email  = function_exists( 'bigm_reglage' ) ? (string) bigm_reglage( 'contact_email' ) : '';
	return array(
		'id'         => bigm_demo_eid(),
		'elType'     => 'widget',
		'widgetType' => 'form',
		'settings'   => array(
			'form_name'          => $nom,
			'form_fields'        => $champs,
			'show_labels'        => 'true',
			'mark_required'      => 'yes',
			'input_size'         => 'md',
			'column_gap'         => array(
				'unit' => 'px',
				'size' => 16,
			),
			'row_gap'            => array(
				'unit' => 'px',
				'size' => 18,
			),
			'button_text'        => $bouton,
			'button_size'        => 'md',
			'button_width'       => '',
			'button_align'       => 'start',
			'submit_actions'     => array( 'email', 'save-to-database' ),
			'email_to'           => $email ? $email : (string) get_option( 'admin_email' ),
			'email_subject'      => $sujet,
			'email_content'      => '[all-fields]',
			'email_from_name'    => 'Site Big M',
			'email_reply_to'     => 'email',
			'email_content_type' => 'html',
			'custom_messages'    => 'yes',
			'success_message'    => "Merci\u{202F}! Votre candidature est bien envoyée\u{202F}: nous revenons vers vous rapidement.",
			'error_message'      => 'L’envoi n’a pas abouti. Réessayez dans un instant.',
		) + bigm_demo_style_formulaire(),
		'elements'   => array(),
	);
}

/**
 * Réglages de style du widget Formulaire aux couleurs de la charte. Sans eux, Elementor applique
 * les couleurs et typographies globales du kit et un fond de champ blanc.
 * Complété par assets/css/formulaires.css de bigm-core (hauteur des champs, focus, consentement).
 *
 * @return array<string, mixed>
 */
function bigm_demo_style_formulaire(): array {
	$px    = static fn( float $taille ): array => array(
		'unit' => 'px',
		'size' => $taille,
	);
	$cotes = static fn( string $v ): array => array(
		'unit'     => 'px',
		'top'      => $v,
		'right'    => $v,
		'bottom'   => $v,
		'left'     => $v,
		'isLinked' => true,
	);
	$typo  = static fn( string $prefixe, string $famille, string $graisse, float $taille ): array => array(
		"{$prefixe}_typography"  => 'custom',
		"{$prefixe}_font_family" => $famille,
		"{$prefixe}_font_weight" => $graisse,
		"{$prefixe}_font_size"   => $px( $taille ),
	);
	return array(
		'label_color'                   => '#2b1406',
		'label_spacing'                 => $px( 6 ),
		'mark_required_color'           => '#c8400a',
		'field_text_color'              => '#2b1406',
		'field_background_color'        => '#faf8f5',
		'field_border_color'            => 'rgba(43, 20, 6, 0.22)',
		'field_border_width'            => $cotes( '1.5' ),
		'field_border_radius'           => $cotes( '12' ),
		'button_background_color'       => '#c8400a',
		'button_text_color'             => '#ffffff',
		'button_background_hover_color' => '#a93506',
		'button_hover_color'            => '#ffffff',
		'button_border_radius'          => $cotes( '12' ),
		'success_message_color'         => '#1d6b3a',
		'error_message_color'           => '#c8400a',
		'inline_message_color'          => '#c8400a',
	) + $typo( 'label', 'Figtree', '600', 15 ) + $typo( 'field', 'Figtree', '400', 17 ) + $typo( 'button', 'Fredoka', '600', 17 ) + $typo( 'message', 'Figtree', '600', 16 );
}

/**
 * Carte blanche (conteneur « en boîte », classe bigm-formulaire) qui porte un formulaire.
 *
 * @param array<string, mixed> $formulaire Widget formulaire.
 * @return array<string, mixed> Élément Elementor.
 */
function bigm_demo_carte_formulaire( array $formulaire ): array {
	return bigm_demo_boite( $formulaire, 'bigm-formulaire' );
}

/**
 * Conteneur « en boîte » de la largeur du site (1240 px), avec la marge latérale des sections
 * Big M, autour d'un élément qui n'a pas sa propre largeur (widget d'une autre extension).
 *
 * @param array<string, mixed> $element Élément Elementor.
 * @param string               $classe  Classe CSS du conteneur.
 * @return array<string, mixed> Élément Elementor.
 */
function bigm_demo_boite( array $element, string $classe = '' ): array {
	$cote = static fn( int $px ): array => array(
		'unit'     => 'px',
		'top'      => '0',
		'right'    => (string) $px,
		'bottom'   => '0',
		'left'     => (string) $px,
		'isLinked' => false,
	);
	return array(
		'id'       => bigm_demo_eid(),
		'elType'   => 'container',
		'isInner'  => true,
		'settings' => array(
			'content_width'  => 'boxed',
			'boxed_width'    => array(
				'unit' => 'px',
				'size' => 1240,
			),
			'padding'        => $cote( 32 ),
			'padding_tablet' => $cote( 24 ),
			'padding_mobile' => $cote( 16 ),
			'css_classes'    => $classe,
		),
		'elements' => array( $element ),
	);
}

/**
 * Formulaires (étape 6) : pages « Devenir franchisé » et « Recrutement », avec leurs formulaires
 * Elementor Pro (brief, § 9). Les textes entre crochets sont à reprendre de l'ancien site.
 *
 * @param bool $sans_pro Créer les pages même sans Elementor Pro (tests).
 * @return string[] Rapport.
 */
function bigm_demo_formulaires( bool $sans_pro = false ): array {
	if ( ! $sans_pro && ! class_exists( '\ElementorPro\Plugin' ) ) {
		return array();
	}
	$nnbsp   = "\u{202F}";
	$requis  = array( 'required' => 'true' );
	$moitie  = array( 'width' => '50' );
	$rapport = array();

	$franchise = bigm_demo_page_elementor(
		'devenir-franchise',
		'Devenir franchisé',
		array(
			array(
				'bigm-entete-carte',
				array(
					'source' => 'manuel',
					'titre'  => 'Devenir franchisé',
					'texte'  => 'Créez, transformez ou reprenez un restaurant Big M avec un concept clé en main, une formation complète et des campagnes TV.',
					'bouton' => 'Déposer ma candidature',
					'lien'   => array( 'url' => '#candidature' ),
				),
			),
			array( 'bigm-chiffres', array( 'titre' => 'Les chiffres clés' ) ),
			array(
				'bigm-texte',
				array(
					'titre' => 'Pourquoi Big M',
					'texte' => '<p>[Présentation de la franchise : reprendre le texte de la page actuelle, bigm-burger.fr/devenir-franchise/.]</p>',
					'fond'  => 'yes',
				),
			),
			array(
				'bigm-texte',
				array(
					'titre' => 'Votre candidature',
					'texte' => "<p>Parlez-nous de votre projet{$nnbsp}: notre équipe franchise étudie chaque candidature et vous recontacte.</p>",
					'ancre' => 'candidature',
				),
			),
			bigm_demo_carte_formulaire(
				bigm_demo_formulaire(
					'Candidature franchise',
					'Candidature franchise : [field id="prenom"] [field id="nom"]',
					'Envoyer ma candidature',
					array(
						bigm_demo_champ( 'nom', 'text', 'Nom', $requis + $moitie ),
						bigm_demo_champ( 'prenom', 'text', 'Prénom', $requis + $moitie ),
						bigm_demo_champ( 'email', 'email', 'E-mail', $requis + $moitie ),
						bigm_demo_champ( 'telephone', 'tel', 'Téléphone', $requis + $moitie + array( 'placeholder' => '0612345678' ) ),
						bigm_demo_champ( 'zone', 'text', 'Zone géographique souhaitée', $requis + array( 'placeholder' => 'Ex. Île-de-France, Lyon et sa région' ) ),
						bigm_demo_champ( 'ville', 'text', 'Département ou ville', $requis + $moitie ),
						bigm_demo_champ( 'adresse', 'text', 'Adresse du local, si vous l’avez déjà', $moitie ),
						bigm_demo_champ( 'situation', 'text', 'Situation actuelle', $requis + array( 'placeholder' => 'Ex. salarié(e), chef d’entreprise, franchisé(e)' ) ),
						bigm_demo_champ( 'experience', 'textarea', 'Métier et expériences', $requis + array( 'rows' => 4 ) ),
						bigm_demo_champ(
							'apport',
							'radio',
							'Apport personnel, hors emprunt',
							$requis + array(
								'field_options' => "De 130 000 à 150 000 €\nDe 150 000 à 200 000 €\nPlus de 200 000 €",
								'inline_list'   => 'elementor-subgroup-inline',
							)
						),
						bigm_demo_champ(
							'delai',
							'radio',
							'Délai du projet',
							$requis + array(
								'field_options' => "3 mois\n6 mois\n12 mois et plus",
								'inline_list'   => 'elementor-subgroup-inline',
							)
						),
						bigm_demo_champ( 'questions', 'textarea', 'Vos questions', array( 'rows' => 4 ) ),
					)
				)
			),
		)
	);
	if ( $franchise ) {
		$rapport[] = 'page Devenir franchisé (chiffres, formulaire)';
	}

	$recrutement = bigm_demo_page_elementor(
		'recrutement',
		'Recrutement',
		array(
			array(
				'bigm-entete-carte',
				array(
					'source' => 'manuel',
					'titre'  => "Recrutez-nous{$nnbsp}!",
					'texte'  => "Équipiers, managers, formateurs{$nnbsp}: rejoignez Big M près de chez vous.",
					'bouton' => 'Postuler',
					'lien'   => array( 'url' => '#candidature' ),
				),
			),
			array(
				'bigm-texte',
				array(
					'titre' => 'Travailler chez Big M',
					'texte' => '<p>[Présentation des métiers et de l’équipe : reprendre le texte de l’ancien site.]</p>',
					'fond'  => 'yes',
				),
			),
			array(
				'bigm-texte',
				array(
					'titre' => 'Votre candidature',
					'texte' => "<p>Envoyez-nous votre CV et votre lettre de motivation{$nnbsp}: nous revenons vers vous rapidement.</p>",
					'ancre' => 'candidature',
				),
			),
			bigm_demo_carte_formulaire(
				bigm_demo_formulaire(
					'Candidature spontanée',
					'Candidature : [field id="prenom"] [field id="nom"]',
					'Envoyer ma candidature',
					array(
						bigm_demo_champ( 'prenom', 'text', 'Prénom', $requis + $moitie ),
						bigm_demo_champ( 'nom', 'text', 'Nom', $requis + $moitie ),
						bigm_demo_champ( 'villes', 'text', 'Ville(s) où vous pouvez travailler', $requis + array( 'placeholder' => 'Ex. Bondy, Saint-Ouen' ) ),
						bigm_demo_champ( 'adresse', 'text', 'Adresse', $requis ),
						bigm_demo_champ( 'telephone', 'tel', 'Téléphone', $requis + $moitie + array( 'placeholder' => '0612345678' ) ),
						bigm_demo_champ( 'email', 'email', 'E-mail', $moitie ),
						bigm_demo_champ(
							'fichiers',
							'upload',
							'CV et lettre de motivation (jusqu’à 3 fichiers PDF ou DOCX, 5 Mo chacun)',
							$requis + array(
								'allow_multiple_upload' => 'yes',
								'max_files'             => 3,
								'file_sizes'            => 5,
								'file_types'            => 'pdf,docx',
								// Joints à l'e-mail puis effacés du serveur : les CV ne restent pas en ligne.
								'attachment_type'       => 'attach',
							)
						),
						bigm_demo_champ( 'message', 'textarea', 'Message', array( 'rows' => 4 ) ),
					)
				)
			),
		)
	);
	if ( $recrutement ) {
		$rapport[] = 'page Recrutement (formulaire avec envoi de CV)';
	}
	return $rapport;
}

/*
 * Mise à jour d'une démo déjà importée avec une version précédente de l'extension.
 */
add_action(
	'admin_init',
	static function () {
		if ( ! current_user_can( 'manage_options' ) || ! get_option( 'bigm_demo_import' ) || ! function_exists( 'bigm_reglage' ) ) {
			return;
		}
		if ( version_compare( (string) get_option( 'bigm_demo_version', '0.1.0' ), BIGM_DEMO_VERSION, '<' ) ) {
			bigm_demo_carte();
			update_option( 'bigm_demo_version', BIGM_DEMO_VERSION, false );
		}
		if ( ! get_option( 'bigm_demo_actualites' ) ) {
			bigm_demo_actualites();
			update_option( 'bigm_demo_actualites', 1, false );
		}
		// Les pages Franchise et Recrutement à formulaire Elementor Pro ne sont plus créées
		// d'office : elles se créent par un bouton (Outils → Démo Big M), si on les veut.
		// Restaurants : dès que le plugin StoreLocatore est actif, une fois.
		if ( post_type_exists( 'tfmsl_store' ) && ! get_option( 'bigm_demo_restaurants' ) ) {
			bigm_demo_restaurants();
			update_option( 'bigm_demo_restaurants', 1, false );
		}
	}
);

/**
 * Supprime le contenu de démonstration (produits, sliders, articles, médias).
 * Les pages Accueil et Actualités restent.
 *
 * @return string[]
 */
function bigm_demo_supprimer(): array {
	$ids = get_posts(
		array(
			'post_type'      => array( 'bigm_produit', 'bigm_slider', 'post', 'attachment', 'tfmsl_store' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	$supprimes = 0;
	$gardes    = 0;
	// Contenus, puis médias : un média encore utilisé hors de la démo (page Elementor,
	// texte, image à la une, réglage) est gardé.
	usort( $ids, static fn( int $a, int $b ): int => ( 'attachment' === get_post_type( $a ) ) <=> ( 'attachment' === get_post_type( $b ) ) );
	foreach ( $ids as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			if ( bigm_demo_media_utilise( $id ) ) {
				delete_post_meta( $id, '_bigm_demo' );
				++$gardes;
				continue;
			}
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
		++$supprimes;
	}
	delete_option( 'bigm_demo_medias' );
	delete_option( 'bigm_demo_import' );
	$rapport = array( $supprimes . ' contenus et médias de démonstration supprimés (produits, sliders, articles, restaurants d’exemple). Les pages sont gardées.' );
	if ( $gardes ) {
		$rapport[] = $gardes . ' médias gardés : ils servent encore dans des pages (à remplacer par ceux du client).';
	}
	return $rapport;
}

/**
 * Un média de la démo est-il encore utilisé hors de la démo : dans le contenu ou les données
 * Elementor d'une page, d'un article ou d'un modèle, en image à la une, ou dans un réglage ?
 *
 * @param int $id Média.
 */
function bigm_demo_media_utilise( int $id ): bool {
	global $wpdb;
	$fichier = wp_basename( (string) get_attached_file( $id ) );
	$nom     = preg_replace( '/\.[a-z0-9]+$/i', '', $fichier ); // Couvre aussi les tailles (nom-300x200.jpg).
	if ( '' === $nom ) {
		return false;
	}
	$demo  = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_bigm_demo'";
	$like  = '%' . $wpdb->esc_like( $nom ) . '%';
	$texte = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status <> 'trash' AND post_type NOT IN ('attachment','revision') AND post_content LIKE %s AND ID NOT IN ($demo)", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$meta  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_status <> 'trash' AND p.post_type <> 'revision' AND m.meta_key = '_elementor_data' AND m.meta_value LIKE %s AND m.post_id NOT IN ($demo)", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$une   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d AND post_id NOT IN ($demo)", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	$logo  = in_array( $id, array( (int) get_theme_mod( 'custom_logo' ), (int) get_option( 'site_icon' ) ), true );
	return $texte || $meta || $une || $logo;
}

/*
 * Page Outils → Démo Big M.
 */
add_action(
	'admin_menu',
	static function () {
		add_management_page( 'Démo Big M', 'Démo Big M', 'manage_options', 'bigm-demo', 'bigm_demo_page' );
	}
);

/**
 * Page de la démo.
 */
function bigm_demo_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$rapport = array();
	if ( isset( $_POST['bigm_demo'] ) && check_admin_referer( 'bigm_demo' ) ) {
		$action  = sanitize_key( wp_unslash( $_POST['bigm_demo'] ) );
		$rapport = 'supprimer' === $action ? bigm_demo_supprimer() : ( 'formulaires' === $action ? bigm_demo_formulaires() : bigm_demo_importer() );
		if ( 'formulaires' === $action && ! $rapport ) {
			$rapport = array( 'Rien à créer : Elementor Pro est inactif, ou les pages devenir-franchise et recrutement existent déjà.' );
		}
	}
	$import = get_option( 'bigm_demo_import' );
	?>
	<div class="wrap">
		<h1>Démo Big M</h1>
		<?php if ( $rapport ) : ?>
			<div class="notice notice-info"><ul style="list-style:disc;padding-left:20px"><?php foreach ( $rapport as $l ) : ?><li><?php echo esc_html( $l ); ?></li><?php endforeach; ?></ul></div>
		<?php endif; ?>
		<p>Importe les visuels et les contenus du prototype : sliders, produits avec allergènes, articles, et la page d'accueil Elementor avec les widgets Big M. Les contenus marqués [Démo] ou « à valider » sont à remplacer par ceux du client.</p>
		<form method="post">
			<?php wp_nonce_field( 'bigm_demo' ); ?>
			<?php if ( $import ) : ?>
				<p>Importé le <?php echo esc_html( mysql2date( 'j F Y à H:i', $import['date'] ) ); ?>. <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Voir l'accueil</a> · <a href="<?php echo esc_url( admin_url( 'post.php?post=' . (int) $import['accueil'] . '&action=elementor' ) ); ?>">Modifier avec Elementor</a></p>
				<p>Carte : <a href="<?php echo esc_url( home_url( '/carte/' ) ); ?>">/carte/</a> · <a href="<?php echo esc_url( home_url( '/carte/burgers/' ) ); ?>">/carte/burgers/</a> · <a href="<?php echo esc_url( home_url( '/produit/smash-naan/' ) ); ?>">/produit/smash-naan/</a></p>
				<p><button class="button" name="bigm_demo" value="supprimer" onclick="return confirm('Supprimer les produits, sliders, articles et médias de démonstration ?')">Supprimer le contenu de démonstration</button></p>
				<p class="description">Avant la mise en ligne. Les médias encore utilisés dans des pages sont gardés : pensez à les remplacer par ceux du client.</p>
				<h2>Pages à formulaire Elementor Pro (facultatif)</h2>
				<p>Crée les pages <code>/devenir-franchise/</code> et <code>/recrutement/</code> avec un formulaire Elementor Pro (champs du brief), si elles n'existent pas encore. Inutile si ces pages sont faites autrement (par exemple avec WPForms).</p>
				<p><button class="button" name="bigm_demo" value="formulaires">Créer les pages à formulaire</button></p>
			<?php else : ?>
				<p><button class="button button-primary" name="bigm_demo" value="importer">Importer le contenu de démonstration</button></p>
				<p class="description">Prend jusqu'à une minute. Définit la page d'accueil et la page des articles du site.</p>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
