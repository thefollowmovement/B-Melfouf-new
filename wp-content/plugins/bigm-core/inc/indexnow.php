<?php
/**
 * IndexNow (https://www.indexnow.org) : Bing, Yandex, Seznam, Naver… sont prévenus dès qu'une page
 * publique est publiée, modifiée ou retirée, sans attendre leur prochain passage. Google ne s'en
 * sert pas (plans du site). Bing alimente aussi Copilot et la recherche de ChatGPT.
 *
 * - Clé : 32 caractères hexadécimaux, option bigm_indexnow_cle (créée une fois), servie en texte
 *   brut à https://site/{clé}.txt (preuve que le site est à nous).
 * - Adresses mises en file (option bigm_indexnow_file, non autoload) quand un contenu public est
 *   publié, modifié, dépublié, mis à la corbeille ou supprimé ; envoyées une minute plus tard par
 *   lots (tâche bigm_indexnow_envoi) : POST https://api.indexnow.org/indexnow. Refus temporaire
 *   (429, 5xx, réseau) : nouvel essai 10 minutes plus tard.
 * - Seulement sur le site en production (wp_get_environment_type(), « Visibilité pour les moteurs
 *   de recherche » permise), case « IndexNow » de Réglages Big M → Référencement (cochée par
 *   défaut) ; rien si une extension de référencement est active (bigm_seo_actif()), qui le fait.
 * - Journal des 20 derniers envois : option bigm_indexnow_journal.
 * - GET /wp-json/bigm/v1/indexnow : état ; POST : envoie tout de suite des adresses (urls) ou
 *   toutes les pages publiques (tout = true). Administrateurs.
 *
 * @package bigm-core
 */

defined( 'ABSPATH' ) || exit;

const BIGM_INDEXNOW_API = 'https://api.indexnow.org/indexnow';
const BIGM_INDEXNOW_MAX = 10000; // Adresses par envoi (limite d'IndexNow).

/**
 * IndexNow actif ? Production, site visible des moteurs, case cochée, pas d'extension SEO.
 * Filtre « bigm_indexnow_actif ».
 */
function bigm_indexnow_actif(): bool {
	$actif = 'production' === wp_get_environment_type()
		&& '0' !== (string) get_option( 'blog_public', '1' )
		&& '1' === (string) bigm_reglage( 'indexnow_actif' )
		&& ( ! function_exists( 'bigm_seo_actif' ) || bigm_seo_actif() );
	return (bool) apply_filters( 'bigm_indexnow_actif', $actif );
}

/** Clé du site (créée une fois). */
function bigm_indexnow_cle(): string {
	$cle = (string) get_option( 'bigm_indexnow_cle', '' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $cle ) ) {
		$cle = bin2hex( random_bytes( 16 ) );
		update_option( 'bigm_indexnow_cle', $cle, true );
	}
	return $cle;
}

/** Adresse du fichier de la clé. */
function bigm_indexnow_url_cle(): string {
	return home_url( '/' . bigm_indexnow_cle() . '.txt' );
}

/**
 * Types de contenu signalés : publics, sauf les médias. Filtre « bigm_indexnow_types ».
 *
 * @return string[]
 */
function bigm_indexnow_types(): array {
	$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );
	return (array) apply_filters( 'bigm_indexnow_types', $types );
}

/** Le contenu doit-il être signalé (type public, sans mot de passe ni « ne pas indexer ») ? */
function bigm_indexnow_signaler( WP_Post $post ): bool {
	return in_array( $post->post_type, bigm_indexnow_types(), true )
		&& '' === (string) $post->post_password
		&& '1' !== (string) get_post_meta( $post->ID, '_bigm_seo_noindex', true );
}

/**
 * Ajoute des adresses à la file (adresses du site seulement) et planifie l'envoi.
 *
 * @param string[] $urls Adresses.
 */
function bigm_indexnow_ajouter( array $urls ): int {
	if ( ! bigm_indexnow_actif() ) {
		return 0;
	}
	$hote = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$file = get_option( 'bigm_indexnow_file', array() );
	$file = is_array( $file ) ? $file : array();
	$n    = 0;
	foreach ( $urls as $url ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url || (string) wp_parse_url( $url, PHP_URL_HOST ) !== $hote || isset( $file[ $url ] ) ) {
			continue;
		}
		$file[ $url ] = time();
		++$n;
	}
	if ( $n ) {
		update_option( 'bigm_indexnow_file', $file, false );
		if ( ! wp_next_scheduled( 'bigm_indexnow_envoi' ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bigm_indexnow_envoi' );
		}
	}
	return $n;
}

/*
 * Contenu enregistré : son adresse publique d'avant (dépublié, corbeille, adresse changée) et
 * d'après (publié, modifié).
 */
