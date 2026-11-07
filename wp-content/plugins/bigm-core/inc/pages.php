<?php
/**
 * Pages de la carte, des catégories et des fiches produit, telles que le prototype les montre.
 *
 * Les gabarits du thème bigm-child (archive-bigm_produit.php, taxonomy-bigm_categorie.php,
 * single-bigm_produit.php) affichent ces pages. Un modèle du Theme Builder d'Elementor Pro
 * (Archive ou Publication unique) les remplace s'il en existe un pour la page : il se compose
 * avec les mêmes widgets Big M.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page /carte/ : barre de catégories, en-tête, bannières, grille des catégories, bandeau des allergènes.
 */
function bigm_page_carte(): string {
	return bigm_rendu_barre_categories( array( 'courante' => 0 ) )
		. '<main id="content">'
		. bigm_rendu_entete_carte(
			array(
				'titre'  => (string) bigm_reglage( 'carte_titre' ),
				'texte'  => (string) bigm_reglage( 'carte_texte' ),
				'bouton' => 'Commander',
			)
		)
		. bigm_rendu_slider(
			array(
				'slider_id' => bigm_slider_de_la_page(),
				'format'    => 'banniere',
				'libelle'   => 'Offres du moment',
			)
		)
		. bigm_rendu_grille_categories()
		. bigm_rendu_bandeau_allergenes()
		. '</main>';
}

/**
 * Page d'une catégorie : barre, en-tête, bannières de la catégorie (ou de sa parente), produits filtrables.
 *
 * @param WP_Term $term Catégorie.
 */
function bigm_page_categorie( WP_Term $term ): string {
	$c = bigm_categorie( $term );
	return bigm_rendu_barre_categories()
		. '<main id="content">'
		. bigm_rendu_entete_carte(
			array(
				'titre'    => $c['titre'],
				'texte'    => wp_strip_all_tags( $c['description'] ),
				'variante' => 'categorie',
			)
		)
		. bigm_rendu_slider(
			array(
				'slider_id' => bigm_slider_de_la_page( $term ),
				'format'    => 'banniere',
				'libelle'   => 'Offres ' . mb_strtolower( $c['nom'] ),
				'marge'     => false,
			)
		)
		. bigm_rendu_grille_produits( array( 'terme' => $term ) )
		. '</main>';
}

/**
 * Fiche produit : bandeau, présentation, sections dépliables, « Ça va bien avec ».
 *
 * @param int $post_id Produit.
 */
function bigm_page_produit( int $post_id ): string {
	return '<main id="content">'
		. bigm_rendu_produit_entete( array( 'produit' => $post_id ) )
		. bigm_rendu_fiche_produit( array( 'produit' => $post_id ) )
		. bigm_rendu_produits_associes( array( 'produit' => $post_id ) )
		. '</main>';
}

/**
 * Page Actualités (page des articles) ou une catégorie d'articles (News, Presse) :
 * en-tête, catégories, cartes des articles de la requête principale, pages.
 */
function bigm_page_actualites(): string {
	$page_id = (int) get_option( 'page_for_posts' );
	if ( is_category() ) {
		$titre = single_cat_title( '', false );
		$texte = wp_strip_all_tags( (string) category_description() );
	} else {
		$titre = $page_id ? get_the_title( $page_id ) : 'Actualités';
		// Chapeau : l'extrait, sinon le texte de la page Actualités.
		$texte = $page_id ? wp_strip_all_tags( has_excerpt( $page_id ) ? get_the_excerpt( $page_id ) : (string) get_post_field( 'post_content', $page_id ) ) : '';
	}
	$cartes = '';
	$i      = 0;
	while ( have_posts() ) {
		the_post();
		$cartes .= bigm_carte_article( get_post(), $i++, '(max-width: 960px) 100vw, 400px', 'h2' );
	}
	$liste = $cartes ? '<div class="news-cards news-cards--liste">' . $cartes . '</div>' : '<p class="news-none">Aucun article pour le moment.</p>';
	$pages = get_the_posts_pagination(
		array(
			'mid_size'           => 1,
			'prev_text'          => 'Précédent',
			'next_text'          => 'Suivant',
			'screen_reader_text' => 'Pages des actualités',
			'class'              => 'news-pages',
		)
	);
	return '<main id="content">'
		. bigm_rendu_entete_carte(
			array(
				'titre'    => (string) $titre,
				'texte'    => trim( $texte ),
				'variante' => 'categorie',
			)
		)
		. '<div class="container">' . bigm_rendu_categories_articles() . $liste . $pages . '</div></main>';
}

/**
 * Un article : retour aux actualités, catégorie et date, titre, photo, texte, « À lire aussi ».
 * À appeler dans la boucle (le texte passe par the_content, comme pour Elementor).
 */
