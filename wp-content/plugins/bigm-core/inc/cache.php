<?php
/**
 * LiteSpeed Cache réglé pour Big M (hébergement Hostinger, serveur LiteSpeed).
 *
 * - Réglages appliqués une fois, au premier passage d'un administrateur après l'activation de
 *   LiteSpeed Cache (ou par POST bigm/v1/litespeed) : cache des pages pour les visiteurs, pas pour
 *   les personnes connectées, pas de cache de l'API REST, tout vidé à chaque contenu publié ou
 *   modifié et à chaque mise à jour d'une extension ou du thème, aucune optimisation des CSS et
 *   JS (Elementor), ni QUIC.cloud. Version dans l'option bigm_litespeed : changer
 *   BIGM_LITESPEED_REGLAGES réapplique la liste (filtre bigm_litespeed_reglages).
 * - Pages à formulaire (WPForms, formulaire Elementor Pro) : jamais en cache, leur jeton
 *   anti-spam doit rester valable.
 * - Fiches restaurants : gardées en cache jusqu'au prochain changement d'horaire (ouverture,
 *   pause, fermeture, minuit), pour que « Ouvert » / « Fermé » et la ligne du jour restent justes.
 *   Vidées quand leurs horaires changent (import de Google la nuit).
 * - Vidage complet aussi après les réglages de StoreLocatore et du personnalisateur ; Réglages
 *   Big M et dates des slides : inc/performance.php.
 *
 * Tout passe par les actions de LiteSpeed Cache : sans l'extension, rien ne se passe.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

const BIGM_LITESPEED_REGLAGES = '1';

/**
 * Réglages de LiteSpeed Cache (identifiants de LiteSpeed => valeur).
 *
 * @return array<string, mixed>
 */
function bigm_litespeed_reglages(): array {
	return (array) apply_filters(
		'bigm_litespeed_reglages',
		array(
			'cache'           => true,  // Cache des pages pour les visiteurs.
			'cache-priv'      => false, // Personnes connectées : toujours la page à jour.
			'cache-commenter' => false,
			'cache-rest'      => false, // API REST (fenêtre Commander, StoreLocatore) : jamais en cache.
			'cache-browser'   => false, // Hostinger envoie déjà Cache-Control (7 jours) sur les fichiers.
			'cache-mobile'    => false, // Mêmes pages sur mobile (mise en page en CSS).
			'purge-upgrade'   => true,  // Extension ou thème mis à jour : nouvelles versions des CSS et JS.
			'purge-post_all'  => true,  // Un produit, un restaurant, un article… apparaît sur plusieurs pages.
			'esi'             => false,
			'guest'           => false,
			'guest_optm'      => false,
			'crawler'         => false,
			'optm-css_min'    => false, // Elementor et le thème servent déjà des fichiers courts ;
			'optm-css_comb'   => false, // combiner ou réécrire les CSS et JS casse facilement les widgets.
			'optm-js_min'     => false,
			'optm-js_comb'    => false,
			'optm-html_min'   => false,
			'optm-ucss'       => false,
			'media-lazy'      => false, // WordPress et Elementor posent déjà loading="lazy" (sauf 1re slide).
			'cdn-quic'        => false, // Pas de QUIC.cloud : aucune donnée envoyée à un service tiers.
		)
	);
}

/**
 * Applique les réglages à LiteSpeed Cache, s'il est actif.
 *
 * @return array<string, mixed>|null Valeurs lues après coup, ou null sans LiteSpeed Cache.
 */
function bigm_litespeed_appliquer(): ?array {
	if ( ! defined( 'LSCWP_V' ) ) {
		return null;
	}
	$reglages = bigm_litespeed_reglages();
	do_action( 'litespeed_save_conf', $reglages );
	update_option( 'bigm_litespeed', BIGM_LITESPEED_REGLAGES, false );
	// Pages mises en cache avant ces réglages (pages à formulaire comprises) : vidées.
	bigm_vider_cache();
	$lus = array();
	foreach ( array_keys( $reglages ) as $id ) {
		$lus[ $id ] = apply_filters( 'litespeed_conf', $id );
	}
	return $lus;
}

