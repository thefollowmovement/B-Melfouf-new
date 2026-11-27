<?php
/**
 * Rendu des composants de la carte, des catégories et des fiches produit.
 *
 * Le balisage est celui de prototype/pages.py et build.py. Ces fonctions servent aux
 * widgets Elementor (inc/elementor/widgets/) et aux gabarits du thème (inc/pages.php) :
 * le même composant a donc le même balisage partout.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ Contexte */

/**
 * Catégorie de la page affichée (archive /carte/burgers/), ou null.
 */
function bigm_categorie_affichee(): ?WP_Term {
	$o = get_queried_object();
	return $o instanceof WP_Term && 'bigm_categorie' === $o->taxonomy ? $o : null;
}

/**
 * Catégorie de premier niveau d'une catégorie (Burgers pour Bœuf).
 *
 * @param WP_Term $term Catégorie.
 */
function bigm_categorie_racine( WP_Term $term ): WP_Term {
	$ancetres = get_ancestors( $term->term_id, 'bigm_categorie', 'taxonomy' );
	if ( ! $ancetres ) {
		return $term;
	}
	$racine = get_term( (int) end( $ancetres ), 'bigm_categorie' );
	return $racine instanceof WP_Term ? $racine : $term;
}

/**
 * Catégorie principale d'un produit, pour le lien « Revenir à… » de la fiche :
 * la plus précise (sous-catégorie), puis la dernière dans l'ordre de la carte,
 * pour ne pas renvoyer vers Nouveautés ou Menus qui passent en premier.
 * Filtre « bigm_categorie_principale » pour choisir autrement.
 *
 * @param int $post_id Produit.
 */
function bigm_categorie_principale( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'bigm_categorie' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return null;
	}
	$ordre = array_flip( wp_list_pluck( bigm_categories( 0 ), 'term_id' ) );
	$cle   = static function ( WP_Term $t ) use ( $ordre ): array {
		$racine = bigm_categorie_racine( $t );
		return array( count( get_ancestors( $t->term_id, 'bigm_categorie', 'taxonomy' ) ), $ordre[ $racine->term_id ] ?? -1 );
	};
	usort( $terms, static fn( WP_Term $a, WP_Term $b ): int => $cle( $b ) <=> $cle( $a ) );
	$choix = apply_filters( 'bigm_categorie_principale', $terms[0], $post_id, $terms );
	return $choix instanceof WP_Term ? $choix : null;
}

/**
 * Lien Commander : celui du produit, sinon celui des Réglages Big M, sinon la bande appli de l'accueil.
 *
 * @param int|null $post_id Produit.
 */
function bigm_url_commander( ?int $post_id = null ): string {
	$lien = bigm_lien_commande( $post_id );
	return '' !== $lien ? $lien : home_url( '/#appli' );
}

/**
 * Page Restaurants (Réglages Big M, sinon /restaurants/).
 */
function bigm_url_restaurants(): string {
	$lien = (string) bigm_reglage( 'restaurants_url' );
	return '' !== $lien ? $lien : home_url( '/restaurants/' );
}

/**
 * Lien du bandeau des allergènes : le filtre de la catégorie Burgers, sinon de la première catégorie.
 */
function bigm_url_filtre_allergenes(): string {
	$term = get_term_by( 'slug', 'burgers', 'bigm_categorie' );
	if ( ! $term instanceof WP_Term ) {
		$premieres = bigm_categories( 0, true );
		$term      = $premieres[0] ?? null;
	}
	$lien = $term instanceof WP_Term ? get_term_link( $term ) : '';
	return is_string( $lien ) && '' !== $lien ? $lien . '#allergenes' : (string) get_post_type_archive_link( 'bigm_produit' );
}

/**
 * Bannières de la page affichée : celles de la catégorie (ou de sa catégorie parente),
 * ou, sur /carte/, celles des Réglages Big M.
 *
 * @param WP_Term|null $term Catégorie (par défaut : celle de la page).
 */
function bigm_slider_de_la_page( ?WP_Term $term = null ): int {
	$term = $term ?? bigm_categorie_affichee();
	while ( $term ) {
		$c = bigm_categorie( $term );
		if ( $c['slider_id'] ) {
			return (int) $c['slider_id'];
		}
		$term = $term->parent ? get_term( $term->parent, 'bigm_categorie' ) : null;
		$term = $term instanceof WP_Term ? $term : null;
	}
	if ( is_post_type_archive( 'bigm_produit' ) ) {
		$id = (int) bigm_reglage( 'carte_slider' );
		return 'bigm_slider' === get_post_type( $id ) ? $id : 0;
	}
	return 0;
}

/* ------------------------------------------------------------------ Médias */

/**
 * Vidéo de vignette (balise lue par assets/js/tuiles-video.js), ou chaîne vide.
 *
 * @param array<string, mixed>|null $v Vidéo de bigm_video().
 */
