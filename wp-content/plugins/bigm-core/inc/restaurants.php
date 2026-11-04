<?php
/**
 * Restaurants : intégration du plugin StoreLocatore de l'agence (boutiques tfmsl_store),
 * sans le modifier. Tout passe par les filtres et options de WordPress :
 *
 * - les fiches sont publiées sous /restaurants/{restaurant}/ et la liste sous /restaurants/ ;
 * - la liste affiche la page « Restaurants » (adresse restaurants), composée dans Elementor
 *   avec le widget « Store – Locator » du plugin ;
 * - les couleurs par défaut du plugin sont celles de la charte, et une feuille d'habillage
 *   (assets/css/restaurants.css) reprend les cartes, boutons et épingles de Big M ;
 * - la recherche de l'accueil (/restaurants/?q=…) est appliquée au store locator
 *   (assets/js/restaurants.js) ;
 * - les fichiers du plugin (jQuery, Leaflet) ne sont chargés que sur les pages restaurants.
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

/**
 * Page « Restaurants » (adresse restaurants), affichée à la place de la liste du plugin.
 */
function bigm_page_restaurants_id(): int {
	$page = get_page_by_path( 'restaurants' );
	return $page instanceof WP_Post && 'publish' === $page->post_status ? (int) $page->ID : 0;
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
		$args['has_archive'] = 'restaurants';
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
			'Boutiques'                                    => 'Restaurants',
			'Boutique'                                     => 'Restaurant',
			'Trouvez votre boutique'                       => 'Trouvez votre restaurant',
			'Voir la boutique →'                           => 'Voir le restaurant',
			'Aucune boutique trouvée pour cette recherche.' => 'Aucun restaurant ne correspond à cette recherche.',
			'Rechercher une ville, une boutique…'          => 'Rechercher une ville, un restaurant…',
			'%d boutique(s) disponible(s)'                 => '%d restaurant(s)',
			'Voir la fiche de la boutique'                 => 'Voir la fiche du restaurant',
			'Présentez ce code en boutique.'               => 'Présentez ce code au restaurant.',
		);
		return $textes[ $texte ] ?? $traduction;
	},
	10,
	2
);

/*
 * Couleurs et police par défaut du plugin : celles de la charte (assets/css/tokens.css du thème).
 * Ne s'applique que tant que les réglages du plugin n'ont pas été enregistrés.
 */
foreach ( array(
	'tfmsl_primary_color'   => '#ffffff',
	'tfmsl_secondary_color' => '#f2ede7',
	'tfmsl_accent_color'    => '#c8400a',
	'tfmsl_text_color'      => '#2b1406',
	'tfmsl_bg_color'        => '#faf8f5',
	'tfmsl_font_family'     => 'var(--font-text)',
	// Barre d'actions mobile du plugin : coupée par défaut, la barre d'onglets Big M occupe le bas de l'écran.
	'tfmsl_mobile_bar'      => '0',
) as $bigm_option => $bigm_valeur ) {
	add_filter( "default_option_{$bigm_option}", static fn() => $bigm_valeur, 20 );
}

/*
 * Permaliens recalculés une fois après l'activation du plugin ou une mise à jour de bigm-core.
 */
add_action(
	'init',
	static function () {
		if ( ! bigm_storelocator_actif() ) {
			return;
		}
		if ( get_option( 'bigm_core_permaliens_restaurants' ) !== BIGM_CORE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'bigm_core_permaliens_restaurants', BIGM_CORE_VERSION, false );
		}
	},
	100
);

/*
 * /restaurants/ : la page « Restaurants » (Elementor) remplace la liste du plugin.
 * Le plugin choisit son gabarit à la priorité 10 ; celui du thème reprend la main à la priorité 20.
 */
add_filter(
	'archive_template',
	static function ( string $template ): string {
		if ( is_post_type_archive( 'tfmsl_store' ) && bigm_page_restaurants_id() ) {
			$theme = locate_template( array( 'archive-tfmsl_store.php' ) );
			return $theme ? $theme : $template;
		}
		return $template;
	},
	20
);

/**
 * Contenu de la page « Restaurants » (Elementor, sinon le contenu de la page).
 */
function bigm_page_restaurants(): string {
	$id = bigm_page_restaurants_id();
	if ( ! $id ) {
		return '';
	}
	if ( did_action( 'elementor/loaded' ) ) {
		$document = \Elementor\Plugin::$instance->documents->get( $id );
		if ( $document && $document->is_built_with_elementor() ) {
			return (string) \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id, true );
		}
	}
	return (string) apply_filters( 'the_content', get_post_field( 'post_content', $id ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- filtre de WordPress.
}

/**
 * La page affichée a-t-elle besoin des fichiers du plugin (store locator, fiche, carte) ?
 */
function bigm_page_utilise_storelocator(): bool {
	if ( is_singular( 'tfmsl_store' ) || is_post_type_archive( 'tfmsl_store' ) || get_query_var( 'tfmsl_review' ) ) {
		return true;
	}
	if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- lecture seule.
		return true;
	}
	$id = (int) get_queried_object_id();
	return $id && str_contains( (string) get_post_meta( $id, '_elementor_data', true ), '"widgetType":"tfmsl_' );
}

/*
 * Fichiers du plugin : il charge sa feuille de style, son script, jQuery et Leaflet sur toutes
 * les pages du site. On ne les garde que là où ils servent ; on y ajoute l'habillage Big M.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! bigm_storelocator_actif() ) {
			return;
		}
		if ( ! bigm_page_utilise_storelocator() ) {
			wp_dequeue_style( 'tfmsl-public' );
			wp_dequeue_script( 'tfmsl-public' );
			return;
		}
		$v = static function ( string $rel ): string {
			$path = BIGM_CORE_DIR . '/assets/' . $rel;
			return is_file( $path ) ? (string) filemtime( $path ) : BIGM_CORE_VERSION;
		};
		wp_enqueue_style( 'bigm-restaurants', BIGM_CORE_URL . 'assets/css/restaurants.css', array( 'tfmsl-public' ), $v( 'css/restaurants.css' ) );
		wp_enqueue_script(
			'bigm-restaurants',
			BIGM_CORE_URL . 'assets/js/restaurants.js',
			array(),
			$v( 'js/restaurants.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		// /restaurants/ : styles de la page Elementor et du store locator dès le <head>.
		if ( is_post_type_archive( 'tfmsl_store' ) && bigm_page_restaurants_id() ) {
			wp_enqueue_style( 'bigm-carte' );
			wp_enqueue_style( 'tfmsl-leaflet' );
			wp_enqueue_style( 'tfmsl-markercluster' );
			wp_enqueue_style( 'tfmsl-markercluster-default' );
			wp_enqueue_script( 'tfmsl-markercluster' );
			if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				\Elementor\Core\Files\CSS\Post::create( bigm_page_restaurants_id() )->enqueue();
			}
		}
	},
	30
);

/*
 * Titre de l'onglet de /restaurants/ : celui de la page « Restaurants ».
 */
add_filter(
	'document_title_parts',
	static function ( array $parts ): array {
		if ( is_post_type_archive( 'tfmsl_store' ) && bigm_page_restaurants_id() ) {
			$parts['title'] = get_the_title( bigm_page_restaurants_id() );
		}
		return $parts;
	}
);