/*
 * Une fois, au premier passage d'un administrateur après l'activation de LiteSpeed Cache.
 */
add_action(
	'admin_init',
	static function () {
		if ( ! defined( 'LSCWP_V' ) || BIGM_LITESPEED_REGLAGES === get_option( 'bigm_litespeed' ) || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}
		bigm_litespeed_appliquer();
		add_action(
			'admin_notices',
			static function () {
				echo '<div class="notice notice-success is-dismissible"><p>LiteSpeed Cache est réglé pour Big M : cache des pages pour les visiteurs, formulaires et personnes connectées hors cache, fiches restaurants à jour aux heures d’ouverture.</p></div>';
			}
		);
	},
	20
);

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/litespeed',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'callback'            => static function () {
					$lus = bigm_litespeed_appliquer();
					if ( null === $lus ) {
						return new WP_Error( 'bigm_litespeed_absent', 'LiteSpeed Cache n’est pas actif.', array( 'status' => 409 ) );
					}
					return array(
						'version'  => LSCWP_V,
						'reglages' => $lus,
					);
				},
			)
		);
	}
);

/**
 * La page a-t-elle un formulaire (WPForms, formulaire Elementor Pro) ?
 *
 * @param int $post_id Page.
 */
function bigm_page_a_formulaire( int $post_id ): bool {
	$textes = (string) get_post_field( 'post_content', $post_id ) . (string) get_post_meta( $post_id, '_elementor_data', true );
	foreach ( array( '[wpforms', 'wp:wpforms/', '"widgetType":"wpforms"', '"widgetType":"form"' ) as $marque ) {
		if ( str_contains( $textes, $marque ) ) {
			return true;
		}
	}
	return (bool) apply_filters( 'bigm_page_a_formulaire', false, $post_id );
}

/**
 * Prochain changement d'état d'après les horaires d'une fiche (ouverture, début ou fin de
 * pause, fermeture), dans les 8 jours, au même format d'heure que StoreLocatore : heure locale
 * comptée comme UTC (current_time( 'timestamp' )).
 *
 * @param array<string, mixed> $heures     Meta tfmsl_hours.
 * @param int                  $maintenant Heure locale (current_time( 'timestamp' )).
 * @return int Heure du changement (même format), 0 s'il n'y en a pas.
 */