function bigm_video_vignette( ?array $v ): string {
	if ( ! $v ) {
		return '';
	}
	$src = $v['mp4'] ? $v['mp4'] : $v['webm'];
	return '<video data-tile-video data-src="' . esc_url( $src ) . '"' . ( $v['mp4'] && $v['webm'] ? ' data-src-webm="' . esc_url( $v['webm'] ) . '"' : '' ) . ' muted loop playsinline preload="none" aria-hidden="true"></video>';
}

/**
 * Image d'une catégorie : sa vignette, sinon la photo de son premier produit.
 *
 * @param array<string, mixed> $cat Catégorie de bigm_categorie().
 */
function bigm_image_categorie( array $cat ): int {
	if ( $cat['image_id'] ) {
		return (int) $cat['image_id'];
	}
	foreach ( bigm_produits_de( (int) $cat['id'] ) as $p ) {
		$img = (int) get_post_thumbnail_id( $p );
		if ( $img ) {
			return $img;
		}
	}
	return 0;
}

/* ------------------------------------------------------------------ Slider */

/**
 * Big M Slider : hero 2:1 ou bannières 3:1 (2:1 sur mobile). Balisage de hs() dans pages.py.
 *
 * @param array<string, mixed> $a slider_id, format (hero|banniere), libelle, titre_cache, id, marge.
 */
function bigm_rendu_slider( array $a ): string {
	$a      = array_merge(
		array(
			'slider_id'   => 0,
			'format'      => 'hero',
			'libelle'     => '',
			'titre_cache' => '',
			'id'          => 't-slider',
			'marge'       => true,
		),
		$a
	);
	$slides = $a['slider_id'] ? bigm_slides_actives( (int) $a['slider_id'] ) : array();
	if ( ! $slides ) {
		return '';
	}
	$hero  = 'banniere' !== $a['format'];
	$sizes = $hero ? '(max-width: 760px) 88vw, min(1040px, 84vw)' : '(max-width: 760px) 90vw, min(1176px, 90vw)';
	$track = '';
	foreach ( $slides as $i => $slide ) {
		$track .= '<div class="hs__slide"><a' . ( $slide['lien'] ? ' href="' . esc_url( $slide['lien'] ) . '"' : '' ) . ' draggable="false">' . bigm_rendu_slide_media( $slide, 0 === $i, $sizes ) . '</a></div>';
	}
	$html = '<div class="hs' . ( $hero ? '' : ' hs--banner' ) . '" data-hs data-interval="' . ( $hero ? 6000 : 5000 ) . '" aria-roledescription="carrousel" aria-label="' . esc_attr( (string) $a['libelle'] ) . '">'
		. '<div class="hs__viewport"><div class="hs__track">' . $track . '</div>'
		. '<button class="hs__arrow hs__arrow--prev" type="button" aria-label="Diapositive précédente">' . bigm_core_icone( 'left' ) . '</button>'
		. '<button class="hs__arrow hs__arrow--next" type="button" aria-label="Diapositive suivante">' . bigm_core_icone( 'right' ) . '</button></div>'
		. '<div class="hs__bar"><div class="hs__dots"></div><button class="hs__pause" type="button"></button></div></div>';

	if ( $hero ) {
		$titre = trim( (string) $a['titre_cache'] );
		return '<section class="hs-section"' . ( $titre ? ' aria-labelledby="' . esc_attr( (string) $a['id'] ) . '"><h1 class="sr-only" id="' . esc_attr( (string) $a['id'] ) . '">' . esc_html( $titre ) . '</h1>' : '>' ) . $html . '</section>';
	}
	return '<section class="hs-banners' . ( $a['marge'] ? '' : ' hs-banners--serre' ) . '" aria-label="' . esc_attr( (string) $a['libelle'] ) . '">' . $html . '</section>';
}

/**
 * Média d'une slide : image (avec image mobile) ou vidéo chargée par le script.
 *
 * @param array<string, mixed> $slide   Slide de bigm_slides_actives().
 * @param bool                 $premier Première slide : chargée tout de suite (LCP).
 * @param string               $sizes   Attribut sizes.
 */
function bigm_rendu_slide_media( array $slide, bool $premier, string $sizes ): string {
	$style = 'object-position:' . $slide['cadrage'];
	if ( 'video' === $slide['type'] ) {
		$src = $slide['mp4'] ? $slide['mp4'] : $slide['webm'];
		return '<video class="hs__media" data-src="' . esc_url( $src ) . '"' . ( $slide['mp4'] && $slide['webm'] ? ' data-src-webm="' . esc_url( $slide['webm'] ) . '"' : '' )
			. ( $slide['poster'] ? ' poster="' . esc_url( $slide['poster'] ) . '"' : '' )
			. ' muted playsinline preload="none" aria-label="' . esc_attr( $slide['alt'] ) . '" style="' . esc_attr( $style ) . '"></video>';
	}
	$img = wp_get_attachment_image(
		(int) $slide['image_id'],
		'full',
		false,
		array(
			'class'     => 'hs__media',
			'alt'       => $slide['alt'],
			'style'     => $style,
			'draggable' => 'false',
			'sizes'     => $sizes,
		) + ( $premier ? array(
			// Première slide : chargée tout de suite et en priorité (LCP). Sans attribut
			// loading, WordPress n'ajoute pas de « lazy » à une image en fetchpriority high.
			'loading'       => false,
			'fetchpriority' => 'high',
		) : array() )
	);
	if ( $slide['image_mobile_id'] && $slide['image_mobile_id'] !== $slide['image_id'] ) {
		$srcset = wp_get_attachment_image_srcset( (int) $slide['image_mobile_id'], 'full' );
		$srcset = $srcset ? $srcset : (string) wp_get_attachment_image_url( (int) $slide['image_mobile_id'], 'full' );
		$img    = '<picture><source media="(max-width: 760px)" srcset="' . esc_attr( $srcset ) . '" sizes="90vw">' . $img . '</picture>';
	}
	return $img;
}

