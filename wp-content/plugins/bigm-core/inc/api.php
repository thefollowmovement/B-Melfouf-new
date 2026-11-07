<?php
/**
 * Lecture des contenus, pour les widgets et les gabarits.
 * Les widgets passent par ces fonctions plutôt que par get_post_meta().
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL d'une pièce jointe, ou chaîne vide.
 *
 * @param int $id Pièce jointe.
 */
function bigm_media_url( int $id ): string {
	return $id ? (string) wp_get_attachment_url( $id ) : '';
}

/**
 * Vidéo de vignette avec ses URL, ou null s'il n'y en a pas.
 *
 * @param mixed $meta Valeur enregistrée.
 * @return array{mp4: string, webm: string, poster: string, poster_id: int}|null
 */
function bigm_video( $meta ): ?array {
	$v = bigm_nettoyer_video( $meta );
	if ( ! $v['mp4'] && ! $v['webm'] ) {
		return null;
	}
	return array(
		'mp4'       => bigm_media_url( $v['mp4'] ),
		'webm'      => bigm_media_url( $v['webm'] ),
		'poster'    => bigm_media_url( $v['poster'] ),
		'poster_id' => $v['poster'],
	);
}

/**
 * Lien de commande : celui du produit, sinon le lien général.
 *
 * @param int|null $post_id Produit, ou null pour le lien général.
 */
function bigm_lien_commande( ?int $post_id = null ): string {
	$lien = $post_id ? (string) get_post_meta( $post_id, '_bigm_lien_commande', true ) : '';
	return $lien ? $lien : (string) bigm_reglage( 'commander_url' );
}

/**
 * Toutes les données d'un produit.
 *
 * @param int|WP_Post $post Produit.
 * @return array<string, mixed>|null
 */
function bigm_produit( $post ): ?array {
	$post = get_post( $post );
	if ( ! $post || 'bigm_produit' !== $post->post_type ) {
		return null;
	}
	$id        = $post->ID;
	$allergens = bigm_allergenes();
	$label     = static fn( array $slugs ): array => array_values( array_intersect_key( wp_list_pluck( $allergens, 'label' ), array_flip( $slugs ) ) );
	$contient  = array_values( array_intersect( array_keys( $allergens ), (array) get_post_meta( $id, '_bigm_allergenes_contient', true ) ) );
	$traces    = array_values( array_intersect( array_keys( $allergens ), (array) get_post_meta( $id, '_bigm_allergenes_traces', true ) ) );
	$nutrition = (array) get_post_meta( $id, '_bigm_nutrition', true );
	$terms     = get_the_terms( $id, 'bigm_categorie' );

	// Lignes du tableau nutritionnel : seulement celles qui ont au moins une valeur.
	$lignes = array();
	foreach ( bigm_nutrition_lignes() as $cle => $libelle ) {
		$portion = (string) ( $nutrition['portion'][ $cle ] ?? '' );
		$cent    = (string) ( $nutrition['100g'][ $cle ] ?? '' );
		if ( '' !== $portion || '' !== $cent ) {
			$lignes[ $cle ] = array(
				'label'   => $libelle,
				'portion' => $portion,
				'100g'    => $cent,
			);
		}
	}

	return array(
		'id'             => $id,
		'titre'          => get_the_title( $post ),
		'url'            => (string) get_permalink( $post ),
		'description'    => wpautop( wp_kses_post( $post->post_content ) ),
		'image_id'       => (int) get_post_thumbnail_id( $post ),
		'video'          => bigm_video( get_post_meta( $id, '_bigm_video', true ) ),
		'badge'          => (string) get_post_meta( $id, '_bigm_badge', true ),
		'viande'         => (string) get_post_meta( $id, '_bigm_viande', true ),
		'ingredients'    => array_values( array_filter( (array) get_post_meta( $id, '_bigm_ingredients', true ) ) ),
		'allergenes'     => array(
			'contient'        => $contient,
			'traces'          => $traces,
			'contient_labels' => $label( $contient ),
			'traces_labels'   => $label( $traces ),
		),
		'nutrition'      => array(
			'portion_g' => (string) ( $nutrition['portion_g'] ?? '' ),
			'lignes'    => $lignes,
		),
		'associes'       => array_values(
			array_filter(
				array_map( 'intval', (array) get_post_meta( $id, '_bigm_associes', true ) ),
				static fn( int $a ): bool => 'publish' === get_post_status( $a )
			)
		),
		'lien_commande'  => bigm_lien_commande( $id ),
		'categories_ids' => is_array( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array(),
	);
}

/**
 * Données d'une catégorie de la carte.
 *
 * @param int|WP_Term $term Catégorie.
 * @return array<string, mixed>|null
 */
function bigm_categorie( $term ): ?array {
	$term = get_term( $term, 'bigm_categorie' );
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$id     = $term->term_id;
	$titre  = (string) get_term_meta( $id, '_bigm_titre', true );
	$slider = (int) get_term_meta( $id, '_bigm_slider', true );
	$ordre  = get_term_meta( $id, '_bigm_ordre', true );
	return array(
		'id'          => $id,
		'nom'         => $term->name,
		'slug'        => $term->slug,
		'titre'       => $titre ? $titre : $term->name,
		'description' => $term->description,
		'url'         => (string) get_term_link( $term ),
		'parent'      => $term->parent,
		'ordre'       => '' === $ordre ? null : (int) $ordre,
		'icone'       => bigm_nettoyer_svg( (string) get_term_meta( $id, '_bigm_icone', true ) ),
		'image_id'    => bigm_nettoyer_media( get_term_meta( $id, '_bigm_image', true ) ),
		'video'       => bigm_video( get_term_meta( $id, '_bigm_video', true ) ),
		'slider_id'   => 'bigm_slider' === get_post_type( $slider ) ? $slider : 0,
	);
}

/**
 * Catégories dans l'ordre choisi (champ « Ordre d'affichage », puis nom).
 *
 * @param int  $parent     Catégorie parente (0 : premier niveau).
 * @param bool $hide_empty Masquer les catégories sans produit publié.
 * @return WP_Term[]
 */
function bigm_categories( int $parent = 0, bool $hide_empty = false ): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'bigm_categorie',
			'parent'     => $parent,
			'hide_empty' => $hide_empty,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$rang = static function ( WP_Term $t ): int {
		$o = get_term_meta( $t->term_id, '_bigm_ordre', true );
		return '' === $o ? PHP_INT_MAX : (int) $o;
	};
	usort( $terms, static fn( WP_Term $a, WP_Term $b ): int => array( $rang( $a ), $a->name ) <=> array( $rang( $b ), $b->name ) );
	return $terms;
}

