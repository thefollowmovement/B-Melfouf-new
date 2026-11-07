<?php
/**
 * Thème enfant Big M (parent : Hello Elementor).
 *
 * Le thème porte la charte : tokens, polices, styles et scripts communs.
 * Les types de contenu, champs et widgets sont dans le plugin bigm-core.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_CHILD_VERSION', '0.6.2' );
define( 'BIGM_CHILD_DIR', get_stylesheet_directory() );
define( 'BIGM_CHILD_URI', get_stylesheet_directory_uri() );

require BIGM_CHILD_DIR . '/inc/tokens.php';
require BIGM_CHILD_DIR . '/inc/setup.php';
require BIGM_CHILD_DIR . '/inc/assets.php';
require BIGM_CHILD_DIR . '/inc/elementor.php';
require BIGM_CHILD_DIR . '/inc/icones.php';
require BIGM_CHILD_DIR . '/inc/navigation.php';
require BIGM_CHILD_DIR . '/inc/chrome.php';