/* ------------------------------------------------------------------ Carte */

/**
 * Barre de catégories en capsule, collante sous l'en-tête (tabs() dans build.py).
 *
 * @param array<string, mixed> $a toute_la_carte (libellé), libelle (aria-label), masquer_vides,
 *                                courante (id de catégorie, -1 : détectée, 0 : toute la carte).
 */
function bigm_rendu_barre_categories( array $a = array() ): string {
	$a = array_merge(
		array(
			'toute_la_carte' => 'Toute la carte',
			'libelle'        => 'Catégories de la carte',
			'masquer_vides'  => true,
			'courante'       => -1,
		),
		$a
	);

	$courante = (int) $a['courante'];
	$exacte   = true;
	if ( -1 === $courante ) {
		$courante = 0;
		$term     = bigm_categorie_affichee();
		if ( ! $term && is_singular( 'bigm_produit' ) ) {
			$term = bigm_categorie_principale( (int) get_queried_object_id() );
		}
		if ( $term ) {
			$racine   = bigm_categorie_racine( $term );
			$courante = $racine->term_id;
			$exacte   = $racine->term_id === $term->term_id && ! is_singular();
		} elseif ( ! is_post_type_archive( 'bigm_produit' ) ) {
			$courante = -1;
		}
	}
	// aria-current="page" sur la page même, "true" sur la catégorie parente d'une sous-catégorie.
	$actif = static fn( bool $oui ): string => $oui ? ' aria-current="' . ( $exacte ? 'page' : 'true' ) . '"' : '';

	$items = '<li><a href="' . esc_url( (string) get_post_type_archive_link( 'bigm_produit' ) ) . '"' . $actif( 0 === $courante ) . '>'
		. bigm_icone_depuis_trace( 'M4 11l8-7 8 7v9h-5v-6H9v6H4z' ) . esc_html( (string) $a['toute_la_carte'] ) . '</a></li>';
	foreach ( bigm_categories( 0, (bool) $a['masquer_vides'] ) as $term ) {
		$c      = bigm_categorie( $term );
		$items .= '<li><a href="' . esc_url( $c['url'] ) . '"' . $actif( $term->term_id === $courante ) . '>' . $c['icone'] . esc_html( $c['nom'] ) . '</a></li>';
	}
	return '<nav class="cat-tabs" aria-label="' . esc_attr( (string) $a['libelle'] ) . '"><ul class="container">' . $items . '</ul></nav>';
}

/**
 * En-tête de la carte ou d'une catégorie : titre h1, texte et bouton Commander.
 *
 * @param array<string, mixed> $a titre, texte, bouton (libellé, vide : pas de bouton), lien, variante (carte|categorie).
 */
function bigm_rendu_entete_carte( array $a ): string {
	$a      = array_merge(
		array(
			'titre'    => '',
			'texte'    => '',
			'bouton'   => '',
			'lien'     => '',
			'variante' => 'carte',
		),
		$a
	);
	$texte  = trim( (string) $a['texte'] );
	$bouton = trim( (string) $a['bouton'] );
	return '<div class="container"><div class="carte-head' . ( 'categorie' === $a['variante'] ? ' carte-head--categorie' : '' ) . '">'
		. '<div><h1>' . esc_html( (string) $a['titre'] ) . '</h1>' . ( '' !== $texte ? '<p>' . esc_html( $texte ) . '</p>' : '' ) . '</div>'
		// Sans lien propre, c'est le bouton Commander, avec son icône de sac.
		. ( '' !== $bouton ? '<a class="btn" href="' . esc_url( $a['lien'] ? (string) $a['lien'] : bigm_url_commander() ) . '">' . ( $a['lien'] ? '' : bigm_core_icone( 'bag' ) ) . esc_html( $bouton ) . '</a>' : '' )
		. '</div></div>';
}

/**
 * Vignette d'une catégorie (cat_tile() dans pages.py).
 *
 * @param WP_Term $term    Catégorie.
 * @param int     $n       Rang, pour le décalage de l'apparition.
 * @param string  $libelle Libellé à la place du nom de la catégorie.
 */
