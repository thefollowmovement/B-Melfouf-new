<?php
/**
 * Activation : adresses (/carte/, /produit/), catégories de la carte et
 * catégories d'articles News et Presse. Les catégories ne sont créées qu'une
 * fois : une catégorie supprimée par le client ne revient pas.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Crée les catégories par défaut, si ce n'est pas déjà fait.
 *
 * @return string[] Catégories créées.
 */
function bigm_core_seed(): array {
	if ( get_option( 'bigm_core_seeded' ) ) {
		return array();
	}
	$crees = array();

	$creer = static function ( array $cat, int $parent, int $ordre ) use ( &$crees ): int {
		$existe = get_term_by( 'slug', $cat['slug'], 'bigm_categorie' );
		if ( $existe ) {
			return (int) $existe->term_id;
		}
		$t = wp_insert_term(
			$cat['nom'],
			'bigm_categorie',
			array(
				'slug'   => $cat['slug'],
				'parent' => $parent,
			)
		);
		if ( is_wp_error( $t ) ) {
			return 0;
		}
		$id = (int) $t['term_id'];
		update_term_meta( $id, '_bigm_ordre', $ordre );
		if ( ! empty( $cat['titre'] ) ) {
			update_term_meta( $id, '_bigm_titre', $cat['titre'] );
		}
		if ( ! empty( $cat['trace'] ) ) {
			update_term_meta( $id, '_bigm_icone', bigm_icone_depuis_trace( $cat['trace'] ) );
		}
		$crees[] = $cat['nom'];
		return $id;
	};

	foreach ( bigm_categories_par_defaut() as $i => $cat ) {
		$parent = $creer( $cat, 0, $i + 1 );
		foreach ( $cat['enfants'] ?? array() as $j => $enfant ) {
			if ( $parent ) {
				$creer( $enfant, $parent, $j + 1 );
			}
		}
	}

	foreach ( array( 'news' => 'News', 'presse' => 'Presse' ) as $slug => $nom ) {
		if ( ! get_term_by( 'slug', $slug, 'category' ) && ! is_wp_error( wp_insert_term( $nom, 'category', array( 'slug' => $slug ) ) ) ) {
			$crees[] = $nom;
		}
	}

	update_option( 'bigm_core_seeded', BIGM_CORE_VERSION, false );
	return $crees;
}

/**
 * Activation du plugin.
 */
function bigm_core_activate(): void {
	bigm_core_register_produit();
	bigm_core_seed();
	flush_rewrite_rules();
}

/**
 * Mise à jour de bigm-core (zip téléversé : l'activation ne se relance pas).
 * Une fois par version : catégories d'articles sous /actualites/ si la base est vide
 * (une seule fois, pour ne pas revenir sur un choix fait ensuite dans Réglages → Permaliens),
 * puis permaliens recalculés (adresses /carte/, /produit/, /restaurants/…).
 */
function bigm_core_mise_a_jour(): void {
	global $wp_rewrite;
	if ( '' === (string) get_option( 'category_base' ) && ! get_option( 'bigm_core_base_categories' ) ) {
		$wp_rewrite->set_category_base( 'actualites' );
	}
	update_option( 'bigm_core_base_categories', 1, false );
	flush_rewrite_rules( false );
}

add_action(
	'init',
	static function () {
		if ( get_option( 'bigm_core_version' ) !== BIGM_CORE_VERSION ) {
			bigm_core_mise_a_jour();
			update_option( 'bigm_core_version', BIGM_CORE_VERSION, false );
		}
	},
	100
);

/**
 * Règle de pagination de la page des articles quand les catégories sont rangées sous son adresse
 * (/actualites/ et /actualites/presse/) : sans elle, /actualites/page/2/ est lu comme la
 * catégorie « page/2 » (404), car les règles des catégories passent avant celles des pages.
 *
 * @return array<string, string> Règle à placer en tête (vide si la base ne vise pas la page des articles).
 */
function bigm_core_regle_pagination(): array {
	$base = trim( (string) get_option( 'category_base' ), '/' );
	$page = (int) get_option( 'page_for_posts' );
	if ( '' === $base || ! $page || get_page_uri( $page ) !== $base ) {
		return array();
	}
	return array( $base . '/page/?([0-9]{1,})/?$' => 'index.php?page_id=' . $page . '&paged=$matches[1]' );
}

add_filter(
	'category_rewrite_rules',
	static fn( array $regles ): array => bigm_core_regle_pagination() + $regles
);

/**
 * Catégories d'articles : adresses toujours à jour. La catégorie est déclarée au début de chaque
 * requête avec la base de ce moment ; quand la base vient de changer (mise à jour ci-dessus),
 * les règles recalculées dans la même requête gardent l'ancienne (/category/news/ au lieu de
 * /actualites/news/). On les recalcule donc dès que la catégorie déclarée a la bonne base
 * et que les règles enregistrées ne la connaissent pas, ou n'ont pas la pagination de la page
 * des articles.
 *
 * @return bool Règles recalculées.
 */
function bigm_core_verifier_regles(): bool {
	$base     = trim( (string) get_option( 'category_base' ), '/' );
	$regles   = get_option( 'rewrite_rules' );
	$category = get_taxonomy( 'category' );
	if ( '' === $base || ! is_array( $regles ) || ! $regles || ! $category || $base !== ( $category->rewrite['slug'] ?? '' ) ) {
		return false;
	}
	$manque = array_diff_key( bigm_core_regle_pagination(), $regles );
	if ( ! $manque && preg_grep( '#(^|/)' . preg_quote( $base, '#' ) . '/\(#', array_keys( $regles ) ) ) {
		return false;
	}
	flush_rewrite_rules( false );
	return true;
}
add_action( 'init', 'bigm_core_verifier_regles', 101 );
