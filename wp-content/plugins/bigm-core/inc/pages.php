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