function bigm_tuile_categorie( WP_Term $term, int $n, string $libelle = '' ): string {
	$c     = bigm_categorie( $term );
	$img   = bigm_image_categorie( $c );
	$img   = $img ? wp_get_attachment_image(
		$img,
		'medium_large',
		false,
		array(
			'alt'   => '',
			'sizes' => '(max-width: 960px) 46vw, 220px',
		)
	) : '';
	$video = bigm_video_vignette( $c['video'] );
	return '<a class="tile' . ( $video ? ' has-video' : '' ) . '" id="' . esc_attr( $c['slug'] ) . '" href="' . esc_url( $c['url'] ) . '" data-reveal style="--i:' . ( $n % 5 ) . '">'
		. '<div class="tile__img">' . $img . $video . '</div><span class="tile__label">' . esc_html( '' !== trim( $libelle ) ? $libelle : $c['nom'] ) . '</span></a>';
}

/**
 * Grille des catégories de la carte.
 *
 * @param array<string, mixed> $a termes (liste de [id, libelle] dans l'ordre ; vide : catégories
 *                                de premier niveau), masquer_vides.
 */
function bigm_rendu_grille_categories( array $a = array() ): string {
	$a     = array_merge(
		array(
			'termes'        => array(),
			'masquer_vides' => true,
		),
		$a
	);
	$terms = array();
	if ( $a['termes'] ) {
		foreach ( (array) $a['termes'] as $item ) {
			$item = (array) $item;
			$t    = get_term( (int) ( $item['id'] ?? 0 ), 'bigm_categorie' );
			if ( $t instanceof WP_Term && ( ! $a['masquer_vides'] || bigm_produits_de( $t->term_id ) ) ) {
				$terms[] = array( $t, (string) ( $item['libelle'] ?? '' ) );
			}
		}
	} else {
		$terms = array_map( static fn( WP_Term $t ): array => array( $t, '' ), bigm_categories( 0, (bool) $a['masquer_vides'] ) );
	}
	if ( ! $terms ) {
		return '';
	}
	$tuiles = '';
	foreach ( $terms as $n => $t ) {
		$tuiles .= bigm_tuile_categorie( $t[0], $n, $t[1] );
	}
	return '<div class="container"><div class="cat-grid">' . $tuiles . '</div></div>';
}

/**
 * Bandeau des allergènes, en bas de la carte.
 *
 * @param array<string, mixed> $a titre, texte, bouton, lien, id.
 */
function bigm_rendu_bandeau_allergenes( array $a = array() ): string {
	$a = array_merge(
		array(
			'titre'  => (string) bigm_reglage( 'allergenes_titre' ),
			'texte'  => (string) bigm_reglage( 'allergenes_texte' ),
			'bouton' => 'Filtrer par allergène',
			'lien'   => '',
			'id'     => 't-allerg',
		),
		$a
	);
	$texte  = trim( (string) $a['texte'] );
	$bouton = trim( (string) $a['bouton'] );
	return '<div class="container"><section class="allergy-band" aria-labelledby="' . esc_attr( (string) $a['id'] ) . '" data-reveal>'
		. '<div><h2 id="' . esc_attr( (string) $a['id'] ) . '">' . esc_html( (string) $a['titre'] ) . '</h2>' . ( '' !== $texte ? '<p>' . esc_html( $texte ) . '</p>' : '' ) . '</div>'
		. ( '' !== $bouton ? '<a class="btn btn--light" href="' . esc_url( $a['lien'] ? (string) $a['lien'] : bigm_url_filtre_allergenes() ) . '">' . esc_html( $bouton ) . '</a>' : '' )
		. '</section></div>';
}

/* ------------------------------------------------------------------ Catégorie */

/**
 * Vignette d'un produit dans la grille d'une catégorie (prod() dans pages.py).
 * data-meat et data-allergens servent au filtre (assets/js/filtres.js).
 *
 * @param array<string, mixed> $p       Produit de bigm_produit().
 * @param int                  $i       Rang, pour le décalage de l'apparition.
 * @param bool                 $secouer Nouveauté qui se secoue en apparaissant (produits.css).
 */
function bigm_carte_produit( array $p, int $i, bool $secouer = false ): string {
	$img   = $p['image_id'] ? wp_get_attachment_image(
		$p['image_id'],
		'medium_large',
		false,
		array(
			// Le nom est déjà dans le lien : l'image est décorative.
			'alt'   => '',
			'sizes' => '(max-width: 960px) 44vw, 260px',
		)
	) : '';
	$video = bigm_video_vignette( $p['video'] );
	$badge = '' !== $p['badge'] ? '<span class="badge">' . esc_html( $p['badge'] ) . '</span>' : '';
	return '<a class="product' . ( $video ? ' has-video' : '' ) . ( $secouer ? ' is-new' : '' ) . '" href="' . esc_url( $p['url'] ) . '" data-meat="' . esc_attr( implode( ' ', $p['viandes'] ?? array( $p['viande'] ) ) ) . '" data-allergens="' . esc_attr( implode( ' ', $p['allergenes']['contient'] ) ) . '" data-reveal style="--i:' . ( $i % 4 ) . '">'
		. '<span class="product__name">' . esc_html( $p['titre'] ) . '</span><div class="product__img">' . $badge . $img . $video . '</div></a>';
}

