<?php
/**
 * Appels à Google (Places API) : planifiés et plafonnés.
 *
 * StoreLocatore interroge Google pour les horaires (import), la note et les avis (fiche, tableau
 * de bord). Laissé seul, il peut dépasser les 1 000 appels gratuits par mois et par catégorie :
 * les avis d'une fiche ne restent que 6 h en mémoire, chaque visite après ce délai rappelle Google,
 * et l'import quotidien appelle deux fois par restaurant et par jour.
 *
 * bigm-core, sans modifier le plugin :
 * - planification chaque nuit (bigm_google_planning) : horaires de chaque restaurant tous les
 *   N jours (réglage google_horaires_jours, 3), note et avis tous les N jours (google_avis_jours, 7),
 *   par petits groupes (nombre de restaurants ÷ N par nuit). Un seul appel par restaurant remplit
 *   la mémoire de la fiche (tfmsl_reviews_{id}) et celle du tableau de bord
 *   (tfmsl_place_summary_{id}), gardées 8 jours de plus que la fréquence ;
 * - l'import quotidien de StoreLocatore (tfmsl_sync_google_hours) est débranché : la planification
 *   le remplace (case « Synchronisation automatique » des fiches sans effet) ;
 * - disjoncteur (pre_http_request) : une page vue par un visiteur n'appelle jamais Google ; seuls la
 *   planification, la tâche planifiée de WordPress, l'admin et l'API REST des comptes qui modifient
 *   les restaurants le peuvent ; au-delà du plafond mensuel par catégorie (google_plafond, 900),
 *   plus aucun appel jusqu'au mois suivant. Compteur : option bigm_google_appels.
 *
 * Catégories (tarifs Google) : « details » = Place Details Enterprise (horaires, note) ;
 * « avis » = Place Details Enterprise + Atmosphere (champ reviews) ; « recherche » = Text Search
 * (Place ID). 1 000 appels gratuits par mois et par catégorie.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Catégorie de facturation d'une requête à places.googleapis.com, ou '' si ce n'en est pas une.
 *
 * @param string $url  Adresse.
 * @param array  $args Arguments de la requête (en-têtes).
 */
function bigm_google_categorie( string $url, array $args ): string {
	if ( 'places.googleapis.com' !== strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) ) {
		return '';
	}
	if ( str_contains( $url, ':search' ) ) {
		return 'recherche';
	}
	$entetes = array_change_key_case( (array) ( $args['headers'] ?? array() ) );
	$champs  = (string) ( $entetes['x-goog-fieldmask'] ?? '' );
	if ( '' === $champs ) {
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $q );
		$champs = (string) ( $q['fields'] ?? '' );
	}
	return (bool) preg_match( '/\breviews?\b|reviewSummary/', $champs ) ? 'avis' : 'details';
}

/**
 * Appels du mois en cours, par catégorie.
 *
 * @return array{mois: string, details: int, avis: int, recherche: int, bloques: int}
 */
function bigm_google_compteur(): array {
	$c    = get_option( 'bigm_google_appels' );
	$mois = current_time( 'Y-m' );
	if ( ! is_array( $c ) || ( $c['mois'] ?? '' ) !== $mois ) {
		$c = array( 'mois' => $mois );
	}
	return array_merge( array( 'details' => 0, 'avis' => 0, 'recherche' => 0, 'bloques' => 0 ), $c );
}

/**
 * Plafond mensuel par catégorie.
 */
function bigm_google_plafond(): int {
	return max( 0, (int) bigm_reglage( 'google_plafond' ) );
}

/**
 * Le contexte peut-il appeler Google ? Jamais une page vue par un visiteur.
 */
function bigm_google_contexte_autorise(): bool {
	$autorise = ! empty( $GLOBALS['bigm_google_planification'] )
		|| wp_doing_cron()
		|| ( defined( 'WP_CLI' ) && WP_CLI )
		|| ( is_admin() && ! wp_doing_ajax() && current_user_can( 'edit_posts' ) )
		|| ( wp_doing_ajax() && current_user_can( 'edit_posts' ) )
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_posts' ) );
	return (bool) apply_filters( 'bigm_google_contexte_autorise', $autorise );
}

/*
 * Disjoncteur : avant chaque requête à Google, contexte et plafond ; sinon, compte l'appel.
 */
