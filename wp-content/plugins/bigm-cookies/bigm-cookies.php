<?php
/**
 * Plugin Name:       Big M — Cookies
 * Description:       Bandeau de consentement aux cookies conforme au RGPD et aux règles de la CNIL : « Tout refuser » aussi simple que « Tout accepter », choix par finalité, rien n'est déposé avant l'accord (Google Analytics de Site Kit compris), choix gardé 6 mois et modifiable à tout moment.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            The Follow Movement
 * Text Domain:       bigm-cookies
 *
 * @package BigM\Cookies
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGM_COOKIES_VERSION', '1.0.0' );
define( 'BIGM_COOKIES_FILE', __FILE__ );
define( 'BIGM_COOKIES_URL', plugin_dir_url( __FILE__ ) );

require __DIR__ . '/inc/reglages.php';
require __DIR__ . '/inc/blocage.php';
require __DIR__ . '/inc/bandeau.php';
