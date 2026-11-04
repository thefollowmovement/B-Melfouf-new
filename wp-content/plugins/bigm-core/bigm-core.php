<?php
/**
 * Plugin Name:       Big M — cœur
 * Description:       Contenus de Big M gérés dans l'admin : produits et catégories de la carte, sliders et bannières, réglages (lien Commander, applis, réseaux sociaux, chiffres de la franchise).
 * Author:            The Follow Movement
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * License:           GPL-2.0-or-later
 * Text Domain:       bigm-core
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_CORE_VERSION', '0.1.0' );
define( 'BIGM_CORE_FILE', __FILE__ );
define( 'BIGM_CORE_DIR', __DIR__ );
define( 'BIGM_CORE_URL', plugin_dir_url( __FILE__ ) );

require BIGM_CORE_DIR . '/inc/donnees.php';
require BIGM_CORE_DIR . '/inc/champs.php';
require BIGM_CORE_DIR . '/inc/produit.php';
require BIGM_CORE_DIR . '/inc/categorie.php';
require BIGM_CORE_DIR . '/inc/slider.php';
require BIGM_CORE_DIR . '/inc/reglages.php';
require BIGM_CORE_DIR . '/inc/api.php';
require BIGM_CORE_DIR . '/inc/activation.php';

register_activation_hook( __FILE__, 'bigm_core_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