add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		if ( false !== $pre ) {
			return $pre;
		}
		$cat = bigm_google_categorie( (string) $url, (array) $args );
		if ( '' === $cat ) {
			return $pre;
		}
		$c = bigm_google_compteur();
		if ( ! bigm_google_contexte_autorise() ) {
			return new WP_Error( 'bigm_google_visiteur', 'Appel à Google refusé : seule la planification de bigm-core interroge Google (avis et horaires en mémoire).' );
		}
		if ( $c[ $cat ] >= bigm_google_plafond() ) {
			++$c['bloques'];
			update_option( 'bigm_google_appels', $c, false );
			return new WP_Error( 'bigm_google_plafond', sprintf( 'Plafond mensuel des appels à Google atteint (%d, catégorie « %s ») : reprise le mois prochain. Réglages Big M → Google.', bigm_google_plafond(), $cat ) );
		}
		++$c[ $cat ];
		update_option( 'bigm_google_appels', $c, false );
		return $pre;
	},
	5,
	3
);

/**
 * Avis au format de la fiche (TFMSL_Google_API::get_reviews) à partir du résumé du tableau de bord.
 *
 * @param array $resume Résumé (get_place_summary).
 * @return array<int, array<string, mixed>>
 */
function bigm_google_avis_fiche( array $resume ): array {
	$avis = array();
	foreach ( (array) ( $resume['reviews'] ?? array() ) as $r ) {
		if ( (int) ( $r['rating'] ?? 0 ) < 1 ) {
			continue;
		}
		$avis[] = array(
			'author_name'               => (string) ( $r['author_name'] ?? '' ),
			'profile_photo_url'         => (string) ( $r['profile_photo_url'] ?? '' ),
			'rating'                    => (int) $r['rating'],
			'text'                      => (string) ( $r['text'] ?? '' ),
			'relative_time_description' => (string) ( $r['relative_time_description'] ?? '' ),
		);
	}
	return $avis;
}

/**
 * Note et avis d'un restaurant : un appel, deux mémoires (fiche et tableau de bord).
 *
 * @param int $id Restaurant.
 * @return bool Avis à jour.
 */
function bigm_google_maj_avis( int $id ): bool {
	if ( ! class_exists( 'TFMSL_Google_API' ) ) {
		return false;
	}
	$resume = TFMSL_Google_API::instance()->get_place_summary( $id, true, true );
	// Daté même en cas d'échec : pas de nouvel essai (facturé) avant la prochaine échéance.
	update_post_meta( $id, '_bigm_avis_maj', time() );
	if ( ! is_array( $resume ) || ! $resume ) {
		return false;
	}
	$duree = ( max( 1, (int) bigm_reglage( 'google_avis_jours' ) ) + 8 ) * DAY_IN_SECONDS;
	set_transient( 'tfmsl_place_summary_' . $id, $resume, $duree );
	set_transient( 'tfmsl_reviews_' . $id, bigm_google_avis_fiche( $resume ), $duree );
	return true;
}

/**
 * Horaires d'un restaurant (import de StoreLocatore).
 *
 * @param int $id Restaurant.
 */
function bigm_google_maj_horaires( int $id ): bool {
	if ( ! class_exists( 'TFMSL_Google_API' ) ) {
		return false;
	}
	$r = TFMSL_Google_API::instance()->sync_store_hours( $id, true );
	update_post_meta( $id, '_bigm_horaires_maj', time() );
	return true === $r;
}

/**
 * Restaurants à mettre à jour : Place ID renseigné, dernière mise à jour la plus ancienne d'abord,
 * dus depuis la fréquence (moins une demi-journée de marge).
 *
 * @param string $cle   Meta de la dernière mise à jour (_bigm_horaires_maj, _bigm_avis_maj).
 * @param int    $jours Fréquence.
 * @param int    $max   Nombre au plus.
 * @return int[]
 */
