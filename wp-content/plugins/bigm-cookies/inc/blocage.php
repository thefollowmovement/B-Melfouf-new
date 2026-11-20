<?php
/**
 * Rien avant l'accord : scripts et contenus soumis au consentement mis en attente dans la page.
 *
 * - Mode de consentement de Google (v2) : tout « refusé » par défaut, en tête de page, avant
 *   tout script Google ; « accordé » seulement après le choix du visiteur (assets/cookies.js).
 *   Cookies _ga limités à 13 mois, sans prolongation à chaque visite (cookie_expires, cookie_update).
 * - Scripts : Google Analytics posé par Site Kit (google_gtagjs, ses scripts en ligne et les
 *   suivis d'événements googlesitekit-events-provider-*) deviennent type="text/plain" avec
 *   data-bigm-cookies="mesure" ; le navigateur ne les télécharge ni ne les exécute. Le JS les
 *   relance dans l'ordre après l'accord. Filtre bigm_cookies_scripts (identifiant => finalité)
 *   pour d'autres services. Un script ajouté à la main (bloc HTML d'Elementor) peut faire de même :
 *   <script type="text/plain" data-bigm-cookies="mesure">…</script>.
 * - Contenus intégrés (YouTube, Vimeo, Instagram, TikTok, X, Facebook, Spotify…) : remplacés
 *   par un encart « Afficher le contenu », avec le code d'origine en attente (data-contenu).
 *   Afficher un contenu vaut accord pour la finalité « contenus ».
 *
 * Tout se décide dans le navigateur : les pages restent les mêmes pour tous (cache).
 *
 * @package BigM\Cookies
 */

defined( 'ABSPATH' ) || exit;

/**
 * Finalité d'un script mis en file d'attente, ou '' s'il n'attend pas de consentement.
 *
 * @param string $handle Identifiant du script.
 */
function bigm_cookies_finalite_script( string $handle ): string {
	$scripts = (array) apply_filters(
		'bigm_cookies_scripts',
		array(
			'google_gtagjs' => 'mesure',
		)
	);
	if ( isset( $scripts[ $handle ] ) ) {
		$finalite = (string) $scripts[ $handle ];
	} elseif ( str_starts_with( $handle, 'googlesitekit-events-provider' ) ) {
		$finalite = 'mesure';
	} else {
		return '';
	}
	// Finalité retirée du bandeau (Réglages → Cookies) : le script reste bloqué, sous ce nom.
	return $finalite;
}

/**
 * Met en attente toutes les balises <script> d'un morceau de HTML (y compris les scripts en
 * ligne « before » et « after » qu'imprime WordPress avec le script principal).
 *
 * @param string $html     Balises.
 * @param string $finalite Finalité.
 */
function bigm_cookies_mettre_en_attente( string $html, string $finalite ): string {
	return (string) preg_replace_callback(
		'/<script\b([^>]*)>/i',
		static function ( array $m ) use ( $finalite ): string {
			$attributs = (string) preg_replace( '/\s+type=(["\'])[^"\']*\1/i', '', $m[1] );
			if ( preg_match( '/\bdata-bigm-cookies=/i', $attributs ) ) {
				return $m[0];
			}
			return '<script type="text/plain" data-bigm-cookies="' . esc_attr( $finalite ) . '"' . $attributs . '>';
		},
		$html
	);
}

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle ): string {
		$finalite = bigm_cookies_finalite_script( $handle );
		return '' === $finalite || is_admin() ? $tag : bigm_cookies_mettre_en_attente( $tag, $finalite );
	},
	20,
	2
);

/*
 * Scripts en ligne isolés de ces mêmes services (imprimés hors de leur script principal).
 */
add_filter(
	'wp_inline_script_attributes',
	static function ( array $attributs ): array {
		$id = (string) ( $attributs['id'] ?? '' );
		if ( is_admin() || ! preg_match( '/^(.+)-js-(before|after|extra)$/', $id, $m ) ) {
			return $attributs;
		}
		$finalite = bigm_cookies_finalite_script( $m[1] );
		if ( '' !== $finalite ) {
			$attributs['type']               = 'text/plain';
			$attributs['data-bigm-cookies'] = $finalite;
		}
		return $attributs;
	}
);

/*
 * Mode de consentement de Google : refusé par défaut, avant tout script Google.
 */
