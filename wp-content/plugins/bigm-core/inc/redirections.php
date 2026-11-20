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
			'/boutique/ /restaurants/',
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
		$cible = bigm_redirection_cible( $demande );
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
		$journal            = (array) get_option( 'bigm_404', array() );
		$ligne              = $journal[ $chemin ] ?? array( 0, '' );
		$journal[ $chemin ] = array( (int) $ligne[0] + 1, current_time( 'mysql' ) );
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
		echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>Adresse</th><th>Visites</th><th>Dernière</th></tr></thead><tbody>';
		foreach ( array_slice( $journal, 0, 50, true ) as $chemin => $l ) {
			echo '<tr><td><code>' . esc_html( (string) $chemin ) . '</code></td><td>' . (int) $l[0] . '</td><td>' . esc_html( mysql2date( 'j M Y H:i', (string) $l[1] ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bigm_vider_404' ), 'bigm_vider_404' ) ) . '">Vider le journal</a></p>';
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
