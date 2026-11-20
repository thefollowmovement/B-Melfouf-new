<?php
/**
 * Charte Big M, côté PHP.
 *
 * Mêmes valeurs que assets/css/tokens.css. Elles servent à remplir les
 * couleurs et les polices globales d'Elementor (voir inc/elementor.php).
 * Si une couleur change, la changer aux deux endroits :
 * tests/check-tokens.php vérifie qu'ils concordent.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Couleurs de la charte, indexées par le nom de la variable CSS (sans « -- »).
 *
 * @return array<string, string>
 */
function bigm_child_colors(): array {
	return array(
		'bg'         => '#faf8f5', // Fond blanc cassé.
		'tile'       => '#f2ede7', // Fond des vignettes.
		'card'       => '#ffffff', // Cartes.
		'ink'        => '#2b1406', // Texte, pied de page.
		'muted'      => '#6b4a3a', // Texte secondaire.
		'orange'     => '#fa5c19', // Grands aplats (bande appli, bouton central mobile).
		'btn'        => '#c8400a', // Boutons, texte blanc (contraste AA).
		'btn-hover'  => '#a93506', // Boutons au survol.
		'yellow'     => '#ffc21a', // Badges « Nouveau ».
		'footer'     => '#2b1406', // Fond du pied de page.
		'footer-ink' => '#f6e4d6', // Texte du pied de page.
	);
}

/**
 * Familles de polices, auto-hébergées dans assets/fonts/.
 *
 * @return array{display: string, text: string}
 */
function bigm_child_fonts(): array {
	return array(
		'display' => 'Fredoka', // Titres et boutons, 500 et 600.
		'text'    => 'Figtree', // Texte courant, 400 et 600.
	);
}
