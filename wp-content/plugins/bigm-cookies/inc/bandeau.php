<?php
/**
 * Le bandeau : premier niveau (texte, « Tout refuser », « Personnaliser », « Tout accepter »),
 * second niveau (une case par finalité, la liste des cookies), lien « Paramétrer les cookies » du
 * pied de page pour changer d'avis, codes courts pour la page de la politique.
 *
 * Imprimé au début de la page (wp_body_open, premier élément atteint au clavier), caché tant que
 * le script n'a pas lu le choix du visiteur : sans JavaScript, rien n'est accepté ni chargé.
 *
 * @package BigM\Cookies
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le bandeau doit-il s'afficher sur cette page ?
 */
function bigm_cookies_actif(): bool {
	$actif = ! is_admin() && ! wp_doing_ajax() && ! is_feed() && ! is_embed() && ! ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() );
	// Aperçu et éditeur d'Elementor : pas de bandeau par-dessus la page qu'on modifie.
	if ( $actif && isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$actif = false;
	}
	return (bool) apply_filters( 'bigm_cookies_actif', $actif );
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! bigm_cookies_actif() ) {
			return;
		}
		wp_enqueue_style( 'bigm-cookies', BIGM_COOKIES_URL . 'assets/cookies.css', array(), BIGM_COOKIES_VERSION );
		wp_enqueue_script(
			'bigm-cookies',
			BIGM_COOKIES_URL . 'assets/cookies.js',
			array(),
			BIGM_COOKIES_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

/**
 * Tableau des cookies d'une finalité.
 *
 * @param array<int, array{0: string, 1: string, 2: string}> $cookies Nom, déposé par, rôle et durée.
 */
function bigm_cookies_tableau( array $cookies ): string {
	$lignes = '';
	foreach ( $cookies as $c ) {
		$lignes .= '<tr><td>' . esc_html( $c[0] ) . '</td><td>' . esc_html( $c[1] ) . '</td><td>' . esc_html( $c[2] ) . '</td></tr>';
	}
	return '<table class="bigm-cookies__table"><thead><tr><th scope="col">Cookie</th><th scope="col">Déposé par</th><th scope="col">Rôle et durée</th></tr></thead><tbody>' . $lignes . '</tbody></table>';
}

/**
 * HTML du bandeau.
 */
function bigm_cookies_html(): string {
	$r         = bigm_cookies_reglages();
	$politique = (int) $r['politique'] && 'publish' === get_post_status( (int) $r['politique'] ) ? (string) get_permalink( (int) $r['politique'] ) : '';
	$cats      = '';
	foreach ( bigm_cookies_categories() as $cle => $cat ) {
		$id    = 'bigm-cookies-' . $cle;
		$cats .= '<div class="bigm-cookies__cat">'
			. '<div class="bigm-cookies__cat-tete"><label for="' . esc_attr( $id ) . '">' . esc_html( $cat['nom'] ) . '</label>'
			. ( $cat['toujours']
				? '<span class="bigm-cookies__toujours">Toujours actifs</span>'
				: '<input type="checkbox" role="switch" class="bigm-cookies__interrupteur" id="' . esc_attr( $id ) . '" data-bigm-cookies-cat="' . esc_attr( $cle ) . '" aria-describedby="' . esc_attr( $id ) . '-texte">' )
			. '</div>'
			. '<p id="' . esc_attr( $id ) . '-texte">' . esc_html( $cat['texte'] ) . '</p>'
			. '<details><summary>Voir les cookies</summary>' . bigm_cookies_tableau( $cat['cookies'] ) . '</details>'
			. '</div>';
	}
	return '<div class="bigm-cookies" id="bigm-cookies" role="dialog" aria-modal="false" aria-labelledby="bigm-cookies-titre" aria-describedby="bigm-cookies-texte" data-version="' . esc_attr( (string) $r['version'] ) . '" data-duree="' . (int) $r['duree'] . '" hidden>'
		. '<div class="bigm-cookies__carte">'
		. '<h2 class="bigm-cookies__titre" id="bigm-cookies-titre" tabindex="-1">' . esc_html( $r['titre'] ) . '</h2>'
		. '<p class="bigm-cookies__texte" id="bigm-cookies-texte">' . esc_html( $r['texte'] ) . ( '' !== $politique ? ' <a href="' . esc_url( $politique ) . '">En savoir plus</a>' : '' ) . '</p>'
		. '<div class="bigm-cookies__choix" id="bigm-cookies-choix" hidden>' . $cats . '</div>'
		. '<div class="bigm-cookies__boutons">'
		. '<button type="button" class="bigm-cookies__btn" data-bigm-cookies-action="refuser">Tout refuser</button>'
		. '<button type="button" class="bigm-cookies__btn bigm-cookies__btn--lien" data-bigm-cookies-action="personnaliser" aria-expanded="false" aria-controls="bigm-cookies-choix">Personnaliser</button>'
		. '<button type="button" class="bigm-cookies__btn" data-bigm-cookies-action="enregistrer" hidden>Enregistrer mes choix</button>'
		. '<button type="button" class="bigm-cookies__btn" data-bigm-cookies-action="accepter">Tout accepter</button>'
		. '</div>'
		. '<p class="bigm-cookies__statut" role="status" aria-live="polite"></p>'
		. '</div></div>';
}

/*
 * Au début de la page, une seule fois ; à défaut (thème sans wp_body_open), en pied de page.
 */
$bigm_cookies_imprimer = static function () {
	static $fait = false;
	if ( $fait || ! bigm_cookies_actif() ) {
		return;
	}
	$fait = true;
	echo bigm_cookies_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_cookies_html().
};
add_action( 'wp_body_open', $bigm_cookies_imprimer, 1 );
add_action( 'wp_footer', $bigm_cookies_imprimer, 1 );
unset( $bigm_cookies_imprimer );

/*
 * Lien « Paramétrer les cookies » dans la colonne des liens légaux du pied de page (thème bigm-child).
 */
add_filter(
	'bigm_child_liens',
	static function ( array $liens, string $emplacement ): array {
		if ( 'bigm-pied-legal' !== $emplacement ) {
			return $liens;
		}
		foreach ( $liens as $lien ) {
			if ( str_ends_with( (string) $lien['url'], '#gestion-cookies' ) ) {
				return $liens; // Déjà dans le menu.
			}
		}
		$liens[] = array(
			'titre'   => 'Paramétrer les cookies',
			'url'     => '#gestion-cookies',
			'cible'   => '',
			'icone'   => '',
			'courant' => false,
		);
		return $liens;
	},
	10,
	2
);

/*
 * [bigm_cookies_gestion] : bouton qui rouvre le bandeau. [bigm_cookies_liste] : les cookies du site.
 */
add_shortcode(
	'bigm_cookies_gestion',
	static function ( $atts ): string {
		$a = shortcode_atts( array( 'texte' => 'Gérer mes cookies' ), (array) $atts );
		return '<button type="button" class="btn" data-bigm-cookies-ouvrir>' . esc_html( (string) $a['texte'] ) . '</button>';
	}
);
add_shortcode(
	'bigm_cookies_liste',
	static function (): string {
		$html = '';
		foreach ( bigm_cookies_categories() as $cat ) {
			$html .= '<h3>' . esc_html( $cat['nom'] ) . ( $cat['toujours'] ? ' (toujours actifs)' : '' ) . '</h3><p>' . esc_html( $cat['texte'] ) . '</p>' . bigm_cookies_tableau( $cat['cookies'] );
		}
		return '<div class="bigm-cookies-liste">' . $html . '</div>';
	}
);
