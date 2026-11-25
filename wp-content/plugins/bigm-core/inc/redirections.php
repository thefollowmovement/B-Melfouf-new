<?php
/**
 * Redirections 301 depuis les adresses de l'ancien site, et journal des adresses introuvables.
 *
 * La liste se règle dans Réglages Big M → Redirections, une règle par ligne :
 *
 *   /nos-restaurants/ /restaurants/      adresse exacte
 *   /boutiques/* /restaurants/*          début d'adresse ; « * » reprend la suite
 *   /app/ [commander]                    lien Commander des Réglages Big M
 *   # commentaire
 *
 * Une règle ne s'applique que si l'adresse demandée n'existe pas (page 404) : une page créée
 * plus tard à la même adresse reste visible. La première règle qui correspond l'emporte.
 *
 * Fiches restaurants de l'ancien site (/boutique/{nom}-{ville}/, /boutiques/{…}/) : retrouvées
 * avant les règles par bigm_redirection_restaurant() (fiche du restaurant, sinon page de sa
 * ville, sinon /restaurants/), quelles que soient les règles enregistrées.
 *
 * Articles (0.16.3) : adresse d'un article à la corbeille ou d'une variante d'un article publié
 * (« …-2 », adresse de l'ancien site sous /presse/ ou /news/) : bigm_redirection_article().
 * Adresses terminées par /null ou /undefined (lien fabriqué à partir d'une valeur vide) : la page
 * d'avant.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Règles par défaut : adresses de l'ancien site bigm-burger.fr (plan du site et brief, § 10).
 */
function bigm_redirections_par_defaut(): string {
	return implode(
		"\n",
		array(
			'# Ancien site → nouveau site. Appliquées seulement si l’adresse n’existe pas.',
			'/nos-restaurants/ /restaurants/',
			'# /boutique/{nom}-{ville}/ et /boutiques/…/ : fiche du restaurant retrouvée automatiquement.',
			'/boutique/* /restaurants/',
			'/boutiques/* /restaurants/*',
			'/storelocator-test/ /restaurants/',
			'/template-boutique/ /restaurants/',
			'/contact/ /nous-rejoindre/',
			'/recrutement/ /nous-rejoindre/',
			'/news/ /actualites/',
			'/presse/ /actualites/',
			'/concours/ /jeu-concours/',
			'/app/ [commander]',
			'/offre-deliveroo/ /promos/',
			'/le-big/ /carte/',
			'/menu-test/ /carte/',
			'/menu-test-2/ /carte/',
			'/produit/* /carte/',
			'/categorie-de-produit/burger/ /carte/burgers/',
			'/categorie-de-produit/* /carte/',
			'/category/presse/* /actualites/presse/',
			'/category/* /actualites/',
			'/tag/* /actualites/',
			'/type/* /actualites/',
			'/privacy-policy/ /mentions-legales/',
			'/accueil-2/ /',
		)
	);
}

/**
 * Règles lues dans les réglages : liste de [ source, cible, préfixe ].
 *
 * @param string|null $texte Texte des règles (par défaut : Réglages Big M).
 * @return array<int, array{0: string, 1: string, 2: bool}>
 */
function bigm_redirections( ?string $texte = null ): array {
	$texte  = $texte ?? (string) bigm_reglage( 'redirections' );
	$regles = array();
	foreach ( preg_split( '/\R/', $texte ) as $ligne ) {
		$ligne = trim( $ligne );
		if ( '' === $ligne || str_starts_with( $ligne, '#' ) ) {
			continue;
		}
		$parts = preg_split( '/\s+/', $ligne );
		if ( count( $parts ) < 2 || ! str_starts_with( $parts[0], '/' ) ) {
			continue;
		}
		$prefixe  = str_ends_with( $parts[0], '*' );
		$source   = bigm_redirection_chemin( rtrim( $parts[0], '*' ) );
		$regles[] = array( $source, $parts[1], $prefixe );
	}
	return $regles;
}

/**
 * Chemin comparable : minuscules, décodé, sans barre finale (sauf « / »).
 *
 * @param string $chemin Chemin.
 */
function bigm_redirection_chemin( string $chemin ): string {
	$chemin = strtolower( rawurldecode( (string) wp_parse_url( $chemin, PHP_URL_PATH ) ) );
	$chemin = '/' . trim( $chemin, '/' );
	return '/' === $chemin ? '/' : $chemin;
}

