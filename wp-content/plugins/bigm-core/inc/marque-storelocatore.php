<?php
/**
 * Habillage Big M des parties de StoreLocatore (2.6 ou plus) qui ne passent pas par le thème :
 *
 * - page d'avis /avis/{restaurant}/ (QR code) : polices Fredoka et Figtree, couleurs de la
 *   charte (action tfmsl_rb_page_head) ;
 * - affiches QR imprimables : polices de la charte (filtre tfmsl_poster_style) ;
 * - réseaux sociaux d'un restaurant : ceux de Big M (Réglages Big M → Réseaux sociaux) quand sa
 *   fiche n'en a pas (filtre tfmsl_store_socials) ;
 * - pastille « Nouveau » des restaurants : jaune de la charte, texte brun.
 *
 * Sans StoreLocatore 2.6, ces crochets ne sont jamais appelés : rien ne change.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Couleurs de la charte (prototype/assets/site.css).
 *
 * @return array<string, string>
 */
function bigm_marque_couleurs(): array {
	return array(
		'fond'   => '#faf8f5',
		'tuile'  => '#f2ede7',
		'encre'  => '#2b1406',
		'doux'   => '#6b4a3a',
		'orange' => '#fa5c19',
		'bouton' => '#c8400a',
		'jaune'  => '#ffc21a',
	);
}

/**
 * Règles @font-face des polices auto-hébergées du thème (Fredoka 500/600, Figtree 400/600).
 */
function bigm_marque_polices_css(): string {
	$base   = function_exists( 'bigm_child_asset_url' ) ? bigm_child_asset_url( 'assets/fonts/' ) : trailingslashit( get_stylesheet_directory_uri() ) . 'assets/fonts/';
	$css    = '';
	$polices = array(
		array( 'Fredoka', 500, 'fredoka-latin-500-normal.woff2' ),
		array( 'Fredoka', 600, 'fredoka-latin-600-normal.woff2' ),
		array( 'Figtree', 400, 'figtree-latin-400-normal.woff2' ),
		array( 'Figtree', 600, 'figtree-latin-600-normal.woff2' ),
	);
	foreach ( $polices as list( $famille, $graisse, $fichier ) ) {
		$css .= '@font-face{font-family:"' . $famille . '";font-style:normal;font-weight:' . $graisse . ';font-display:swap;src:url("' . esc_url_raw( $base . $fichier ) . '") format("woff2")}';
	}
	return $css;
}

/**
 * Familles de polices (titres, texte), avec leurs replis.
 *
 * @return array{titre: string, texte: string}
 */
function bigm_marque_familles(): array {
	return array(
		'titre' => '"Fredoka", "Arial Rounded MT Bold", ui-rounded, system-ui, sans-serif',
		'texte' => '"Figtree", ui-sans-serif, system-ui, sans-serif',
	);
}

/*
 * Page d'avis : polices et couleurs de la charte.
 */
add_action(
	'tfmsl_rb_page_head',
	static function () {
		$c = bigm_marque_couleurs();
		$f = bigm_marque_familles();
		echo '<style id="bigm-page-avis">' . bigm_marque_polices_css() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS construit ici, adresses échappées.
			. ':root{--tfmsl-rb-accent:' . $c['bouton'] . ';--tfmsl-rb-font:' . $f['texte'] . ';--tfmsl-rb-font-title:' . $f['titre'] . ';--tfmsl-rb-ink:' . $c['encre'] . ';--tfmsl-rb-bg:' . $c['fond'] . ';--tfmsl-rb-card:#ffffff;--tfmsl-rb-btn-text:#ffffff;--tfmsl-new-bg:' . $c['jaune'] . ';--tfmsl-new-fg:' . $c['encre'] . '}'
			. '</style>';
	}
);

/*
 * Affiches QR : polices de la charte, et ses couleurs (bandeau brun, accents orange, étoiles
 * jaunes, papier blanc cassé) tant qu'aucune couleur n'est choisie dans Collecte d'avis ▸
 * Réglages ▸ Affiches. Logo : celui du site (réglage de l'affiche vide).
 */
add_filter(
	'tfmsl_poster_style',
	static function ( $affiche ) {
		$affiche               = is_array( $affiche ) ? $affiche : array();
		$f                     = bigm_marque_familles();
		$c                     = bigm_marque_couleurs();
		$affiche['font_css']   = bigm_marque_polices_css() . ( (string) ( $affiche['font_css'] ?? '' ) );
		$affiche['font_title'] = $f['titre'];
		$affiche['font_text']  = $f['texte'];
		$choix                 = class_exists( 'TFMSL_Review_Booster' ) && method_exists( 'TFMSL_Review_Booster', 'get_settings' ) ? (array) TFMSL_Review_Booster::get_settings() : array();
		$marque                = array(
			'band'   => array( 'poster_band', $c['encre'] ),
			'accent' => array( 'poster_accent', $c['bouton'] ),
			'ink'    => array( 'poster_ink', $c['encre'] ),
			'paper'  => array( 'poster_paper', $c['fond'] ),
		);
		$couleurs              = (array) ( $affiche['colors'] ?? array() );
		foreach ( $marque as $cle => list( $reglage, $valeur ) ) {
			if ( empty( $choix[ $reglage ] ) ) {
				$couleurs[ $cle ] = $valeur;
			}
		}
		$couleurs['star']  = $c['jaune'];
		$affiche['colors'] = $couleurs;
		return $affiche;
	}
);

/*
 * Réseaux d'un restaurant : ceux de Big M quand sa fiche n'en a aucun.
 */
add_filter(
	'tfmsl_store_socials',
	static function ( $reseaux, $post_id = 0 ) {
		if ( ! empty( $reseaux ) || ! function_exists( 'bigm_reglage' ) ) {
			return $reseaux;
		}
		$noms   = array(
			'instagram' => 'Instagram',
			'tiktok'    => 'TikTok',
			'snapchat'  => 'Snapchat',
			'facebook'  => 'Facebook',
			'x'         => 'X',
			'youtube'   => 'YouTube',
		);
		$marque = array();
		foreach ( $noms as $reseau => $nom ) {
			$url = (string) bigm_reglage( $reseau . '_url' );
			if ( '' !== $url ) {
				$marque[ $reseau ] = array(
					'url'   => $url,
					'label' => $nom,
				);
			}
		}
		return (array) apply_filters( 'bigm_reseaux_restaurant', $marque, (int) $post_id );
	},
	10,
	2
);

/*
 * Pastille « Nouveau » des fiches et de la liste des restaurants : jaune de la charte, texte brun.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( wp_style_is( 'tfmsl-public', 'enqueued' ) || wp_style_is( 'tfmsl-public', 'registered' ) ) {
			$c = bigm_marque_couleurs();
			wp_add_inline_style( 'tfmsl-public', ':root{--tfmsl-new-bg:' . $c['jaune'] . ';--tfmsl-new-fg:' . $c['encre'] . '}' );
		}
	},
	31
);