/**
 * Produits d'une catégorie (et de ses sous-catégories), dans l'ordre manuel.
 *
 * @param int $term_id Catégorie.
 * @return WP_Post[]
 */
function bigm_produits_de( int $term_id ): array {
	return get_posts(
		array(
			'post_type'      => 'bigm_produit',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'bigm_categorie',
					'terms'            => $term_id,
					'include_children' => true,
				),
			),
		)
	);
}

/**
 * Slides visibles d'un slider à un instant donné (dates de début et de fin respectées),
 * avec les URL des médias. Une slide incomplète (vidéo sans fichier, photo sans image) est ignorée.
 *
 * @param int         $slider_id Slider.
 * @param string|null $maintenant « AAAA-MM-JJ HH:MM » à l'heure du site ; par défaut l'heure actuelle.
 * @return array<int, array<string, mixed>>
 */
function bigm_slides_actives( int $slider_id, ?string $maintenant = null ): array {
	if ( 'bigm_slider' !== get_post_type( $slider_id ) || 'publish' !== get_post_status( $slider_id ) ) {
		return array();
	}
	$maintenant = $maintenant ?? current_time( 'Y-m-d H:i' );
	$slides     = array();
	foreach ( (array) get_post_meta( $slider_id, '_bigm_slides', true ) as $s ) {
		if ( ! is_array( $s ) ) {
			continue;
		}
		$s = array_merge( bigm_slide_vide(), $s );
		if ( ( $s['debut'] && $s['debut'] > $maintenant ) || ( $s['fin'] && $s['fin'] <= $maintenant ) ) {
			continue;
		}
		$video = 'video' === $s['type'];
		if ( $video ? ! ( $s['mp4'] || $s['webm'] ) : ! ( $s['image'] || $s['image_mobile'] ) ) {
			continue;
		}
		$slides[] = array(
			'type'            => $video ? 'video' : 'image',
			'image_id'        => (int) ( $s['image'] ? $s['image'] : $s['image_mobile'] ),
			'image_mobile_id' => (int) $s['image_mobile'],
			'mp4'             => $video ? bigm_media_url( (int) $s['mp4'] ) : '',
			'webm'            => $video ? bigm_media_url( (int) $s['webm'] ) : '',
			// Image d'aperçu à 1536 px au plus : l'original peut peser plusieurs centaines de Ko.
			'poster'          => $video && $s['poster'] ? (string) ( wp_get_attachment_image_url( (int) $s['poster'], '1536x1536' ) ?: bigm_media_url( (int) $s['poster'] ) ) : '',
			'alt'             => (string) $s['alt'],
			'lien'            => (string) $s['lien'],
			'cadrage'         => $s['cadrage'] ? (string) $s['cadrage'] : '50% 50%',
		);
	}
	return $slides;
}

/**
 * Vignette d'un produit (.tile du prototype) : photo, ou vidéo de vignette lue en boucle.
 *
 * @param int    $post_id Produit.
 * @param string $sizes   Attribut sizes de l'image.
 */
function bigm_tuile_produit( int $post_id, string $sizes = '(max-width: 960px) 46vw, 300px' ): string {
	$p = bigm_produit( $post_id );
	if ( ! $p ) {
		return '';
	}
	$img = $p['image_id'] ? wp_get_attachment_image(
		$p['image_id'],
		'medium_large',
		false,
		array(
			'alt'   => '',
			'sizes' => $sizes,
		)
	) : '';
	$video = bigm_video_vignette( $p['video'] );
	return '<a class="tile' . ( $video ? ' has-video' : '' ) . '" href="' . esc_url( $p['url'] ) . '"><div class="tile__img">' . $img . $video . '</div><span class="tile__label">' . esc_html( $p['titre'] ) . '</span></a>';
}
