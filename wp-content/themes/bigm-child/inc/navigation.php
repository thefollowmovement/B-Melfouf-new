<?php
/**
 * Menus, logo et liens de l'en-tête, du tiroir, de la barre d'onglets et du pied de page.
 *
 * Les liens se règlent dans Apparence → Menus. Au premier passage d'un
 * administrateur, les menus du prototype sont créés et placés ; un emplacement
 * laissé vide affiche ces mêmes liens par défaut.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Emplacements de menus : nom affiché, et liens par défaut (titre, chemin, icône pour les onglets).
 *
 * @return array<string, array{nom: string, liens: array<int, array{0: string, 1: string, 2?: string}>}>
 */
function bigm_child_menus(): array {
	$nnbsp = "\u{202F}";
	return array(
		'bigm-principal'       => array(
			'nom'   => 'En-tête',
			'liens' => array(
				array( 'Notre carte', '/carte/' ),
				array( 'Actualités', '/actualites/' ),
				array( 'Franchise', '/devenir-franchise/' ),
				array( "Recrutez-nous{$nnbsp}!", '/recrutement/' ),
			),
		),
		'bigm-tiroir'          => array(
			'nom'   => 'Menu latéral',
			'liens' => array(
				array( 'Accueil', '/' ),
				array( 'Notre carte', '/carte/' ),
				array( 'Nos restaurants', '/restaurants/' ),
				array( 'Actualités', '/actualites/' ),
				array( 'Devenir franchisé', '/devenir-franchise/' ),
				array( "Recrutez-nous{$nnbsp}!", '/recrutement/' ),
				array( 'Jeu concours', '/jeu-concours/' ),
			),
		),
		'bigm-onglets'         => array(
			'nom'   => 'Barre d’onglets mobile (3 liens)',
			'liens' => array(
				array( 'Accueil', '/', 'accueil' ),
				array( 'La carte', '/carte/', 'carte' ),
				array( 'Restos', '/restaurants/', 'restos' ),
			),
		),
		'bigm-pied-produits'   => array(
			'nom'   => 'Pied de page : Produits',
			'liens' => array(
				array( 'Notre carte', '/carte/' ),
				array( 'Nos burgers', '/carte/burgers/' ),
				array( 'Nos menus', '/carte/menus/' ),
				array( 'Allergènes', '/carte/burgers/#allergenes' ),
			),
		),
		'bigm-pied-entreprise' => array(
			'nom'   => 'Pied de page : L’entreprise',
			'liens' => array(
				array( 'Notre histoire', '/#legendes' ),
				array( 'Actualités et presse', '/actualites/' ),
				array( 'Devenir franchisé', '/devenir-franchise/' ),
				array( 'Recrutement', '/recrutement/' ),
				array( 'Jeu concours', '/jeu-concours/' ),
			),
		),
		'bigm-pied-legal'      => array(
			'nom'   => 'Pied de page : informations légales',
			'liens' => array(
				array( 'Mentions légales', '/mentions-legales/' ),
				array( 'Politique de confidentialité', '/politique-de-confidentialite/' ),
				array( 'Gestion des cookies', '/cookies/' ),
			),
		),
	);
}

add_action(
	'after_setup_theme',
	static function () {
		register_nav_menus( wp_list_pluck( bigm_child_menus(), 'nom' ) );
	}
);

/**
 * URL absolue d'un lien : les chemins (« /carte/ ») suivent l'adresse du site,
 * ce qui évite de réécrire les menus quand la préprod devient le site en ligne.
 *
 * @param string $url Lien saisi.
 */
function bigm_child_url( string $url ): string {
	return ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) ? home_url( $url ) : $url;
}

/**
 * Le lien mène-t-il à la page affichée (ou à sa rubrique) ?
 *
 * @param string $url Lien.
 */