/**
 * Groupes de produits d'une catégorie : un par sous-catégorie, dans l'ordre de la carte.
 * Les produits rangés dans la catégorie elle-même forment un premier groupe sans titre.
 *
 * @param WP_Term $term    Catégorie.
 * @param bool    $grouper Regrouper par sous-catégorie.
 * @return array<int, array{term: ?WP_Term, produits: array<int, array<string, mixed>>}>
 */
function bigm_groupes_produits( WP_Term $term, bool $grouper = true ): array {
	$enfants = $grouper ? bigm_categories( $term->term_id, true ) : array();
	$groupes = array();
	$vus     = array();
	foreach ( $enfants as $enfant ) {
		$produits = array_filter( array_map( 'bigm_produit', bigm_produits_de( $enfant->term_id ) ) );
		if ( $produits ) {
			$groupes[] = array(
				'term'     => $enfant,
				'produits' => array_values( $produits ),
			);
			$vus       = array_merge( $vus, wp_list_pluck( $produits, 'id' ) );
		}
	}
	$autres = array();
	foreach ( bigm_produits_de( $term->term_id ) as $post ) {
		if ( ! in_array( $post->ID, $vus, true ) ) {
			$autres[] = bigm_produit( $post );
		}
	}
	if ( $autres ) {
		array_unshift(
			$groupes,
			array(
				'term'     => null,
				'produits' => array_values( array_filter( $autres ) ),
			)
		);
	}
	return '1' === (string) bigm_reglage( 'carte_nouveautes' ) ? bigm_nouveautes_en_tete( $groupes ) : $groupes;
}

/**
 * Nouveautés en tête d'une catégorie (Réglages Big M → La carte), les plus récentes d'abord.
 * Plusieurs groupes : un groupe « Nouveautés » (nom de la catégorie Nouveautés) en premier, les
 * produits quittant leur sous-catégorie. Un seul groupe : en tête de ce groupe. Rien ne bouge si
 * tous les produits sont nouveaux, ou aucun.
 *
 * @param array<int, array{term: ?WP_Term, produits: array<int, array<string, mixed>>}> $groupes Groupes de bigm_groupes_produits().
 * @return array<int, array<string, mixed>>
 */
function bigm_nouveautes_en_tete( array $groupes ): array {
	$nouveaux = array();
	$total    = 0;
	foreach ( $groupes as $g ) {
		foreach ( $g['produits'] as $p ) {
			++$total;
			if ( bigm_produit_est_nouveau( $p ) ) {
				$nouveaux[ $p['id'] ] = $p;
			}
		}
	}
	if ( ! $nouveaux || count( $nouveaux ) === $total ) {
		return $groupes;
	}
	$rang = array_flip( array_keys( $nouveaux ) );
	uasort( $nouveaux, static fn( array $a, array $b ): int => array( get_post_field( 'post_date', $b['id'] ), $rang[ $a['id'] ] ) <=> array( get_post_field( 'post_date', $a['id'] ), $rang[ $b['id'] ] ) );
	$sans = static fn( array $produits ): array => array_values( array_filter( $produits, static fn( array $p ): bool => ! isset( $nouveaux[ $p['id'] ] ) ) );

	if ( 1 === count( $groupes ) ) {
		$groupes[0]['produits'] = array_merge( array_values( $nouveaux ), $sans( $groupes[0]['produits'] ) );
		return $groupes;
	}
	$restants = array();
	foreach ( $groupes as $g ) {
		$g['produits'] = $sans( $g['produits'] );
		if ( $g['produits'] ) {
			$restants[] = $g;
		}
	}
	$cat = get_term_by( 'slug', 'nouveautes', 'bigm_categorie' );
	array_unshift(
		$restants,
		array(
			'term'     => null,
			'titre'    => (string) apply_filters( 'bigm_groupe_nouveautes_titre', $cat ? $cat->name : 'Nouveautés' ),
			'ancre'    => 'nouveau',
			'produits' => array_values( $nouveaux ),
		)
	);
	return $restants;
}

/**
 * Grille des produits d'une catégorie, avec les filtres par viande et par allergène.
 * Le filtrage se fait dans le navigateur (assets/js/filtres.js), comme dans le prototype.
 *
 * @param array<string, mixed> $a terme (id ou WP_Term), grouper, filtres, textes.
 */
