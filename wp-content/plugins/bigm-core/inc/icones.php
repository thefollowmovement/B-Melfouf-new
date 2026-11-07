<?php
/**
 * Icônes SVG du prototype utilisées par les widgets (prototype/build.py, dictionnaire ICON).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icône SVG décorative (aria-hidden).
 *
 * @param string $nom left, right, arrow, pin, bag, apple ou play.
 */
function bigm_core_icone( string $nom ): string {
	$icones = array(
		'left'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>',
		'right' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>',
		'arrow' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>',
		'pin'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
		'bag'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>',
		'apple' => '<svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M16.4 12.6c0-2.4 2-3.5 2-3.6-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9s-1.8-.9-3-.8C7 7.3 5.6 8.2 4.8 9.6c-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.7 2.3 2.9 2.3s1.6-.7 3-.7 1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.6zM14.1 5.6c.6-.8 1.1-1.8 1-2.9-.9 0-2.1.6-2.7 1.4-.6.7-1.1 1.8-1 2.8 1 .1 2.1-.5 2.7-1.3z"/></svg>',
		'play'  => '<svg viewBox="0 0 24 26" aria-hidden="true"><path d="M3 2l11 11L3 24c-.6-.3-1-.9-1-1.6V3.6C2 2.9 2.4 2.3 3 2z" fill="#34A853"/><path d="M14 13l3.5-3.5 3.6 2c1.2.7 1.2 2.3 0 3l-3.6 2L14 13z" fill="#FBBC04"/><path d="M3 2c.4-.2 1-.2 1.5.1l13 7.4L14 13 3 2z" fill="#4285F4"/><path d="M3 24l11-11 3.5 3.5-13 7.4c-.5.3-1.1.3-1.5.1z" fill="#EA4335"/></svg>',
	);
	return $icones[ $nom ] ?? '';
}

/**
 * Badges App Store et Google Play, avec les liens des Réglages Big M.
 * Un lien vide masque son badge.
 */
function bigm_core_stores_html(): string {
	$html = '';
	$ios  = (string) bigm_reglage( 'appstore_url' );
	$and  = (string) bigm_reglage( 'googleplay_url' );
	if ( $ios ) {
		$html .= '<a class="store" href="' . esc_url( $ios ) . '">' . bigm_core_icone( 'apple' ) . '<span><small>Télécharger dans</small><b>l\'App Store</b></span></a>';
	}
	if ( $and ) {
		$html .= '<a class="store" href="' . esc_url( $and ) . '">' . bigm_core_icone( 'play' ) . '<span><small>Disponible sur</small><b>Google Play</b></span></a>';
	}
	return $html ? '<div class="stores">' . $html . '</div>' : '';
}
