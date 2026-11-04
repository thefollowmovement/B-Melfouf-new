<?php
/**
 * Une catégorie d'articles (News, Presse), avec les pastilles des catégories.
 * Le contenu vient de bigm-core (bigm_page_actualites()). Un modèle « Archive » du Theme Builder
 * d'Elementor Pro, s'il vise cette page, le remplace.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'archive' ) ) {
	if ( function_exists( 'bigm_page_actualites' ) ) {
		echo bigm_page_actualites(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé par bigm-core.
	} else {
		get_template_part( 'template-parts/archive' );
	}
}
get_footer();
