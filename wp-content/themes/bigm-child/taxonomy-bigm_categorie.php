<?php
/**
 * Page d'une catégorie de la carte (/carte/burgers/), telle que prototype/categorie.html la montre.
 * Le contenu vient de bigm-core (bigm_page_categorie()). Un modèle « Archive » du Theme Builder
 * d'Elementor Pro, s'il vise cette page, le remplace.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'archive' ) ) {
	$bigm_term = get_queried_object();
	if ( function_exists( 'bigm_page_categorie' ) && $bigm_term instanceof WP_Term ) {
		echo bigm_page_categorie( $bigm_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé par bigm-core.
	}
}
get_footer();