/**
 * Adresse de destination d'un chemin demandé, ou chaîne vide.
 *
 * @param string     $demande Chemin demandé (/news/).
 * @param array|null $regles  Règles de bigm_redirections() (par défaut : celles des réglages).
 */
function bigm_redirection_cible( string $demande, ?array $regles = null ): string {
	$demande = bigm_redirection_chemin( $demande );
	foreach ( $regles ?? bigm_redirections() as $r ) {
		list( $source, $cible, $prefixe ) = $r;
		$suite = '';
		if ( $prefixe ) {
			$base = '/' === $source ? '/' : $source . '/';
			if ( $demande !== $source && ! str_starts_with( $demande . '/', $base ) ) {
				continue;
			}
			$suite = ltrim( (string) substr( $demande, strlen( $source ) ), '/' );
		} elseif ( $demande !== $source ) {
			continue;
		}
		if ( '[commander]' === $cible ) {
			$url = function_exists( 'bigm_url_commander' ) ? bigm_url_commander() : (string) bigm_reglage( 'commander_url' );
		} else {
			if ( str_contains( $cible, '*' ) ) {
				$cible = str_replace( '*', '' !== $suite ? trailingslashit( $suite ) : '', $cible );
			}
			$url = str_starts_with( $cible, '/' ) ? home_url( $cible ) : $cible;
		}
		if ( '' === $url ) {
			continue;
		}
		// Pas de boucle : une cible du site identique à l'adresse demandée est ignorée.
		if ( ! str_starts_with( $url, home_url() ) || bigm_redirection_chemin( $url ) !== $demande ) {
			return $url;
		}
	}
	return '';
}

/**
 * Adresse d'un nom à la façon de l'ancien site (extension de boutiques) : minuscules, tout ce
 * qui n'est pas une lettre ou un chiffre sans accent devient un tiret (« Pont-De-Chéruy » →
 * « pont-de-ch-ruy », « Saint-Étienne » → « saint-tienne »).
 *
 * @param string $texte Nom ou ville.
 */
function bigm_redirection_ancien_slug( string $texte ): string {
	$texte = html_entity_decode( $texte, ENT_QUOTES, 'UTF-8' );
	return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( $texte ) ), '-' );
}

/**
 * Mots d'une adresse (« dakar-2-plateau-dakar » → dakar, 2, plateau), sans doublon.
 *
 * @param string $slug Adresse.
 * @return string[]
 */
function bigm_redirection_mots( string $slug ): array {
	return array_values( array_unique( array_filter( explode( '-', $slug ), 'strlen' ) ) );
}

/**
 * Restaurants publiés et leurs adresses possibles sur l'ancien site, pour une requête.
 *
 * @param bool $recharger Relire les restaurants (tests).
 * @return array<int, array{url: string, cles: string[], mots: string[][], ville: string}>
 */
function bigm_redirection_index_restaurants( bool $recharger = false ): array {
	static $index = null;
	if ( null !== $index && ! $recharger ) {
		return $index;
	}
	$index = array();
	if ( ! post_type_exists( 'tfmsl_store' ) ) {
		return $index;
	}
	foreach ( get_posts( array( 'post_type' => 'tfmsl_store', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'ASC' ) ) as $p ) {
		$titre   = (string) $p->post_title;
		$ville   = (string) get_post_meta( $p->ID, 'tfmsl_city', true );
		$ancien  = array( bigm_redirection_ancien_slug( $titre ), bigm_redirection_ancien_slug( $ville ) );
		$nouveau = array( sanitize_title( $titre ), sanitize_title( $ville ) );
		$index[] = array(
			'url'   => (string) get_permalink( $p ),
			// Adresse de l'ancien site ({nom}-{ville}), nom seul, adresse actuelle de la fiche.
			'cles'  => array_values( array_unique( array_filter( array( trim( implode( '-', $ancien ), '-' ), trim( implode( '-', $nouveau ), '-' ), $ancien[0], $nouveau[0], (string) $p->post_name ) ) ) ),
			'mots'  => array(
				bigm_redirection_mots( implode( '-', $ancien ) ),
				bigm_redirection_mots( implode( '-', $nouveau ) . '-' . $p->post_name ),
			),
			'ville' => $nouveau[1],
		);
	}
	return $index;
}