function bigm_rendu_grille_produits( array $a ): string {
	static $rang = 0;
	$a    = array_merge(
		array(
			'terme'          => null,
			'grouper'        => true,
			'filtres'        => true,
			'filtre_libelle' => 'Filtrer par allergène',
			'panneau_titre'  => "Masquer les produits qui contiennent\u{202F}:",
			'panneau_note'   => 'Les traces éventuelles sont indiquées sur chaque fiche produit.',
			'vide_titre'     => 'Aucun produit ne correspond',
			'vide_texte'     => 'Retirez un allergène ou changez de filtre.',
			'vide_bouton'    => 'Réinitialiser les filtres',
			'aucun'          => 'Aucun produit dans cette catégorie pour le moment.',
		),
		$a
	);
	$term = $a['terme'] instanceof WP_Term ? $a['terme'] : get_term( (int) $a['terme'], 'bigm_categorie' );
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	$groupes = bigm_groupes_produits( $term, (bool) $a['grouper'] );
	if ( ! $groupes ) {
		return '<div class="container"><div class="empty" data-show="true"><h2>' . esc_html( (string) $a['aucun'] ) . '</h2></div></div>';
	}
	++$rang;
	$uid = 'allergy-' . $term->term_id . ( $rang > 1 ? '-' . $rang : '' );

	// Vignettes des nouveautés qui se secouent : pas sur une page où tout est nouveau.
	$tous     = array_merge( ...array_column( $groupes, 'produits' ) );
	$nouveaux = count( array_filter( array_map( 'bigm_produit_est_nouveau', $tous ) ) );
	$secouer  = '1' === (string) bigm_reglage( 'carte_secousse' ) && $nouveaux < count( $tous );

	$html = '';
	foreach ( $groupes as $g ) {
		$cartes = '';
		foreach ( $g['produits'] as $i => $p ) {
			$cartes .= bigm_carte_produit( $p, $i, $secouer && bigm_produit_est_nouveau( $p ) );
		}
		$titre = $g['term'] ? $g['term']->name : (string) ( $g['titre'] ?? '' );
		if ( '' !== $titre ) {
			$slug  = $g['term'] ? $g['term']->slug : (string) $g['ancre'];
			$html .= '<section class="group" id="' . esc_attr( $slug ) . '" data-group="' . esc_attr( $slug ) . '" aria-labelledby="t-' . esc_attr( $slug ) . '"><h2 id="t-' . esc_attr( $slug ) . '">' . esc_html( $titre ) . '</h2><div class="products">' . $cartes . '</div></section>';
		} else {
			$html .= '<div class="group" data-group><div class="products">' . $cartes . '</div></div>';
		}
	}

	$filtres = '';
	if ( $a['filtres'] ) {
		// Viandes présentes dans la catégorie, dans l'ordre de la liste de référence.
		$presentes = array_unique( array_filter( array_merge( ...array_map( static fn( array $g ): array => array_merge( array(), ...wp_list_pluck( $g['produits'], 'viandes' ) ), $groupes ) ) ) );
		$viandes   = array_intersect_key( bigm_viandes(), array_flip( $presentes ) );
		$chips     = '';
		if ( count( $viandes ) > 1 ) {
			$chips = '<button class="chip" type="button" data-meat-filter="tous" aria-pressed="true">Tous</button>';
			foreach ( $viandes as $slug => $nom ) {
				$chips .= '<button class="chip" type="button" data-meat-filter="' . esc_attr( $slug ) . '" aria-pressed="false">' . esc_html( $nom ) . '</button>';
			}
			$chips = '<div class="chips" role="group" aria-label="Filtrer par viande">' . $chips . '</div>';
		}
		$allergenes = '';
		foreach ( bigm_allergenes() as $slug => $al ) {
			$allergenes .= '<button class="chip" type="button" data-allergen="' . esc_attr( $slug ) . '" aria-pressed="false">' . esc_html( $al['label'] ) . '</button>';
		}
		$note    = trim( (string) $a['panneau_note'] );
		$filtres = '<div class="filters"' . ( 1 === $rang ? ' id="allergenes"' : '' ) . '>' . $chips
			. '<button class="chip" type="button" data-toggle-allergy data-libelle="' . esc_attr( (string) $a['filtre_libelle'] ) . '" aria-expanded="false" aria-controls="' . esc_attr( $uid ) . '">' . esc_html( (string) $a['filtre_libelle'] ) . '</button></div>'
			. '<div class="allergy" id="' . esc_attr( $uid ) . '" data-open="false"><h2>' . esc_html( (string) $a['panneau_titre'] ) . '</h2><div class="chips">' . $allergenes . '</div>'
			. ( '' !== $note ? '<small>' . esc_html( $note ) . '</small>' : '' ) . '</div>';
		$html   .= '<div class="empty" data-empty><h2>' . esc_html( (string) $a['vide_titre'] ) . '</h2><p>' . esc_html( (string) $a['vide_texte'] ) . '</p><button class="btn" type="button" data-reset>' . esc_html( (string) $a['vide_bouton'] ) . '</button></div>';
	}
	return '<div class="container"' . ( $a['filtres'] ? ' data-filtres' : '' ) . '>' . $filtres . $html . '</div>';
}

/* ------------------------------------------------------------------ Fiche produit */

/**
 * Bandeau orange (photo, lien de retour) et présentation du produit (nom, description, boutons).
 *
 * @param array<string, mixed> $a produit (id), retour (bool), commander, restaurants (libellés).
 */
