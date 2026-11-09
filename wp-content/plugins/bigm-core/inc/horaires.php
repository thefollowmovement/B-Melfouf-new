<?php
/**
 * Horaires importés de Google : pause de midi.
 *
 * StoreLocatore (2.5) importe les horaires de Google (Places API, regularOpeningHours.periods)
 * jour par jour, mais chaque plage écrase la précédente : un restaurant ouvert 11:15–14:00 puis
 * 18:30–23:00 n'a plus que 18:30–23:00. Sa fiche sait pourtant afficher une pause
 * (break_start, break_end) et en tient compte pour « Ouvert / Fermé ».
 *
 * Sans modifier le plugin : juste après chacun de ses imports (bouton de la fiche, import
 * quotidien), il enregistre la date de l'import (tfmsl_gmb_last_sync) après avoir gardé les
 * plages reçues (option tfmsl_gmb_debug_{id}). bigm-core relit ces plages et réécrit les
 * horaires : de la première ouverture à la dernière fermeture, avec la plus longue coupure
 * entre deux plages comme pause. À retirer quand StoreLocatore le fera lui-même.
 *
 * POST /wp-json/bigm/v1/restaurants/{id}/horaires-google relance l'import d'un restaurant.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Horaires de la fiche (format tfmsl_hours) à partir des plages de Google.
 *
 * @param array<int, array{open?: array, close?: array}> $periodes Plages (jour 0 = dimanche … 6 = samedi ; 7 = dimanche accepté).
 * @return array<string, array{closed: string, open: string, close: string, break_start: string, break_end: string}>
 */
function bigm_horaires_depuis_google( array $periodes ): array {
	$jours = array( 0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday' );
	$heure = static fn( array $p ): string => sprintf( '%02d:%02d', (int) ( $p['hour'] ?? 0 ), (int) ( $p['minute'] ?? 0 ) );
	$min   = static fn( string $h ): int => (int) substr( $h, 0, 2 ) * 60 + (int) substr( $h, 3, 2 );

	$par_jour = array();
	foreach ( $periodes as $p ) {
		$j = $p['open']['day'] ?? null;
		if ( null === $j || ! isset( $jours[ $j ] ) ) {
			continue;
		}
		$ouv = $heure( (array) $p['open'] );
		// Sans fermeture : ouvert 24 h sur 24 (comme le plugin : 00:00).
		$fer = isset( $p['close'] ) ? $heure( (array) $p['close'] ) : '00:00';
		// Fermeture le lendemain (après minuit) : comptée après la fin de la journée.
		$fin = isset( $p['close']['day'] ) && (int) $p['close']['day'] !== (int) $j ? $min( $fer ) + 1440 : $min( $fer );
		$par_jour[ $jours[ $j ] ][] = array(
			'ouv' => $ouv,
			'fer' => $fer,
			'a'   => $min( $ouv ),
			'b'   => $fin <= $min( $ouv ) ? $fin + 1440 : $fin,
		);
	}

	$horaires = array();
	foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $jour ) {
		$plages = $par_jour[ $jour ] ?? array();
		if ( ! $plages ) {
			$horaires[ $jour ] = array( 'closed' => '1', 'open' => '', 'close' => '', 'break_start' => '', 'break_end' => '' );
			continue;
		}
		usort( $plages, static fn( $x, $y ) => $x['a'] <=> $y['a'] );
		$pause = array( '', '' );
		$ecart = 0;
		for ( $i = 1, $n = count( $plages ); $i < $n; $i++ ) {
			$coupure = $plages[ $i ]['a'] - $plages[ $i - 1 ]['b'];
			if ( $coupure > $ecart ) {
				$ecart = $coupure;
				$pause = array( $plages[ $i - 1 ]['fer'], $plages[ $i ]['ouv'] );
			}
		}
		$derniere          = $plages[ count( $plages ) - 1 ];
		$horaires[ $jour ] = array(
			'closed'      => '0',
			'open'        => $plages[0]['ouv'],
			'close'       => $derniere['fer'],
			'break_start' => $pause[0],
			'break_end'   => $pause[1],
		);
	}
	return $horaires;
}

/**
 * Réécrit les horaires d'un restaurant avec la pause, d'après les plages du dernier import
 * de StoreLocatore. Ne fait rien si ces plages manquent ou si les horaires sont déjà justes.
 *
 * @param int $id Restaurant (tfmsl_store).
 * @return bool Horaires réécrits.
 */
function bigm_horaires_corriger( int $id ): bool {
	$brut = get_option( 'tfmsl_gmb_debug_' . $id );
	if ( ! is_array( $brut ) || empty( $brut['periods'] ) || ! is_array( $brut['periods'] ) ) {
		return false;
	}
	$horaires = bigm_horaires_depuis_google( $brut['periods'] );
	if ( get_post_meta( $id, 'tfmsl_hours', true ) === $horaires ) {
		return false;
	}
	update_post_meta( $id, 'tfmsl_hours', $horaires );
	return true;
}

/*
 * Juste après un import de StoreLocatore (date de l'import enregistrée en dernier).
 */
$bigm_apres_import = static function ( $meta_id, $post_id, $cle ) {
	if ( 'tfmsl_gmb_last_sync' === $cle && 'tfmsl_store' === get_post_type( (int) $post_id ) ) {
		bigm_horaires_corriger( (int) $post_id );
	}
};
add_action( 'added_post_meta', $bigm_apres_import, 10, 3 );
add_action( 'updated_post_meta', $bigm_apres_import, 10, 3 );
unset( $bigm_apres_import );

/*
 * POST /wp-json/bigm/v1/restaurants/{id}/horaires-google : relance l'import des horaires Google
 * d'un restaurant par StoreLocatore (comme le bouton de la fiche), pause comprise.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/restaurants/(?P<id>\d+)/horaires-google',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn( WP_REST_Request $r ): bool => 'tfmsl_store' === get_post_type( (int) $r['id'] ) && current_user_can( 'edit_post', (int) $r['id'] ),
				'callback'            => static function ( WP_REST_Request $r ) {
					if ( ! class_exists( 'TFMSL_Google_API' ) ) {
						return new WP_Error( 'bigm_horaires_plugin', 'StoreLocatore n’est pas actif.', array( 'status' => 400 ) );
					}
					$id       = (int) $r['id'];
					$resultat = TFMSL_Google_API::instance()->sync_store_hours( $id, true );
					if ( is_wp_error( $resultat ) ) {
						$resultat->add_data( array( 'status' => 502 ) );
						return $resultat;
					}
					return rest_ensure_response(
						array(
							'id'       => $id,
							'horaires' => get_post_meta( $id, 'tfmsl_hours', true ),
						)
					);
				},
			)
		);
	}
);