add_action(
	'wp_after_insert_post',
	static function ( $post_id, $post, $update, $avant ) {
		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) || ! bigm_indexnow_actif() ) {
			return;
		}
		$urls = array();
		if ( $avant instanceof WP_Post && 'publish' === $avant->post_status && bigm_indexnow_signaler( $avant ) ) {
			$urls[] = get_permalink( $avant );
		}
		if ( 'publish' === $post->post_status && bigm_indexnow_signaler( $post ) ) {
			$urls[] = get_permalink( $post );
		}
		if ( $urls ) {
			bigm_indexnow_ajouter( array_unique( array_filter( $urls ) ) );
		}
	},
	20,
	4
);

/* Contenu publié supprimé sans passer par la corbeille. */
add_action(
	'before_delete_post',
	static function ( $post_id, $post = null ) {
		$post = $post instanceof WP_Post ? $post : get_post( (int) $post_id );
		if ( $post && 'publish' === $post->post_status && bigm_indexnow_signaler( $post ) ) {
			bigm_indexnow_ajouter( array( get_permalink( $post ) ) );
		}
	},
	10,
	2
);

/**
 * Envoie la file (ou les adresses données) à IndexNow. Retour : code HTTP (0 = erreur réseau),
 * nombre d'adresses, message.
 *
 * @param string[]|null $urls Adresses à envoyer tout de suite (null = la file).
 * @return array{code: int, n: int, message: string}
 */
function bigm_indexnow_envoyer( ?array $urls = null ): array {
	if ( ! bigm_indexnow_actif() ) {
		return array( 'code' => 0, 'n' => 0, 'message' => 'IndexNow inactif.' );
	}
	$depuis_file = null === $urls;
	if ( $depuis_file ) {
		$file = get_option( 'bigm_indexnow_file', array() );
		$urls = is_array( $file ) ? array_keys( $file ) : array();
	}
	$urls = array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
	if ( ! $urls ) {
		return array( 'code' => 0, 'n' => 0, 'message' => 'Rien à envoyer.' );
	}
	$lot  = array_slice( $urls, 0, BIGM_INDEXNOW_MAX );
	$rep  = wp_remote_post(
		BIGM_INDEXNOW_API,
		array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => wp_json_encode(
				array(
					'host'        => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
					'key'         => bigm_indexnow_cle(),
					'keyLocation' => bigm_indexnow_url_cle(),
					'urlList'     => $lot,
				),
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			),
		)
	);
	$code     = is_wp_error( $rep ) ? 0 : (int) wp_remote_retrieve_response_code( $rep );
	$messages = array(
		200 => 'Reçues.',
		202 => 'Reçues (clé en cours de vérification).',
		400 => 'Demande refusée (format).',
		403 => 'Clé refusée : le fichier de la clé n’est pas lisible.',
		422 => 'Adresses refusées (autre site ou clé différente).',
		429 => 'Trop d’envois : nouvel essai plus tard.',
	);
	$message    = is_wp_error( $rep ) ? $rep->get_error_message() : ( $messages[ $code ] ?? 'Réponse ' . $code . '.' );
	$temporaire = 0 === $code || 429 === $code || $code >= 500;

	if ( $depuis_file ) {
		$file = get_option( 'bigm_indexnow_file', array() );
		$file = is_array( $file ) ? $file : array();
		if ( ! $temporaire ) {
			$file = array_diff_key( $file, array_flip( $lot ) );
		}
		update_option( 'bigm_indexnow_file', $file, false );
		if ( $file && ! wp_next_scheduled( 'bigm_indexnow_envoi' ) ) {
			wp_schedule_single_event( time() + ( $temporaire ? 10 * MINUTE_IN_SECONDS : MINUTE_IN_SECONDS ), 'bigm_indexnow_envoi' );
		}
	}

	$journal = get_option( 'bigm_indexnow_journal', array() );
	$journal = is_array( $journal ) ? $journal : array();
	array_unshift(
		$journal,
		array(
			'date'    => time(),
			'code'    => $code,
			'n'       => count( $lot ),
			'message' => $message,
			'urls'    => array_slice( $lot, 0, 5 ),
		)
	);
	update_option( 'bigm_indexnow_journal', array_slice( $journal, 0, 20 ), false );
	return array( 'code' => $code, 'n' => count( $lot ), 'message' => $message );
}
add_action(
	'bigm_indexnow_envoi',
	static function () {
		bigm_indexnow_envoyer(); // do_action() sans argument passe '' : pas de lien direct.
	}
);