/**
 * Fiche d'un restaurant de l'ancien site (/boutique/soissons-soissons/) : adresse de sa fiche
 * (adresse exacte de l'ancien site, sinon le restaurant dont le nom et la ville ressemblent le
 * plus, sans ex aequo), sinon page de sa ville (StoreLocatore 2.16), sinon /restaurants/.
 * Filtre bigm_redirection_restaurant ( $url, $ancien ).
 *
 * @param string $ancien Dernière partie de l'ancienne adresse (« soissons-soissons »).
 */
function bigm_redirection_restaurant( string $ancien ): string {
	$ancien = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( rawurldecode( $ancien ) ) ), '-' );
	$url    = '';
	if ( '' !== $ancien ) {
		$index = bigm_redirection_index_restaurants();
		foreach ( $index as $r ) {
			if ( in_array( $ancien, $r['cles'], true ) ) {
				$url = $r['url'];
				break;
			}
		}
		if ( '' === $url ) {
			// Mots en commun / mots en tout (Jaccard) : nom changé depuis l'ancien site.
			$mots    = bigm_redirection_mots( $ancien );
			$scores  = array();
			foreach ( $index as $i => $r ) {
				$s = 0.0;
				foreach ( $r['mots'] as $m ) {
					$union = count( array_unique( array_merge( $mots, $m ) ) );
					$s     = max( $s, $union ? count( array_intersect( $mots, $m ) ) / $union : 0.0 );
				}
				$scores[ $i ] = $s;
			}
			arsort( $scores );
			$premiers = array_slice( $scores, 0, 2, true );
			$meilleur = (float) reset( $premiers );
			$second   = count( $premiers ) > 1 ? (float) array_values( $premiers )[1] : 0.0;
			if ( $meilleur >= 0.5 && $meilleur > $second ) {
				$url = $index[ (int) array_key_first( $premiers ) ]['url'];
			}
		}
		if ( '' === $url && class_exists( 'TFMSL_Areas' ) && is_callable( array( 'TFMSL_Areas', 'data' ) ) && is_callable( array( 'TFMSL_Areas', 'city_url' ) ) ) {
			// Page de la ville dont tous les mots sont dans l'ancienne adresse (la plus longue).
			$mots  = bigm_redirection_mots( $ancien );
			$ville = '';
			foreach ( array_keys( (array) ( TFMSL_Areas::data()['cities'] ?? array() ) ) as $slug ) {
				$slug = (string) $slug;
				if ( strlen( $slug ) > strlen( $ville ) && ! array_diff( bigm_redirection_mots( $slug ), $mots ) ) {
					$ville = $slug;
				}
			}
			$page = '' !== $ville ? (string) TFMSL_Areas::city_url( $ville ) : '';
			if ( '' !== $page && is_callable( array( 'TFMSL_Areas', 'all_urls' ) ) && in_array( $page, (array) TFMSL_Areas::all_urls(), true ) ) {
				$url = $page; // pages par ville activées
			}
		}
	}
	if ( '' === $url ) {
		$url = home_url( '/restaurants/' );
	}
	return (string) apply_filters( 'bigm_redirection_restaurant', $url, $ancien );
}

/**
 * Article d'une adresse introuvable (0.16.3), ou chaîne vide :
 * 1. article publié de même adresse (/presse/{article}/, /news/{article}/ de l'ancien site) ;
 * 2. article de même adresse à la corbeille (retiré volontairement) : page de sa catégorie
 *    (Presse, News), sinon /actualites/ ;
 * 3. variante d'un article publié (« …-2 », mots presque tous en commun : au moins 75 %,
 *    sans ex aequo) : cet article ;
 * 4. adresse d'article de presse de l'ancien site (web-, print-, tv-, radio-, rs-…) : /actualites/presse/.
 * Filtre bigm_redirection_article ( $url, $slug ).
 *
 * @param string $slug Dernière partie de l'adresse (« web-capital-cette-chaine… »).
 */