function bigm_child_est_courant( string $url ): bool {
	$cible = wp_parse_url( bigm_child_url( $url ) );
	$site  = wp_parse_url( home_url( '/' ) );
	if ( ! $cible || ( isset( $cible['host'] ) && $cible['host'] !== ( $site['host'] ?? '' ) ) || isset( $cible['fragment'] ) ) {
		return false;
	}
	$chemin = trailingslashit( $cible['path'] ?? '/' );
	if ( trailingslashit( $site['path'] ?? '/' ) === $chemin ) {
		return is_front_page();
	}
	// « La carte » reste allumée sur les catégories et les fiches produit.
	$carte = get_post_type_archive_link( 'bigm_produit' );
	if ( $carte && trailingslashit( (string) wp_parse_url( $carte, PHP_URL_PATH ) ) === $chemin
		&& ( is_post_type_archive( 'bigm_produit' ) || is_tax( 'bigm_categorie' ) || is_singular( 'bigm_produit' ) ) ) {
		return true;
	}
	$ici = trailingslashit( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- comparé, jamais affiché.
	return str_starts_with( $ici, $chemin );
}

/**
 * Liens d'un emplacement : ceux du menu placé, sinon ceux du prototype.
 *
 * @param string $emplacement Emplacement de menu.
 * @return array<int, array{titre: string, url: string, cible: string, icone: string, courant: bool}>
 */
function bigm_child_liens( string $emplacement ): array {
	$liens     = array();
	$locations = get_nav_menu_locations();
	$items     = ! empty( $locations[ $emplacement ] ) ? wp_get_nav_menu_items( $locations[ $emplacement ] ) : false;

	if ( $items ) {
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent ) {
				continue; // Un seul niveau, comme dans le prototype.
			}
			$icone = '';
			foreach ( (array) $item->classes as $classe ) {
				if ( str_starts_with( (string) $classe, 'bigm-icone-' ) ) {
					$icone = substr( (string) $classe, 11 );
				}
			}
			$liens[] = array(
				'titre' => (string) $item->title,
				'url'   => (string) $item->url,
				'cible' => (string) $item->target,
				'icone' => $icone,
			);
		}
	} else {
		foreach ( bigm_child_menus()[ $emplacement ]['liens'] ?? array() as $lien ) {
			$liens[] = array(
				'titre' => $lien[0],
				'url'   => $lien[1],
				'cible' => '',
				'icone' => $lien[2] ?? '',
			);
		}
	}

	foreach ( $liens as &$lien ) {
		$lien['courant'] = bigm_child_est_courant( $lien['url'] );
		$lien['url']     = bigm_child_url( $lien['url'] );
	}
	return $liens;
}

/**
 * Attributs d'un lien de menu : href, target, aria-current.
 *
 * @param array{url: string, cible: string, courant: bool} $lien Lien.
 */
function bigm_child_attributs_lien( array $lien ): string {
	$attr = ' href="' . esc_url( $lien['url'] ) . '"';
	if ( '_blank' === $lien['cible'] ) {
		$attr .= ' target="_blank" rel="noopener"';
	}
	return $attr . ( $lien['courant'] ? ' aria-current="page"' : '' );
}

/*
 * Premier passage d'un administrateur : les menus du prototype sont créés et placés
 * dans les emplacements encore vides. Une seule fois.
 */
add_action(
	'admin_init',
	static function () {
		if ( get_option( 'bigm_child_menus_crees' ) || ! current_user_can( 'edit_theme_options' ) || wp_doing_ajax() ) {
			return;
		}
		$locations = get_nav_menu_locations();
		foreach ( bigm_child_menus() as $emplacement => $menu ) {
			if ( ! empty( $locations[ $emplacement ] ) ) {
				continue;
			}
			$nom    = 'Big M — ' . preg_replace( '/ \(.*\)$/', '', $menu['nom'] ); // Sans l'indication entre parenthèses.
			$existe = wp_get_nav_menu_object( $nom );
			$id     = $existe ? (int) $existe->term_id : wp_create_nav_menu( $nom );
			if ( is_wp_error( $id ) ) {
				continue;
			}
			if ( ! $existe ) {
				foreach ( $menu['liens'] as $lien ) {
					wp_update_nav_menu_item(
						$id,
						0,
						array(
							'menu-item-title'   => $lien[0],
							'menu-item-url'     => $lien[1],
							'menu-item-type'    => 'custom',
							'menu-item-status'  => 'publish',
							'menu-item-classes' => isset( $lien[2] ) ? 'bigm-icone-' . $lien[2] : '',
						)
					);
				}
			}
			$locations[ $emplacement ] = $id;
		}
		set_theme_mod( 'nav_menu_locations', $locations );
		update_option( 'bigm_child_menus_crees', BIGM_CHILD_VERSION, false );
	}
);

/**
 * Logo : celui choisi dans l'admin (Apparence → Personnaliser → Identité du site), sinon celui du thème.
 *
 * @return array{url: string, width: int, height: int}
 */
function bigm_child_logo(): array {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( $id ) {
		$img = wp_get_attachment_image_src( $id, 'full' );
		if ( $img ) {
			return array(
				'url'    => $img[0],
				'width'  => (int) $img[1],
				'height' => (int) $img[2],
			);
		}
	}
	return array(
		'url'    => bigm_child_asset_url( 'assets/img/logo.webp' ),
		'width'  => 273,
		'height' => 352,
	);
}

/**
 * Réglage du plugin bigm-core, avec une valeur de secours si le plugin est inactif.
 *
 * @param string $cle    Clé du réglage.
 * @param string $defaut Valeur de secours.
 */
function bigm_child_reglage( string $cle, string $defaut = '' ): string {
	$valeur = function_exists( 'bigm_reglage' ) ? (string) bigm_reglage( $cle ) : '';
	return '' !== $valeur ? $valeur : $defaut;
}

/**
 * Lien des boutons Commander : celui des Réglages Big M, sinon la bande « appli » de l'accueil.
 */
function bigm_child_lien_commander(): string {
	return bigm_child_reglage( 'commander_url', home_url( '/#appli' ) );
}

/**
 * Lien « Restaurants » de l'en-tête.
 */
function bigm_child_lien_restaurants(): string {
	return bigm_child_url( bigm_child_reglage( 'restaurants_url', '/restaurants/' ) );
}