function bigm_rendu_produit_entete( array $a ): string {
	$a = array_merge(
		array(
			'produit'     => 0,
			'retour'      => true,
			'commander'   => 'Commander',
			'restaurants' => 'Trouver un restaurant',
		),
		$a
	);
	$p = bigm_produit( (int) $a['produit'] );
	if ( ! $p ) {
		return '';
	}
	$retour = '';
	if ( $a['retour'] ) {
		// « Nos burgers » → « Revenir à nos burgers ». Sans titre de page propre : « Revenir à la carte ».
		$cat   = bigm_categorie_principale( $p['id'] );
		$cat   = $cat ? bigm_categorie( bigm_categorie_racine( $cat ) ) : null;
		$texte = 'la carte';
		$lien  = (string) get_post_type_archive_link( 'bigm_produit' );
		if ( $cat && $cat['titre'] !== $cat['nom'] ) {
			$texte = mb_strtolower( mb_substr( $cat['titre'], 0, 1 ) ) . mb_substr( $cat['titre'], 1 );
			$lien  = $cat['url'];
		}
		$retour = '<a class="p-back" href="' . esc_url( $lien ) . '">' . bigm_core_icone( 'left' ) . esc_html( 'Revenir à ' . $texte ) . '</a>';
	}
	$image = $p['image_id'] ? wp_get_attachment_image(
		$p['image_id'],
		'large',
		false,
		array(
			'sizes'         => '(max-width: 700px) calc(100vw - 32px), 640px',
			// Image principale de la page (LCP) : chargée tout de suite.
			'loading'       => false,
			'fetchpriority' => 'high',
		)
	) : '';

	$boutons = '';
	if ( '' !== trim( (string) $a['commander'] ) ) {
		$boutons .= '<a class="btn" href="' . esc_url( bigm_url_commander( $p['id'] ) ) . '">' . bigm_core_icone( 'bag' ) . esc_html( (string) $a['commander'] ) . '</a>';
	}
	if ( '' !== trim( (string) $a['restaurants'] ) ) {
		$boutons .= '<a class="btn btn--outline" href="' . esc_url( bigm_url_restaurants() ) . '">' . esc_html( (string) $a['restaurants'] ) . '</a>';
	}
	return '<div class="p-band"><div class="container p-band__inner">' . $retour . $image . '</div></div>'
		. '<div class="p-main"><h1>' . esc_html( $p['titre'] ) . '</h1>' . $p['description']
		. ( $boutons ? '<div class="p-actions">' . $boutons . '</div>' : '' ) . '</div>';
}

/**
 * Sections dépliables de la fiche : ingrédients, allergènes, valeurs nutritionnelles.
 * Une section sans donnée n'est pas affichée.
 *
 * @param array<string, mixed> $a produit (id), titres, note, ouvertes (ingredients, allergenes, nutrition).
 */
function bigm_rendu_fiche_produit( array $a ): string {
	$a = array_merge(
		array(
			'produit'           => 0,
			'titre_ingredients' => 'Ingrédients',
			'titre_allergenes'  => 'Allergènes',
			'titre_nutrition'   => 'Valeurs nutritionnelles',
			'contient'          => 'Contient',
			'traces'            => 'Peut contenir des traces de',
			'note'              => (string) bigm_reglage( 'allergenes_note' ),
			'ouvertes'          => array( 'ingredients', 'allergenes' ),
		),
		$a
	);
	$p = bigm_produit( (int) $a['produit'] );
	if ( ! $p ) {
		return '';
	}
	$section = static function ( string $cle, string $titre, string $corps ) use ( $a ): string {
		// Titre h2 dans le résumé : les « Contient » et « Traces » (h3) suivent alors un h2 (ordre des titres).
		return '<details' . ( in_array( $cle, (array) $a['ouvertes'], true ) ? ' open' : '' ) . '><summary><h2 class="acc__titre">' . esc_html( $titre ) . '</h2> ' . bigm_core_icone( 'right' ) . '</summary><div class="acc__body">' . $corps . '</div></details>';
	};
	$liste   = static fn( array $items, string $classe ): string => '<ul class="' . $classe . '">' . implode( '', array_map( static fn( string $t ): string => '<li>' . esc_html( $t ) . '</li>', $items ) ) . '</ul>';

	$html = '';
	if ( $p['ingredients'] ) {
		$html .= $section( 'ingredients', (string) $a['titre_ingredients'], $liste( $p['ingredients'], 'ingredients' ) );
	}
	$al = $p['allergenes'];
	if ( $al['contient_labels'] || $al['traces_labels'] ) {
		$corps = '';
		if ( $al['contient_labels'] ) {
			$corps .= '<h3>' . esc_html( (string) $a['contient'] ) . '</h3>' . $liste( $al['contient_labels'], 'al-list' );
		}
		if ( $al['traces_labels'] ) {
			$corps .= '<h3>' . esc_html( (string) $a['traces'] ) . '</h3>' . $liste( $al['traces_labels'], 'al-list traces' );
		}
		$note   = trim( (string) $a['note'] );
		$corps .= '' !== $note ? '<small>' . esc_html( $note ) . '</small>' : '';
		$html  .= $section( 'allergenes', (string) $a['titre_allergenes'], $corps );
	}
	if ( $p['nutrition']['lignes'] ) {
		$portion = '1 portion' . ( '' !== $p['nutrition']['portion_g'] ? " ({$p['nutrition']['portion_g']}\u{202F}g)" : '' );
		$lignes  = '';
		foreach ( $p['nutrition']['lignes'] as $l ) {
			$lignes .= '<tr><td>' . esc_html( $l['label'] ) . '</td><td>' . esc_html( '' !== $l['portion'] ? $l['portion'] : '—' ) . '</td><td>' . esc_html( '' !== $l['100g'] ? $l['100g'] : '—' ) . '</td></tr>';
		}
		$html .= $section(
			'nutrition',
			(string) $a['titre_nutrition'],
			'<table><thead><tr><th scope="col">Pour</th><th scope="col">' . esc_html( $portion ) . "</th><th scope=\"col\">100\u{202F}g</th></tr></thead><tbody>" . $lignes . '</tbody></table>'
		);
	}
	return $html ? '<div class="acc">' . $html . '</div>' : '';
}