function bigm_redirection_article( string $slug ): string {
	global $wpdb;
	$slug = sanitize_title( rawurldecode( $slug ) );
	$url  = '';
	if ( '' === $slug || strlen( $slug ) < 8 ) {
		return (string) apply_filters( 'bigm_redirection_article', '', $slug );
	}
	$publie = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'name' => $slug, 'posts_per_page' => 1, 'fields' => 'ids' ) );
	if ( $publie ) {
		$url = (string) get_permalink( (int) $publie[0] );
	}
	if ( '' === $url ) {
		// Corbeille : WordPress renomme l'adresse en « {adresse}__trashed » et garde l'ancienne en
		// _wp_desired_post_slug.
		$jete = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wp_desired_post_slug'
				WHERE p.post_type = 'post' AND p.post_status = 'trash' AND ( m.meta_value = %s OR p.post_name = %s OR p.post_name LIKE %s ) ORDER BY p.ID DESC LIMIT 1",
				$slug,
				$slug . '__trashed',
				$wpdb->esc_like( $slug . '__trashed-' ) . '%'
			)
		);
		if ( $jete ) {
			$cats = wp_get_post_categories( $jete );
			$lien = $cats ? get_category_link( (int) $cats[0] ) : '';
			$url  = $lien && ! is_wp_error( $lien ) ? (string) $lien : home_url( '/actualites/' );
		}
	}
	if ( '' === $url ) {
		$base  = (string) preg_replace( '/-\d+$/', '', $slug );
		$mots  = bigm_redirection_mots( $base );
		$noms  = $wpdb->get_col( "SELECT post_name FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$meil  = array( '', 0.0 );
		$sec   = 0.0;
		foreach ( (array) $noms as $nom ) {
			$m     = bigm_redirection_mots( (string) preg_replace( '/-\d+$/', '', (string) $nom ) );
			$union = count( array_unique( array_merge( $mots, $m ) ) );
			$score = $union ? count( array_intersect( $mots, $m ) ) / $union : 0.0;
			if ( $score > $meil[1] ) {
				$sec  = $meil[1];
				$meil = array( (string) $nom, $score );
			} elseif ( $score > $sec ) {
				$sec = $score;
			}
		}
		if ( $meil[1] >= 0.75 && $meil[1] > $sec ) {
			$p   = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'name' => $meil[0], 'posts_per_page' => 1, 'fields' => 'ids' ) );
			$url = $p ? (string) get_permalink( (int) $p[0] ) : '';
		}
	}
	if ( '' === $url && preg_match( '/^(web|print|tv|radio|rs|presse|press)-/', $slug ) ) {
		$cat = get_category_by_slug( 'presse' );
		$url = $cat ? (string) get_category_link( $cat ) : home_url( '/actualites/' );
	}
	return (string) apply_filters( 'bigm_redirection_article', $url, $slug );
}

/**
 * Destination d'une adresse introuvable : fiche restaurant de l'ancien site, sinon règles de
 * Réglages Big M ; chaîne vide s'il n'y en a pas.
 *
 * @param string $demande Chemin demandé (/boutique/soissons-soissons/).
 */
function bigm_redirection_adresse( string $demande ): string {
	$chemin = bigm_redirection_chemin( $demande );
	$autre  = static fn( string $url ): bool => '' !== $url && bigm_redirection_chemin( $url ) !== $chemin;
	// « …/null », « …/undefined » : lien fabriqué à partir d'une valeur vide ; la page d'avant.
	if ( preg_match( '#^(.*)/(null|undefined)$#', $chemin, $m ) ) {
		return home_url( trailingslashit( '' === $m[1] ? '/' : $m[1] ) );
	}
	if ( preg_match( '#^/boutiques?/([^/]+)$#', $chemin, $m ) ) {
		$url = bigm_redirection_restaurant( $m[1] );
		if ( $autre( $url ) ) {
			return $url;
		}
	}
	// Article : /{article}/, ou /presse/{article}/, /news/{article}/ de l'ancien site.
	if ( preg_match( '#^(?:/(?:presse|news|actualites|actualites/presse|actualites/news))?/([a-z0-9][a-z0-9-]*)$#', $chemin, $m ) ) {
		$url = bigm_redirection_article( $m[1] );
		if ( $autre( $url ) ) {
			return $url;
		}
	}
	return bigm_redirection_cible( $demande );
}