function bigm_horaires_prochain_changement( array $heures, int $maintenant ): int {
	$jours    = array( 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday' );
	$prochain = 0;
	$retenir  = static function ( $t ) use ( &$prochain, $maintenant ): void {
		if ( is_int( $t ) && $t > $maintenant && ( ! $prochain || $t < $prochain ) ) {
			$prochain = $t;
		}
	};
	// Depuis la veille : une fermeture après minuit tombe le lendemain.
	for ( $i = -1; $i <= 7; $i++ ) {
		$ts   = $maintenant + $i * DAY_IN_SECONDS;
		$date = gmdate( 'Y-m-d', $ts );
		$jour = $heures[ $jours[ (int) gmdate( 'N', $ts ) ] ] ?? null;
		if ( ! is_array( $jour ) || '1' === (string) ( $jour['closed'] ?? '' ) || empty( $jour['open'] ) || empty( $jour['close'] ) ) {
			continue;
		}
		$a_l_heure = static fn( string $h ) => strtotime( $date . ' ' . $h . ' UTC' );
		$ouverture = $a_l_heure( (string) $jour['open'] );
		$fermeture = $a_l_heure( (string) $jour['close'] );
		if ( false === $ouverture || false === $fermeture ) {
			continue;
		}
		$retenir( $ouverture );
		$retenir( $fermeture <= $ouverture ? $fermeture + DAY_IN_SECONDS : $fermeture );
		if ( ! empty( $jour['break_start'] ) && ! empty( $jour['break_end'] ) ) {
			$retenir( $a_l_heure( (string) $jour['break_start'] ) );
			$retenir( $a_l_heure( (string) $jour['break_end'] ) );
		}
	}
	return $prochain;
}

/**
 * Durée de cache d'une fiche restaurant, en secondes : jusqu'au prochain changement d'horaire
 * ou au prochain minuit (ligne du jour), plus une minute ; entre 2 minutes et 1 jour.
 *
 * @param int      $post_id Restaurant.
 * @param int|null $maintenant Heure réelle (time()), pour les tests.
 */
function bigm_restaurant_duree_cache( int $post_id, ?int $maintenant = null ): int {
	$reel     = $maintenant ?? time();
	$decalage = wp_timezone()->getOffset( new DateTimeImmutable( '@' . $reel ) ); // Comme current_time( 'timestamp' ), l'horloge de StoreLocatore.
	$local    = $reel + $decalage;
	$heures   = get_post_meta( $post_id, 'tfmsl_hours', true );
	$dates    = array(
		bigm_horaires_prochain_changement( is_array( $heures ) ? $heures : array(), $local ),
		( intdiv( $local, DAY_IN_SECONDS ) + 1 ) * DAY_IN_SECONDS,            // Minuit, heure de Paris.
		( intdiv( $reel, DAY_IN_SECONDS ) + 1 ) * DAY_IN_SECONDS + $decalage, // Minuit UTC : StoreLocatore lit le jour avec date( 'N' ).
	);
	$prochain = min( array_filter( $dates ) );
	$duree    = $prochain - $local + MINUTE_IN_SECONDS;
	return (int) apply_filters( 'bigm_restaurant_duree_cache', max( 2 * MINUTE_IN_SECONDS, min( DAY_IN_SECONDS, $duree ) ), $post_id );
}

/**
 * Règle de cache de la page demandée (template_redirect) : pages à formulaire hors cache, fiches
 * restaurants jusqu'au prochain changement d'horaire.
 */
function bigm_cache_regle_page(): void {
	if ( ! is_singular() || is_user_logged_in() ) {
		return;
	}
	$id = (int) get_queried_object_id();
	if ( is_singular( 'tfmsl_store' ) ) {
		do_action( 'litespeed_control_set_ttl', bigm_restaurant_duree_cache( $id ), 'Big M : horaires du restaurant' );
	} elseif ( bigm_page_a_formulaire( $id ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		do_action( 'litespeed_control_set_nocache', 'Big M : page à formulaire' );
	}
}
add_action( 'template_redirect', 'bigm_cache_regle_page' );

/*
 * Horaires d'un restaurant changés sans enregistrer la fiche (import de Google, la nuit) :
 * sa page est vidée.
 */
$bigm_vider_restaurant = static function ( $meta_id, $post_id, $cle ) {
	if ( 'tfmsl_hours' === $cle && 'tfmsl_store' === get_post_type( (int) $post_id ) ) {
		do_action( 'litespeed_purge_post', (int) $post_id );
	}
};
add_action( 'added_post_meta', $bigm_vider_restaurant, 10, 3 );
add_action( 'updated_post_meta', $bigm_vider_restaurant, 10, 3 );

/*
 * Réglages de StoreLocatore (promos, traductions, style des fiches…) enregistrés dans l'admin :
 * tout est vidé. Les données que le plugin et bigm-core écrivent seuls (diagnostic, avis et
 * notes de Google, drapeaux) ne vident rien.
 */
add_action(
	'updated_option',
	static function ( $option ) {
		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() || ! str_starts_with( (string) $option, 'tfmsl_' ) ) {
			return;
		}
		if ( preg_match( '/^tfmsl_(gmb_|place_summary_|reviews_|rating|needs_flush|installed_version|migrated)/', (string) $option ) ) {
			return;
		}
		bigm_vider_cache();
	}
);

add_action( 'customize_save_after', 'bigm_vider_cache' );
