<?php
/**
 * Page /carte/ (archive des produits), telle que prototype/carte.html la montre.
 * Le contenu vient de bigm-core (bigm_page_carte()). Un modèle « Archive » du Theme Builder
 * d'Elementor Pro, s'il vise cette page, le remplace.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! bigm_child_emplacement( 'archive' ) ) {
	if ( function_exists( 'bigm_page_carte' ) ) {
		echo bigm_page_carte(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé par bigm-core.
	}
}
get_footer();