/*
 * Redirection des adresses introuvables, avant les devinettes de WordPress (redirect_canonical).
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return;
		}
		$demande = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$home    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( '/' !== $home && str_starts_with( $demande, rtrim( $home, '/' ) ) ) {
			$demande = substr( $demande, strlen( rtrim( $home, '/' ) ) );
		}
		$cible = bigm_redirection_adresse( $demande );
		if ( '' !== $cible ) {
			wp_redirect( $cible, 301, 'Big M' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- cibles réglées par l'administrateur (lien Commander externe).
			exit;
		}
	},
	1
);

/*
 * Journal des adresses introuvables (les 100 plus récentes), pour compléter les redirections.
 * Ni fichiers (images, scripts), ni administration, ni robots qui sondent des failles.
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_404() ) {
			return;
		}
		$chemin = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( strlen( $chemin ) > 200 || preg_match( '#^/(wp-|xmlrpc|\.well-known)|\.(php|js|css|map|png|jpe?g|gif|webp|avif|svg|ico|txt|xml|json|env|bak|sql|zip|woff2?)$#i', $chemin ) ) {
			return;
		}
		$journal = (array) get_option( 'bigm_404', array() );
		$ligne   = $journal[ $chemin ] ?? array( 0, '' );
		// Provenance de la dernière visite (0.16.3) : page d'où vient le lien (adresse sans
		// paramètres) et navigateur ou robot, pour trouver l'origine d'un lien cassé.
		$ref                = (string) wp_get_referer();
		$ref                = $ref ? (string) strtok( $ref, '?#' ) : '';
		$agent              = substr( sanitize_text_field( wp_unslash( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ), 0, 160 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$journal[ $chemin ] = array( (int) $ligne[0] + 1, current_time( 'mysql' ), substr( esc_url_raw( $ref ), 0, 200 ), $agent );
		if ( count( $journal ) > 100 ) {
			uasort( $journal, static fn( array $a, array $b ): int => strcmp( $b[1], $a[1] ) );
			$journal = array_slice( $journal, 0, 100, true );
		}
		update_option( 'bigm_404', $journal, false );
	},
	99
);

/*
 * Réglages Big M : journal sous le formulaire, et bouton pour le vider.
 */
add_action(
	'bigm_reglages_apres',
	static function () {
		$journal = (array) get_option( 'bigm_404', array() );
		uasort( $journal, static fn( array $a, array $b ): int => $b[0] <=> $a[0] ?: strcmp( $b[1], $a[1] ) );
		echo '<h2 id="journal-404">Adresses introuvables</h2>';
		echo '<p class="description">Les dernières adresses demandées qui n’existent pas sur le site (les plus fréquentes en premier). Une adresse de l’ancien site qui revient souvent mérite une règle dans « Redirections ».</p>';
		if ( ! $journal ) {
			echo '<p>Aucune pour l’instant.</p>';
			return;
		}
		echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Adresse</th><th>Visites</th><th>Dernière</th><th>Venant de</th></tr></thead><tbody>';
		foreach ( array_slice( $journal, 0, 50, true ) as $chemin => $l ) {
			$depuis = (string) ( $l[2] ?? '' );
			echo '<tr><td><code>' . esc_html( (string) $chemin ) . '</code></td><td>' . (int) $l[0] . '</td><td>' . esc_html( mysql2date( 'j M Y H:i', (string) $l[1] ) ) . '</td><td title="' . esc_attr( (string) ( $l[3] ?? '' ) ) . '">' . ( $depuis ? '<code>' . esc_html( $depuis ) . '</code>' : '—' ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bigm_vider_404' ), 'bigm_vider_404' ) ) . '">Vider le journal</a></p>';
	}
);

/*
 * GET /wp-json/bigm/v1/redirections[?chemins[]=/boutique/soissons-soissons/] : journal des
 * adresses introuvables et destination des chemins donnés (sans rediriger), administrateurs.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/redirections',
			array(
				'methods'             => 'GET',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'callback'            => static function ( WP_REST_Request $r ) {
					$journal = (array) get_option( 'bigm_404', array() );
					uasort( $journal, static fn( array $a, array $b ): int => $b[0] <=> $a[0] ?: strcmp( $b[1], $a[1] ) );
					$essais = array();
					foreach ( array_slice( array_map( 'strval', (array) $r->get_param( 'chemins' ) ), 0, 100 ) as $chemin ) {
						$essais[ $chemin ] = bigm_redirection_adresse( $chemin );
					}
					return rest_ensure_response(
						array(
							'journal' => array_map(
								static fn( array $l ): array => array(
									'visites'  => (int) $l[0],
									'derniere' => (string) $l[1],
									'depuis'   => (string) ( $l[2] ?? '' ),
									'agent'    => (string) ( $l[3] ?? '' ),
								),
								$journal
							),
							'essais'  => $essais,
						)
					);
				},
			)
		);
	}
);

add_action(
	'admin_post_bigm_vider_404',
	static function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bigm_vider_404' ) ) {
			wp_die( 'Action non autorisée.' );
		}
		delete_option( 'bigm_404' );
		wp_safe_redirect( admin_url( 'admin.php?page=bigm-reglages#journal-404' ) );
		exit;
	}
);
