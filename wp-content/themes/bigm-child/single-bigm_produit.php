<?php
/**
 * Fiche produit (/produit/smash-naan/), telle que prototype/produit.html la montre.
 * Le contenu vient de bigm-core (bigm_page_produit()). Un modèle « Publication unique » du
 * Theme Builder d'Elementor Pro, s'il vise les produits, le remplace.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! bigm_child_emplacement( 'single' ) ) {
	if ( function_exists( 'bigm_page_produit' ) ) {
		echo bigm_page_produit( (int) get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé par bigm-core.
	}
}
get_footer();
