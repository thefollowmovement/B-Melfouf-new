<?php
/**
 * Pied de page Big M, à la place de celui de Hello Elementor.
 * Non utilisé quand un pied de page du Theme Builder d'Elementor Pro est actif.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

echo bigm_child_pied_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