add_action(
	'wp_head',
	static function () {
		if ( is_admin() ) {
			return;
		}
		$defaut = array(
			'ad_storage'              => 'denied',
			'ad_user_data'            => 'denied',
			'ad_personalization'      => 'denied',
			'analytics_storage'       => 'denied',
			'functionality_storage'   => 'granted',
			'personalization_storage' => 'denied',
			'security_storage'        => 'granted',
			'wait_for_update'         => 500,
		);
		// Cookies de mesure d'audience : 13 mois au plus, sans prolongation à chaque visite (CNIL).
		$duree = array(
			'cookie_expires' => 395 * DAY_IN_SECONDS,
			'cookie_update'  => false,
		);
		wp_print_inline_script_tag(
			'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("consent","default",' . wp_json_encode( $defaut ) . ');gtag("set","ads_data_redaction",true);gtag("set",' . wp_json_encode( $duree ) . ');',
			array( 'id' => 'bigm-cookies-consentement' )
		);
	},
	2
);

/*
 * Les mêmes durées dans la configuration de Google Analytics posée par Site Kit.
 */
add_filter(
	'googlesitekit_gtag_opt',
	static function ( $opt ) {
		return array_merge(
			(array) $opt,
			array(
				'cookie_expires' => 395 * DAY_IN_SECONDS,
				'cookie_update'  => false,
			)
		);
	}
);

/**
 * Fournisseur d'un contenu intégré soumis au consentement, d'après son adresse, ou ''.
 *
 * @param string $texte Adresse ou code HTML du contenu.
 */
function bigm_cookies_fournisseur( string $texte ): string {
	$fournisseurs = (array) apply_filters(
		'bigm_cookies_fournisseurs',
		array(
			'youtube.com'     => 'YouTube',
			'youtu.be'        => 'YouTube',
			'youtube-nocookie.com' => 'YouTube',
			'vimeo.com'       => 'Vimeo',
			'dailymotion.com' => 'Dailymotion',
			'instagram.com'   => 'Instagram',
			'tiktok.com'      => 'TikTok',
			'twitter.com'     => 'X (Twitter)',
			'x.com'           => 'X (Twitter)',
			'facebook.com'    => 'Facebook',
			'spotify.com'     => 'Spotify',
			'soundcloud.com'  => 'SoundCloud',
			'google.com/maps' => 'Google Maps',
		)
	);
	foreach ( $fournisseurs as $domaine => $nom ) {
		if ( preg_match( '#(//|\.)' . preg_quote( $domaine, '#' ) . '#i', $texte ) ) {
			return (string) $nom;
		}
	}
	return '';
}

/**
 * Encart qui remplace un contenu intégré jusqu'à l'accord.
 *
 * @param string $html        Code d'origine.
 * @param string $fournisseur Nom du site.
 */
function bigm_cookies_encart( string $html, string $fournisseur ): string {
	return '<div class="bigm-cookies-contenu" data-bigm-cookies-contenu="contenus" data-contenu="' . esc_attr( base64_encode( $html ) ) . '">' // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- code d'origine gardé tel quel pour le navigateur.
		. '<p>Ce contenu est hébergé par ' . esc_html( $fournisseur ) . ', qui peut déposer des cookies.</p>'
		. '<button type="button" class="btn" data-bigm-cookies-autoriser="contenus">Afficher le contenu</button>'
		. '<p class="bigm-cookies-contenu__note">Cela autorise les contenus d’autres sites sur tout le site. Vous pourrez changer d’avis avec « Paramétrer les cookies », en bas de page.</p>'
		. '</div>';
}

add_filter(
	'embed_oembed_html',
	static function ( $html, $url ) {
		if ( is_admin() || ! is_string( $html ) ) {
			return $html;
		}
		$fournisseur = bigm_cookies_fournisseur( (string) $url . ' ' . $html );
		return '' !== $fournisseur ? bigm_cookies_encart( $html, $fournisseur ) : $html;
	},
	20,
	2
);

/*
 * Cadres (iframe) collés directement dans un contenu.
 */
add_filter(
	'the_content',
	static function ( $contenu ) {
		if ( is_admin() || ! is_string( $contenu ) || false === stripos( $contenu, '<iframe' ) ) {
			return $contenu;
		}
		return (string) preg_replace_callback(
			'#<iframe\b[^>]*\bsrc=(["\'])([^"\']+)\1[^>]*>.*?</iframe>#is',
			static function ( array $m ): string {
				$fournisseur = bigm_cookies_fournisseur( $m[2] );
				return '' !== $fournisseur ? bigm_cookies_encart( $m[0], $fournisseur ) : $m[0];
			},
			$contenu
		);
	},
	99
);