function bigm_page_article(): string {
	$post = get_post();
	if ( ! $post ) {
		return '';
	}
	$page_id = (int) get_option( 'page_for_posts' );
	$retour  = $page_id ? (string) get_permalink( $page_id ) : home_url( '/' );
	$meta    = bigm_article_meta( $post );
	$image   = get_post_thumbnail_id( $post ) ? wp_get_attachment_image(
		(int) get_post_thumbnail_id( $post ),
		'full',
		false,
		array(
			'sizes'         => '(max-width: 860px) 100vw, 812px',
			'loading'       => false,
			'fetchpriority' => 'high',
		)
	) : '';
	$texte = (string) apply_filters( 'the_content', get_the_content( null, false, $post ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre de WordPress.

	// À lire aussi : les derniers articles de la même catégorie, puis les autres.
	$cats   = wp_get_post_categories( $post->ID );
	$autres = get_posts(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 3,
			'post__not_in'        => array( $post->ID ),
			'category__in'        => $cats,
			'ignore_sticky_posts' => true,
		)
	);
	if ( count( $autres ) < 3 ) {
		$autres = array_merge(
			$autres,
			get_posts(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => 3 - count( $autres ),
					'post__not_in'        => array_merge( array( $post->ID ), wp_list_pluck( $autres, 'ID' ) ),
					'ignore_sticky_posts' => true,
				)
			)
		);
	}
	$plus = '';
	foreach ( $autres as $i => $a ) {
		$plus .= bigm_carte_article( $a, $i );
	}
	$plus = $plus ? '<section class="container section" aria-labelledby="t-plus"><div class="section-head"><h2 id="t-plus">À lire aussi</h2></div><div class="news-cards">' . $plus . '</div></section>' : '';

	return '<main id="content"><article class="article">'
		. '<a class="article__back" href="' . esc_url( $retour ) . '">' . bigm_core_icone( 'left' ) . 'Toutes les actualités</a>'
		. ( '' !== $meta ? '<p class="article__meta">' . esc_html( $meta ) . '</p>' : '' )
		. '<h1>' . esc_html( get_the_title( $post ) ) . '</h1>'
		. ( $image ? '<div class="article__img">' . $image . '</div>' : '' )
		. '<div class="article__body page-content">' . $texte . '</div>'
		. '</article>' . $plus . '</main>';
}

/*
 * Page Actualités et catégories : 9 articles par page (3 rangées de 3 cartes).
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ) {
		if ( ! is_admin() && $q->is_main_query() && ( $q->is_home() || $q->is_category() ) ) {
			$q->set( 'posts_per_page', 9 );
		}
	}
);

/**
 * Styles et scripts de chaque page (déclarés dans inc/elementor.php).
 *
 * @return array{styles: string[], scripts: string[]}
 */
function bigm_page_assets(): array {
	if ( is_post_type_archive( 'bigm_produit' ) ) {
		return array(
			'styles'  => array( 'bigm-barre-categories', 'bigm-carte', 'bigm-slider', 'bigm-tuiles' ),
			'scripts' => array( 'bigm-slider', 'bigm-tuiles-video' ),
		);
	}
	if ( is_tax( 'bigm_categorie' ) ) {
		return array(
			'styles'  => array( 'bigm-barre-categories', 'bigm-carte', 'bigm-slider', 'bigm-tuiles', 'bigm-produits' ),
			'scripts' => array( 'bigm-slider', 'bigm-tuiles-video', 'bigm-filtres' ),
		);
	}
	if ( is_home() || is_category() || is_singular( 'post' ) ) {
		return array(
			'styles'  => array( 'bigm-carte', 'bigm-articles' ),
			'scripts' => array(),
		);
	}
	if ( is_singular( 'bigm_produit' ) ) {
		return array(
			'styles'  => array( 'bigm-tuiles', 'bigm-fiche' ),
			'scripts' => array( 'bigm-tuiles-video' ),
		);
	}
	return array(
		'styles'  => array(),
		'scripts' => array(),
	);
}

/*
 * Ces pages chargent leurs styles dans le <head>, qu'elles soient rendues par le thème
 * ou par un modèle du Theme Builder (les widgets demandent les mêmes fichiers).
 * Priorité 25 : après la base du thème (20, 21), comme le <style> de chaque page du prototype
 * vient après site.css.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$assets = bigm_page_assets();
		array_map( 'wp_enqueue_style', $assets['styles'] );
		array_map( 'wp_enqueue_script', $assets['scripts'] );
	},
	25
);

/*
 * Titre de l'onglet : « Notre carte » et « Nos burgers », comme dans le prototype,
 * plutôt que le nom du type de contenu (« Produits ») et de la catégorie (« Burgers »).
 */
add_filter(
	'document_title_parts',
	static function ( array $parts ): array {
		if ( is_post_type_archive( 'bigm_produit' ) ) {
			$parts['title'] = (string) bigm_reglage( 'carte_titre' );
		} elseif ( is_tax( 'bigm_categorie' ) ) {
			$cat            = bigm_categorie( get_queried_object() );
			$parts['title'] = $cat ? $cat['titre'] : $parts['title'];
		}
		return $parts;
	}
);

/*
 * Formulaires Elementor Pro (franchise, recrutement) : habillage de la charte et champ téléphone,
 * dès le <head>, sur les pages qui ont un formulaire.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$id = is_singular() ? (int) get_queried_object_id() : 0;
		if ( $id && str_contains( (string) get_post_meta( $id, '_elementor_data', true ), '"widgetType":"form"' ) ) {
			wp_enqueue_style( 'bigm-formulaires' );
			wp_enqueue_script( 'bigm-formulaires' );
		}
	},
	25
);


/*
 * Vidéos YouTube intégrées (articles repris de l'ancien site) : mode « confidentialité renforcée »,
 * sans cookie avant la lecture (page Gestion des cookies).
 */
add_filter(
	'embed_oembed_html',
	static fn( $html ) => is_string( $html ) ? str_replace( array( 'https://www.youtube.com/embed/', 'https://youtube.com/embed/' ), 'https://www.youtube-nocookie.com/embed/', $html ) : $html
);
