<?php
/**
 * Plugin Name:  Big M — contenu de démonstration
 * Description:  Importe les visuels et les contenus du prototype (sliders, produits, articles) et crée la page d'accueil Elementor. Temporaire : à supprimer avant la mise en ligne. Outils → Démo Big M.
 * Author:       The Follow Movement
 * Version:      0.2.0
 * Requires PHP: 8.2
 * Requires Plugins: bigm-core, elementor
 *
 * @package BigM\Demo
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_DEMO_VERSION', '0.2.0' );
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
	$rapport   = array_merge( $rapport, bigm_demo_carte( $s_ids['carte'] ) );

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
			'post_type'      => array( 'bigm_produit', 'bigm_slider', 'post', 'attachment' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $ids as $id ) {
		'attachment' === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
	}
	delete_option( 'bigm_demo_medias' );
	delete_option( 'bigm_demo_import' );
	return array( count( $ids ) . ' contenus et médias de démonstration supprimés. Les pages Accueil et Actualités sont gardées.' );
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
		$rapport = 'supprimer' === $_POST['bigm_demo'] ? bigm_demo_supprimer() : bigm_demo_importer();
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
				<p class="description">Avant la mise en ligne. Remplacer d'abord les images de la page d'accueil : elles viennent de la démo.</p>
			<?php else : ?>
				<p><button class="button button-primary" name="bigm_demo" value="importer">Importer le contenu de démonstration</button></p>
				<p class="description">Prend jusqu'à une minute. Définit la page d'accueil et la page des articles du site.</p>
			<?php endif; ?>
		</form>
	</div>
	<?php
}
