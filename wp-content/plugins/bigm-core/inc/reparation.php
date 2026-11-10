<?php
/**
 * Réparation des données sérialisées abîmées par un changement d'adresse.
 *
 * WordPress range les tableaux (slides d'un slider, réglages…) sous une forme sérialisée qui
 * note la longueur de chaque texte : s:47:"https://preprod.bigm-burger.fr/carte/boissons/".
 * Un rechercher-remplacer qui ne recalcule pas ces longueurs (lors de la mise en ligne du
 * 9 novembre 2026) rend la valeur illisible : get_post_meta() la renvoie alors comme un simple
 * texte, et les sliders paraissent vides alors que leurs slides sont toujours dans la base.
 *
 * bigm_reparer_serialisations() recalcule ces longueurs, seulement pour les valeurs déjà
 * illisibles qui contiennent l'adresse du site (méta des contenus et des catégories, options).
 * Une valeur lisible n'est jamais modifiée ; une valeur que le calcul ne rend pas lisible non plus.
 * Elle se lance une fois au premier passage d'un administrateur (compte rendu en haut de
 * l'admin), et à la demande : POST /wp-json/bigm/v1/maintenance/serialisations (essai=1 pour
 * voir sans rien écrire).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valeur sérialisée réparée, ou null si elle est lisible, n'est pas sérialisée ou reste illisible.
 *
 * @param string $valeur Valeur brute de la base.
 */
function bigm_serialise_repare( string $valeur ): ?string {
	if ( 'b:0;' === $valeur || ! is_serialized( $valeur ) || false !== @unserialize( $valeur, array( 'allowed_classes' => false ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		return null;
	}
	// Chaque texte s:N:"…"; suivi de la fin du tableau ou du jeton suivant : N recalculé en octets.
	$repare = preg_replace_callback(
		'/s:(\d+):"(.*?)";(?=\}|[sibadNO][:;]|$)/s',
		static fn( array $m ): string => 's:' . strlen( $m[2] ) . ':"' . $m[2] . '";',
		$valeur
	);
	if ( null === $repare || $repare === $valeur || false === @unserialize( $repare, array( 'allowed_classes' => false ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		return null;
	}
	return $repare;
}

/**
 * Cherche et répare les valeurs sérialisées illisibles qui contiennent l'adresse du site.
 *
 * @param bool $ecrire Écrire les réparations (sinon : essai, rien ne change).
 * @return array{reparees: array<int, string>, illisibles: array<int, string>, date: string, essai: bool}
 */
function bigm_reparer_serialisations( bool $ecrire = true ): array {
	global $wpdb;
	$hote   = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$hote   = '' !== $hote ? preg_replace( '/^(www|preprod|staging)\./', '', $hote ) : 'bigm-burger.fr';
	$like   = '%' . $wpdb->esc_like( $hote ) . '%';
	$tables = array(
		array( $wpdb->postmeta, 'meta_id', 'meta_key', 'meta_value', 'post_id' ),
		array( $wpdb->termmeta, 'meta_id', 'meta_key', 'meta_value', 'term_id' ),
		array( $wpdb->options, 'option_id', 'option_name', 'option_value', 'option_name' ),
	);
	$cr = array(
		'reparees'   => array(),
		'illisibles' => array(),
		'date'       => current_time( 'mysql' ),
		'essai'      => ! $ecrire,
	);
	foreach ( $tables as list( $table, $id, $cle, $valeur, $objet ) ) {
		$lignes = $wpdb->get_results( $wpdb->prepare( "SELECT {$id} AS id, {$cle} AS cle, {$objet} AS objet, {$valeur} AS valeur FROM {$table} WHERE ( {$valeur} LIKE 'a:%' OR {$valeur} LIKE 's:%' OR {$valeur} LIKE 'O:%' ) AND {$valeur} LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		foreach ( (array) $lignes as $l ) {
			if ( false !== @unserialize( (string) $l->valeur, array( 'allowed_classes' => false ) ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				continue;
			}
			$nom    = str_replace( $wpdb->prefix, '', $table ) . ' ' . $l->cle . ( $table === $wpdb->options ? '' : ' (' . $l->objet . ')' );
			$repare = bigm_serialise_repare( (string) $l->valeur );
			if ( null === $repare ) {
				$cr['illisibles'][] = $nom;
				continue;
			}
			$cr['reparees'][] = $nom;
			if ( $ecrire ) {
				$wpdb->update( $table, array( $valeur => $repare ), array( $id => (int) $l->id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}
	if ( $ecrire && $cr['reparees'] ) {
		wp_cache_flush();
		if ( function_exists( 'bigm_vider_cache' ) ) {
			bigm_vider_cache();
		}
	}
	return $cr;
}

/*
 * Une fois, au premier passage d'un administrateur, puis compte rendu en haut de l'admin.
 */
add_action(
	'admin_init',
	static function () {
		if ( '1' === get_option( 'bigm_reparation_serialisations' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'bigm_reparation_serialisations', '1', false );
		update_option( 'bigm_reparation_serialisations_cr', bigm_reparer_serialisations(), false );
	}
);
add_action(
	'admin_notices',
	static function () {
		$cr = get_option( 'bigm_reparation_serialisations_cr' );
		if ( ! is_array( $cr ) || ! current_user_can( 'manage_options' ) || ( ! $cr['reparees'] && ! $cr['illisibles'] ) ) {
			return;
		}
		delete_option( 'bigm_reparation_serialisations_cr' );
		echo '<div class="notice notice-' . ( $cr['illisibles'] ? 'warning' : 'success' ) . '"><p><strong>Big M</strong> : ' . (int) count( $cr['reparees'] ) . ' donnée(s) abîmée(s) par le changement d’adresse réparée(s) (slides, réglages…).';
		if ( $cr['illisibles'] ) {
			echo ' Encore illisibles : ' . esc_html( implode( ', ', $cr['illisibles'] ) ) . '.';
		}
		echo '</p></div>';
	}
);

/*
 * POST /wp-json/bigm/v1/maintenance/serialisations (essai=1 : sans rien écrire).
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/maintenance/serialisations',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'args'                => array(
					'essai' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				'callback'            => static fn( WP_REST_Request $r ) => rest_ensure_response( bigm_reparer_serialisations( ! $r['essai'] ) ),
			)
		);
	}
);