/**
 * « Ça va bien avec » : vignettes des produits associés.
 *
 * @param array<string, mixed> $a produit (id), titre, id (du titre).
 */
function bigm_rendu_produits_associes( array $a ): string {
	$a = array_merge(
		array(
			'produit' => 0,
			'titre'   => 'Ça va bien avec',
			'id'      => 't-with',
		),
		$a
	);
	$p = bigm_produit( (int) $a['produit'] );
	if ( ! $p || ! $p['associes'] ) {
		return '';
	}
	$tuiles = '';
	foreach ( $p['associes'] as $id ) {
		$tuiles .= bigm_tuile_produit( $id, '(max-width: 700px) 44vw, 260px' );
	}
	return '<section class="with" aria-labelledby="' . esc_attr( (string) $a['id'] ) . '"><h2 id="' . esc_attr( (string) $a['id'] ) . '">' . esc_html( (string) $a['titre'] ) . '</h2><div class="with__grid">' . $tuiles . '</div></section>';
}

/* ------------------------------------------------------------------ Actualités */

/**
 * Carte d'un article (.news-card de l'accueil) : photo, catégorie (News ou Presse) et date, titre.
 *
 * @param int|WP_Post $post   Article.
 * @param int         $i      Rang, pour le décalage de l'apparition.
 * @param string      $sizes  Attribut sizes de la photo.
 * @param string      $niveau Balise du titre : h3 sous un titre de section, h2 directement sous le h1.
 */
function bigm_carte_article( $post, int $i = 0, string $sizes = '(max-width: 960px) 100vw, 400px', string $niveau = 'h3' ): string {
	$niveau = 'h2' === $niveau ? 'h2' : 'h3';
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$meta = bigm_article_meta( $post );
	$img  = get_post_thumbnail_id( $post ) ? wp_get_attachment_image( (int) get_post_thumbnail_id( $post ), 'large', false, array( 'sizes' => $sizes ) ) : '';
	return '<a class="news-card" href="' . esc_url( get_permalink( $post ) ) . '" data-reveal' . ( $i ? ' style="--i:' . ( $i % 3 ) . '"' : '' ) . '>'
		. '<div class="news-card__img">' . $img . '</div><div class="news-card__body"><small>' . esc_html( $meta ) . '</small><' . $niveau . '>' . esc_html( get_the_title( $post ) ) . '</' . $niveau . '></div></a>';
}

/**
 * Catégorie (News ou Presse de préférence) et date d'un article : « Presse · 9 octobre 2026 ».
 *
 * @param WP_Post $post Article.
 */
function bigm_article_meta( WP_Post $post ): string {
	$terms = get_the_category( $post->ID );
	$cat   = $terms ? $terms[0]->name : '';
	foreach ( $terms as $t ) {
		if ( in_array( $t->slug, array( 'news', 'presse' ), true ) ) {
			$cat = $t->name;
			break;
		}
	}
	// La catégorie par défaut (« Non classé ») n'est pas affichée.
	if ( $terms && (int) get_option( 'default_category' ) === $terms[0]->term_id && 1 === count( $terms ) ) {
		$cat = '';
	}
	return trim( $cat . ' · ' . get_the_date( 'j F Y', $post ), ' ·' );
}

/**
 * Pastilles des catégories d'articles (Tout, News, Presse…), la catégorie affichée allumée.
 * Les catégories vides et la catégorie par défaut (« Non classé ») ne sont pas proposées.
 */
function bigm_rendu_categories_articles(): string {
	$tout     = (int) get_option( 'page_for_posts' ) ? (string) get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
	$courante = is_category() ? (int) get_queried_object_id() : 0;
	$liens    = '<a href="' . esc_url( $tout ) . '"' . ( $courante ? '' : ' aria-current="page"' ) . '>Tout</a>';
	$n        = 0;
	foreach ( get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) ) as $cat ) {
		$liens .= '<a href="' . esc_url( get_category_link( $cat ) ) . '"' . ( $cat->term_id === $courante ? ' aria-current="page"' : '' ) . '>' . esc_html( $cat->name ) . '</a>';
		++$n;
	}
	return $n ? '<nav class="news-tabs" aria-label="Catégories d’articles">' . $liens . '</nav>' : '';
}
