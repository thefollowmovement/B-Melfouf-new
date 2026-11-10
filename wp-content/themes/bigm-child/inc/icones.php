<?php
/**
 * Icônes SVG du prototype (prototype/build.py, dictionnaire ICON),
 * plus Facebook et YouTube dessinées dans le même style.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icône SVG, prête à insérer (décorative : aria-hidden).
 *
 * @param string $nom Nom de l'icône.
 */
function bigm_child_icone( string $nom ): string {
	$trait = static fn( string $corps, string $largeur = '2.2' ): string => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . $largeur . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $corps . '</svg>';

	switch ( $nom ) {
		case 'pin':
			return $trait( '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>' );
		case 'bag':
			return $trait( '<path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>' );
		case 'panier':
			// Panier à anse arrondie et trois barres (icône Commander de l'en-tête mobile).
			return $trait( '<path d="M6.2 9V8a5.8 5.8 0 0 1 11.6 0v1"/><path d="M3.5 9h17a1.5 1.5 0 0 1 1.46 1.85l-2.2 8.9a2.5 2.5 0 0 1-2.43 1.85H6.67a2.5 2.5 0 0 1-2.43-1.9l-2.2-8.85A1.5 1.5 0 0 1 3.5 9z"/><path d="M7.7 13v4.6M12 13v4.6M16.3 13v4.6"/>' );
		case 'menu':
			return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
		case 'close':
			return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
		case 'apple':
			return '<svg viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M16.4 12.6c0-2.4 2-3.5 2-3.6-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9s-1.8-.9-3-.8C7 7.3 5.6 8.2 4.8 9.6c-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.7 2.3 2.9 2.3s1.6-.7 3-.7 1.8.7 3 .7c1.3 0 2.1-1.1 2.8-2.3.9-1.3 1.3-2.5 1.3-2.6 0 0-2.5-1-2.5-3.6zM14.1 5.6c.6-.8 1.1-1.8 1-2.9-.9 0-2.1.6-2.7 1.4-.6.7-1.1 1.8-1 2.8 1 .1 2.1-.5 2.7-1.3z"/></svg>';
		case 'play':
			return '<svg viewBox="0 0 24 26" aria-hidden="true"><path d="M3 2l11 11L3 24c-.6-.3-1-.9-1-1.6V3.6C2 2.9 2.4 2.3 3 2z" fill="#34A853"/><path d="M14 13l3.5-3.5 3.6 2c1.2.7 1.2 2.3 0 3l-3.6 2L14 13z" fill="#FBBC04"/><path d="M3 2c.4-.2 1-.2 1.5.1l13 7.4L14 13 3 2z" fill="#4285F4"/><path d="M3 24l11-11 3.5 3.5-13 7.4c-.5.3-1.1.3-1.5.1z" fill="#EA4335"/></svg>';
		case 'instagram':
			return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor"/></svg>';
		case 'x':
			return $trait( '<path d="M4 4l16 16M20 4L4 20"/>' );
		case 'snapchat':
			return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c3 0 5 2.2 5 5.2v2.3l1.8.6-.9 1.6 1.6 1.2c-1.4.9-3 .9-3.7 1.9-.5.8-1.3 1.7-3.8 1.7s-3.3-.9-3.8-1.7c-.7-1-2.3-1-3.7-1.9l1.6-1.2-.9-1.6 1.8-.6V8.2C7 5.2 9 3 12 3z"/></svg>';
		case 'tiktok':
			return $trait( '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.4 2.6 2.2 4.4 5 4.6"/>', '2' );
		case 'facebook':
			return $trait( '<path d="M15 3.5h-2.2A3.8 3.8 0 0 0 9 7.3V10H6.8v3.5H9V21h3.6v-7.5h2.6l.5-3.5h-3.1V7.6c0-.6.4-1 1-1H15z"/>', '1.9' );
		case 'youtube':
			return $trait( '<rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.3v5.4l4.6-2.7z" fill="currentColor"/>', '1.9' );
		// Barre d'onglets mobile : tracés de prototype/build.py, fonction tabbar().
		case 'accueil':
		case 'carte':
		case 'restos':
		case 'plus':
			$traces = array(
				'accueil' => 'M4 11l8-7 8 7v9h-5v-6H9v6H4z',
				'carte'   => 'M4 10a8 6 0 0 1 16 0z M3 13.5h18 M4.5 17h15v.5a2.5 2.5 0 0 1-2.5 2.5H7a2.5 2.5 0 0 1-2.5-2.5z',
				'restos'  => 'M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
				'plus'    => 'M4 7h16M4 12h16M4 17h16',
			);
			return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="tabbar__ico" d="' . $traces[ $nom ] . '"/></svg>';
	}
	return '';
}
