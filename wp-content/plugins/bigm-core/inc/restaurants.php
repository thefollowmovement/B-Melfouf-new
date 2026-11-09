<?php
/**
 * Restaurants : réglages du plugin StoreLocatore de l'agence (boutiques tfmsl_store) pour Big M,
 * sans le modifier. Depuis sa version 2.2, le plugin charge ses fichiers seulement là où ils
 * servent, lit la recherche ?q= de l'accueil, normalise la recherche et propose un style
 * « Clair, aux couleurs du site » (couleurs et polices du kit Elementor, donc de la charte).
 * Il reste ici :
 *
 * - les adresses : fiches sous /restaurants/{restaurant}/, sans liste du plugin ; /restaurants/
 *   est la page qui contient le widget Store – Locator (le plugin la trouve seul) ;
 * - les mots : « Restaurants » au lieu de « Boutiques » ;
 * - deux réglages par défaut, tant qu'ils n'ont pas été enregistrés : fiche en style clair,
 *   barre d'actions mobile coupée (la barre d'onglets Big M occupe le bas de l'écran) ;
 * - le type de cuisine « Burgers halal » par défaut, pour les données structurées Restaurant ;
 * - le Google Place ID lisible et modifiable par l'API REST, pour une saisie en lot, et une
 *   recherche des Place ID possibles d'un restaurant avec la clé Google du plugin.
 *
 * Rien ne se passe si le plugin n'est pas actif.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le plugin StoreLocatore est-il actif ?
 */
function bigm_storelocator_actif(): bool {
	return post_type_exists( 'tfmsl_store' );
}

/*
 * Adresses et libellés : « Restaurants » au lieu de « Boutiques ».
 */
add_filter(
	'register_post_type_args',
	static function ( array $args, string $type ): array {
		if ( 'tfmsl_store' !== $type ) {
			return $args;
		}
		$args['rewrite']     = array(
			'slug'       => 'restaurants',
			'with_front' => false,
		);
		// Pas de liste du plugin : /restaurants/ est la page Elementor du store locator.
		$args['has_archive'] = false;
		$args['labels']      = array_merge(
			(array) ( $args['labels'] ?? array() ),
			array(
				'name'               => 'Restaurants',
				'singular_name'      => 'Restaurant',
				'menu_name'          => 'Restaurants',
				'all_items'          => 'Tous les restaurants',
				'add_new'            => 'Ajouter un restaurant',
				'add_new_item'       => 'Ajouter un restaurant',
				'edit_item'          => 'Modifier le restaurant',
				'new_item'           => 'Nouveau restaurant',
				'view_item'          => 'Voir le restaurant',
				'search_items'       => 'Rechercher un restaurant',
				'not_found'          => 'Aucun restaurant trouvé',
				'not_found_in_trash' => 'Aucun restaurant dans la corbeille',
				'archives'           => 'Restaurants',
			)
		);
		return $args;
	},
	10,
	2
);

/*
 * Textes du plugin vus par les visiteurs : « restaurant » plutôt que « boutique ».
 * Les surcharges de la page Traductions du plugin restent prioritaires.
 */
add_filter(
	'gettext_storelocatore',
	static function ( string $traduction, string $texte ): string {
		static $textes = array(
			'Boutiques'                                     => 'Restaurants',
			'Boutique'                                      => 'Restaurant',
			'Trouvez votre boutique'                        => 'Trouvez votre restaurant',
			'Voir la boutique →'                            => 'Voir le restaurant',
			'Aucune boutique trouvée pour cette recherche.' => 'Aucun restaurant ne correspond à cette recherche.',
			'Rechercher une ville, une boutique…'           => 'Rechercher une ville, un restaurant…',
			'%d boutique(s) disponible(s)'                  => '%d restaurant(s)',
			'Voir la fiche de la boutique'                  => 'Voir la fiche du restaurant',
			'Présentez ce code en boutique.'                => 'Présentez ce code au restaurant.',
			'Toutes nos boutiques'                          => 'Tous nos restaurants',
			'Voir la boutique'                              => 'Voir le restaurant',
			'Rechercher une boutique'                       => 'Rechercher un restaurant',
			'Nos dernières boutiques'                       => 'Nos derniers restaurants',
			'Aucune promo en cours pour cette boutique.'    => 'Aucune promo en cours dans ce restaurant.',
			'Boutique introuvable.'                         => 'Restaurant introuvable.',
		);
		return $textes[ $texte ] ?? $traduction;
	},
	10,
	2
);