/** Toutes les adresses publiques signalables (pour un premier envoi). @return string[] */
function bigm_indexnow_toutes(): array {
	$urls = array( home_url( '/' ) );
	foreach ( bigm_indexnow_types() as $type ) {
		$archive = get_post_type_archive_link( $type );
		if ( $archive && 'post' !== $type ) {
			$urls[] = $archive;
		}
		foreach ( get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => -1, 'has_password' => false ) ) as $p ) {
			if ( bigm_indexnow_signaler( $p ) ) {
				$urls[] = get_permalink( $p );
			}
		}
	}
	foreach ( array( 'bigm_categorie', 'category' ) as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			foreach ( get_terms( array( 'taxonomy' => $tax, 'hide_empty' => true ) ) as $t ) {
				$lien = get_term_link( $t );
				if ( ! is_wp_error( $lien ) ) {
					$urls[] = $lien;
				}
			}
		}
	}
	return array_values( array_unique( array_filter( $urls ) ) );
}

/**
 * Fichier de la clé : /{clé}.txt répond la clé en texte brut (avant le reste de WordPress).
 *
 * @param string $chemin Chemin demandé (tests).
 */
function bigm_indexnow_servir_cle( string $chemin ): bool {
	$cle = (string) get_option( 'bigm_indexnow_cle', '' );
	if ( ! preg_match( '/^[a-f0-9]{32}$/', $cle ) ) {
		return false;
	}
	$base = rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	return $chemin === $base . '/' . $cle . '.txt';
}
add_action(
	'init',
	static function () {
		$chemin = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! str_ends_with( $chemin, '.txt' ) || ! bigm_indexnow_servir_cle( $chemin ) ) {
			return;
		}
		status_header( 200 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Robots-Tag: noindex' );
		echo esc_html( bigm_indexnow_cle() );
		exit;
	},
	0
);

/* Première activation en production : la clé existe avant le premier envoi. */
add_action(
	'admin_init',
	static function () {
		if ( bigm_indexnow_actif() ) {
			bigm_indexnow_cle();
		}
	}
);

/** État pour Réglages Big M et l'API. */
function bigm_indexnow_etat(): array {
	$journal = get_option( 'bigm_indexnow_journal', array() );
	$file    = get_option( 'bigm_indexnow_file', array() );
	return array(
		'actif'   => bigm_indexnow_actif(),
		'cle_url' => preg_match( '/^[a-f0-9]{32}$/', (string) get_option( 'bigm_indexnow_cle', '' ) ) ? bigm_indexnow_url_cle() : '',
		'file'    => is_array( $file ) ? count( $file ) : 0,
		'journal' => array_map(
			static fn( $e ) => array_merge( $e, array( 'date' => wp_date( 'c', (int) $e['date'] ) ) ),
			is_array( $journal ) ? $journal : array()
		),
	);
}

/** Phrase d'état sous la case de Réglages Big M. */
function bigm_indexnow_resume(): string {
	$journal = get_option( 'bigm_indexnow_journal', array() );
	$dernier = is_array( $journal ) && $journal ? $journal[0] : null;
	$texte   = 'Prévient Bing (et Copilot, ChatGPT), Yandex, Seznam et Naver dès qu’une page, un produit, un restaurant ou un article est publié, modifié ou retiré. Google ne s’en sert pas : il lit les plans du site. Seulement sur le site en production.';
	if ( $dernier ) {
		$texte .= ' Dernier envoi : ' . wp_date( 'j M Y, H:i', (int) $dernier['date'] ) . ', ' . (int) $dernier['n'] . ' adresse(s), ' . $dernier['message'];
	}
	return $texte;
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/indexnow',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => static fn() => rest_ensure_response( bigm_indexnow_etat() ),
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => static function ( WP_REST_Request $r ) {
						if ( ! bigm_indexnow_actif() ) {
							return new WP_Error( 'bigm_indexnow_inactif', 'IndexNow est inactif (production seulement, case de Réglages Big M → Référencement).', array( 'status' => 409 ) );
						}
						$urls = $r->get_param( 'tout' ) ? bigm_indexnow_toutes() : array_map( 'strval', (array) $r->get_param( 'urls' ) );
						$hote = (string) wp_parse_url( home_url(), PHP_URL_HOST );
						$urls = array_values( array_filter( $urls, static fn( $u ) => (string) wp_parse_url( $u, PHP_URL_HOST ) === $hote ) );
						if ( ! $urls ) {
							return new WP_Error( 'bigm_indexnow_vide', 'Aucune adresse de ce site.', array( 'status' => 400 ) );
						}
						return rest_ensure_response( bigm_indexnow_envoyer( $urls ) + array( 'cle_url' => bigm_indexnow_url_cle() ) );
					},
					'permission_callback' => static fn() => current_user_can( 'manage_options' ),
				),
			)
		);
	}
);