function bigm_google_dus( string $cle, int $jours, int $max ): array {
	if ( $max < 1 || ! post_type_exists( 'tfmsl_store' ) ) {
		return array();
	}
	$ids  = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array( array( 'key' => 'tfmsl_gmb_place_id', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
	$seuil = time() - ( $jours * DAY_IN_SECONDS - 12 * HOUR_IN_SECONDS );
	$dates = array();
	foreach ( $ids as $id ) {
		$t = (int) get_post_meta( $id, $cle, true );
		if ( $t <= $seuil ) {
			$dates[ $id ] = $t;
		}
	}
	asort( $dates );
	return array_slice( array_keys( $dates ), 0, $max );
}

/**
 * Un passage de la planification : une part des restaurants dus pour les horaires et les avis.
 *
 * @param int|null $horaires Nombre de restaurants pour les horaires (null : total ÷ fréquence).
 * @param int|null $avis     Nombre pour les avis (null : total ÷ fréquence).
 * @return array{horaires: int[], avis: int[]}
 */
function bigm_google_planification( ?int $horaires = null, ?int $avis = null ): array {
	$total = post_type_exists( 'tfmsl_store' ) ? count(
		get_posts(
			array(
				'post_type'      => 'tfmsl_store',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( array( 'key' => 'tfmsl_gmb_place_id', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			)
		)
	) : 0;
	$jh = max( 1, (int) bigm_reglage( 'google_horaires_jours' ) );
	$ja = max( 1, (int) bigm_reglage( 'google_avis_jours' ) );
	$fait = array( 'horaires' => array(), 'avis' => array() );
	$GLOBALS['bigm_google_planification'] = true;
	foreach ( bigm_google_dus( '_bigm_horaires_maj', $jh, $horaires ?? (int) ceil( $total / $jh ) ) as $id ) {
		bigm_google_maj_horaires( $id );
		$fait['horaires'][] = $id;
	}
	foreach ( bigm_google_dus( '_bigm_avis_maj', $ja, $avis ?? (int) ceil( $total / $ja ) ) as $id ) {
		bigm_google_maj_avis( $id );
		$fait['avis'][] = $id;
	}
	$GLOBALS['bigm_google_planification'] = false;
	update_option( 'bigm_google_dernier_passage', array( 'date' => current_time( 'mysql' ), 'horaires' => count( $fait['horaires'] ), 'avis' => count( $fait['avis'] ) ), false );
	return $fait;
}

/*
 * Chaque nuit vers 4 h (heure du site).
 */
add_action( 'bigm_google_planning', static fn() => bigm_google_planification() );
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'bigm_google_planning' ) ) {
			$quatre_h = ( new DateTimeImmutable( 'tomorrow 04:00', wp_timezone() ) )->getTimestamp();
			wp_schedule_event( $quatre_h, 'daily', 'bigm_google_planning' );
		}
		// Import quotidien de StoreLocatore débranché : la planification le remplace.
		if ( class_exists( 'TFMSL_Cron_Handler' ) && method_exists( 'TFMSL_Cron_Handler', 'instance' ) ) {
			remove_action( 'tfmsl_sync_google_hours', array( TFMSL_Cron_Handler::instance(), 'sync_all_stores' ) );
		}
	},
	30
);
register_deactivation_hook( BIGM_CORE_FILE, static fn() => wp_clear_scheduled_hook( 'bigm_google_planning' ) );

/*
 * Réglages Big M → Google : compteur du mois et dernier passage.
 */
add_action(
	'bigm_reglages_section',
	static function ( string $section ) {
		if ( 'google' !== $section ) {
			return;
		}
		$c      = bigm_google_compteur();
		$p      = bigm_google_plafond();
		$dernier = get_option( 'bigm_google_dernier_passage' );
		$prochain = wp_next_scheduled( 'bigm_google_planning' );
		printf(
			'<p><strong>Ce mois-ci :</strong> horaires et notes %1$d / %4$d · avis %2$d / %4$d · recherches de Place ID %3$d / %4$d%5$s</p>',
			(int) $c['details'],
			(int) $c['avis'],
			(int) $c['recherche'],
			(int) $p,
			$c['bloques'] ? ' · <strong>' . (int) $c['bloques'] . ' appel(s) bloqué(s) par le plafond</strong>' : ''
		);
		printf(
			'<p class="description">Dernier passage : %s. Prochain : %s.</p>',
			esc_html( is_array( $dernier ) ? sprintf( '%s (%d horaires, %d avis)', mysql2date( 'j F Y à H:i', $dernier['date'] ), $dernier['horaires'], $dernier['avis'] ) : 'aucun' ),
			esc_html( $prochain ? wp_date( 'j F Y à H:i', $prochain ) : 'non planifié' )
		);
	}
);

/*
 * POST /wp-json/bigm/v1/google/planification {horaires?, avis?} : un passage tout de suite
 * (par exemple avis = 70 pour remplir toutes les fiches d'un coup), dans la limite du plafond.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/google/planification',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'callback'            => static function ( WP_REST_Request $r ) {
					$h    = $r->get_param( 'horaires' );
					$a    = $r->get_param( 'avis' );
					$fait = bigm_google_planification( null === $h ? null : max( 0, (int) $h ), null === $a ? null : max( 0, (int) $a ) );
					return rest_ensure_response(
						array(
							'horaires' => count( $fait['horaires'] ),
							'avis'     => count( $fait['avis'] ),
							'compteur' => bigm_google_compteur(),
						)
					);
				},
			)
		);
	}
);