/*
 * Réglages par défaut du plugin pour Big M, tant qu'ils n'ont pas été enregistrés :
 * fiche en style clair (couleurs du kit Elementor), barre d'actions mobile coupée.
 */
add_filter( 'default_option_tfmsl_store_style', static fn() => 'site', 20 );
add_filter( 'default_option_tfmsl_mobile_bar', static fn() => '0', 20 );

/*
 * Si la barre d'actions mobile du plugin est activée malgré tout, elle se place
 * au-dessus de la barre d'onglets Big M (tabbar.css du thème : 68 px + 10 px de marge).
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( wp_style_is( 'tfmsl-public', 'enqueued' ) ) {
			wp_add_inline_style( 'tfmsl-public', '@media (max-width: 760px) { .tfmsl-mobile-bar { bottom: calc(90px + env(safe-area-inset-bottom, 0px)); } body.single-tfmsl_store { padding-bottom: calc(170px + env(safe-area-inset-bottom, 0px)); } }' );
		}
	},
	30
);

/*
 * Données structurées des restaurants : le plugin écrit « Restaurant » quand le type de cuisine
 * est rempli, « LocalBusiness » sinon. Sans saisie, on lui donne celui de Big M (brief, § 10).
 */
add_filter(
	'get_post_metadata',
	static function ( $valeur, $post_id, $cle, $seul ) {
		static $lecture = false;
		if ( 'tfmsl_cuisine_type' !== $cle || $lecture || 'tfmsl_store' !== get_post_type( (int) $post_id ) ) {
			return $valeur;
		}
		$lecture = true;
		$reelle  = (string) get_post_meta( (int) $post_id, 'tfmsl_cuisine_type', true );
		$lecture = false;
		if ( '' !== $reelle ) {
			return $valeur;
		}
		$defaut = 'Burgers halal';
		return $seul ? $defaut : array( $defaut );
	},
	10,
	4
);

/*
 * Google Place ID des restaurants (avis Google, horaires, lien « Laisser un avis » du plugin) dans
 * l'API REST : champ meta de /wp/v2/tfmsl_store/{id}, modifiable par les comptes qui peuvent
 * modifier le restaurant. Une saisie en lot (75 restaurants) n'a plus à passer par l'écran de
 * chaque fiche. Le plugin garde sa propre saisie ; un Place ID n'a que des lettres, chiffres, - et _.
 */
add_action(
	'init',
	static function () {
		if ( ! bigm_storelocator_actif() ) {
			return;
		}
		register_post_meta(
			'tfmsl_store',
			'tfmsl_gmb_place_id',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => static fn( $valeur ): string => (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $valeur ),
				'auth_callback'     => static fn( $permis, $cle, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
			)
		);
	},
	20
);

/**
 * Place ID possibles d'un restaurant : recherche texte de l'API Places (New) de Google
 * (« Big M » + adresse de la fiche), près des coordonnées de la fiche, avec la clé Google des
 * réglages de StoreLocatore (celle qui sert aux avis et aux horaires). Rien n'est enregistré.
 *
 * @param int $id Restaurant (tfmsl_store).
 * @return array<int, array{place_id: string, nom: string, adresse: string, statut: string, distance_m: ?int, maps: string}>|WP_Error
 */
