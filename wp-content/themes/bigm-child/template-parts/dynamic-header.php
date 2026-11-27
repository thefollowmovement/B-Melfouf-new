<?php
/**
 * En-tête Big M, à la place de celui de Hello Elementor.
 * Non utilisé quand un en-tête du Theme Builder d'Elementor Pro est actif.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

echo bigm_child_entete_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
