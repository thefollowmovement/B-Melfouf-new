<?php
/**
 * /restaurants/ : la page « Restaurants » (Elementor, avec le store locator du plugin StoreLocatore)
 * à la place de la liste du plugin. Choisi par bigm-core (inc/restaurants.php) quand cette page existe.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'archive' ) ) {
	if ( function_exists( 'bigm_page_restaurants' ) ) {
		echo '<main id="content">' . bigm_page_restaurants() . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenu Elementor de la page.
	}
}
get_footer();