function bigm_restaurant_place_candidats( int $id ) {
	$cle = trim( (string) get_option( 'tfmsl_google_api_key', '' ) );
	if ( '' === $cle ) {
		return new WP_Error( 'bigm_place_cle', 'Clé API Google absente des réglages de StoreLocatore.', array( 'status' => 400 ) );
	}
	$m       = static fn( string $k ): string => trim( (string) get_post_meta( $id, $k, true ) );
	$requete = trim( 'Big M ' . $m( 'tfmsl_address' ) . ', ' . $m( 'tfmsl_zip' ) . ' ' . $m( 'tfmsl_city' ) );
	$corps   = array(
		'textQuery'    => $requete,
		'languageCode' => 'fr',
		'regionCode'   => false !== stripos( $m( 'tfmsl_country' ), 'gal' ) ? 'SN' : 'FR',
		'pageSize'     => 5,
	);
	$lat     = $m( 'tfmsl_lat' );
	$lng     = $m( 'tfmsl_lng' );
	$centre  = is_numeric( $lat ) && is_numeric( $lng );
	if ( $centre ) {
		$corps['locationBias'] = array(
			'circle' => array(
				'center' => array(
					'latitude'  => (float) $lat,
					'longitude' => (float) $lng,
				),
				'radius' => 2000.0,
			),
		);
	}
	$reponse = wp_remote_post(
		'https://places.googleapis.com/v1/places:searchText',
		array(
			'timeout' => 15,
			'headers' => array(
				'Content-Type'     => 'application/json',
				'X-Goog-Api-Key'   => $cle,
				'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location,places.businessStatus,places.googleMapsUri',
			),
			'body'    => wp_json_encode( $corps ),
		)
	);
	if ( is_wp_error( $reponse ) ) {
		return new WP_Error( 'bigm_place_reseau', $reponse->get_error_message(), array( 'status' => 502 ) );
	}
	$donnees = json_decode( (string) wp_remote_retrieve_body( $reponse ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $reponse ) ) {
		return new WP_Error( 'bigm_place_google', 'Google : ' . ( $donnees['error']['message'] ?? 'HTTP ' . wp_remote_retrieve_response_code( $reponse ) ), array( 'status' => 502 ) );
	}
	$out = array();
	foreach ( (array) ( $donnees['places'] ?? array() ) as $p ) {
		$loc   = $p['location'] ?? array();
		$dist  = null;
		if ( $centre && isset( $loc['latitude'], $loc['longitude'] ) ) {
			$r    = M_PI / 180;
			$x    = sin( ( $loc['latitude'] - (float) $lat ) * $r / 2 );
			$y    = sin( ( $loc['longitude'] - (float) $lng ) * $r / 2 );
			$dist = (int) round( 12742000 * asin( sqrt( $x * $x + cos( (float) $lat * $r ) * cos( $loc['latitude'] * $r ) * $y * $y ) ) );
		}
		$out[] = array(
			'place_id'   => (string) ( $p['id'] ?? '' ),
			'nom'        => (string) ( $p['displayName']['text'] ?? '' ),
			'adresse'    => (string) ( $p['formattedAddress'] ?? '' ),
			'statut'     => (string) ( $p['businessStatus'] ?? '' ),
			'distance_m' => $dist,
			'maps'       => (string) ( $p['googleMapsUri'] ?? '' ),
		);
	}
	return $out;
}

/*
 * GET /wp-json/bigm/v1/restaurants/{id}/place-id : Place ID possibles d'un restaurant, pour les
 * comptes qui peuvent le modifier. La clé reste sur le serveur. Enregistrer le bon avec
 * POST /wp/v2/tfmsl_store/{id} {"meta": {"tfmsl_gmb_place_id": "…"}}, ou dans la fiche.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/restaurants/(?P<id>\d+)/place-id',
			array(
				'methods'             => 'GET',
				'permission_callback' => static fn( WP_REST_Request $r ): bool => 'tfmsl_store' === get_post_type( (int) $r['id'] ) && current_user_can( 'edit_post', (int) $r['id'] ),
				'callback'            => static function ( WP_REST_Request $r ) {
					$c = bigm_restaurant_place_candidats( (int) $r['id'] );
					return is_wp_error( $c ) ? $c : rest_ensure_response(
						array(
							'id'        => (int) $r['id'],
							'actuel'    => (string) get_post_meta( (int) $r['id'], 'tfmsl_gmb_place_id', true ),
							'candidats' => $c,
						)
					);
				},
			)
		);
	}
);
