<?php
/**
 * Un article des actualités : retour, catégorie et date, titre, photo, texte, « À lire aussi ».
 * Le contenu vient de bigm-core (bigm_page_article()). Un modèle « Publication unique » du
 * Theme Builder d'Elementor Pro, s'il vise les articles, le remplace.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! bigm_child_emplacement( 'single' ) ) {
	if ( function_exists( 'bigm_page_article' ) ) {
		while ( have_posts() ) {
			the_post();
			echo bigm_page_article(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé par bigm-core.
		}
	} else {
		get_template_part( 'template-parts/single' );
	}
}
get_footer();
