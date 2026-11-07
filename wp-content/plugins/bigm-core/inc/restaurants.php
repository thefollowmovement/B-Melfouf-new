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
 * - le type de cuisine « Burgers halal » par défaut, pour les données structurées Restaurant.
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

