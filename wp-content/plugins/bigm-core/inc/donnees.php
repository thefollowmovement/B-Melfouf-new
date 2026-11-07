<?php
/**
 * Listes de référence : allergènes, viandes, valeurs nutritionnelles, catégories par défaut.
 *
 * Les identifiants (gluten, lait, boeuf…) sont ceux du prototype : ils servent
 * d'attributs data-* au filtre de la catégorie, côté navigateur.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Les 14 allergènes à déclaration obligatoire (règlement UE 1169/2011, annexe II),
 * dans l'ordre des filtres du prototype.
 *
 * @return array<string, array{label: string, detail: string}>
 */
function bigm_allergenes(): array {
	$allergenes = array(
		'gluten'       => array( 'Gluten', 'Céréales contenant du gluten : blé, seigle, orge, avoine, épeautre, kamut' ),
		'lait'         => array( 'Lait', 'Lait et produits à base de lait, y compris le lactose' ),
		'oeufs'        => array( 'Œufs', 'Œufs et produits à base d’œufs' ),
		'moutarde'     => array( 'Moutarde', 'Moutarde et produits à base de moutarde' ),
		'sesame'       => array( 'Sésame', 'Graines de sésame et produits à base de sésame' ),
		'soja'         => array( 'Soja', 'Soja et produits à base de soja' ),
		'celeri'       => array( 'Céleri', 'Céleri et produits à base de céleri' ),
		'poissons'     => array( 'Poissons', 'Poissons et produits à base de poissons' ),
		'crustaces'    => array( 'Crustacés', 'Crustacés et produits à base de crustacés' ),
		'mollusques'   => array( 'Mollusques', 'Mollusques et produits à base de mollusques' ),
		'arachides'    => array( 'Arachides', 'Arachides et produits à base d’arachides' ),
		'fruitsacoque' => array( 'Fruits à coque', 'Amandes, noisettes, noix, noix de cajou, de pécan, du Brésil, pistaches, noix de macadamia' ),
		'sulfites'     => array( 'Sulfites', 'Anhydride sulfureux et sulfites au-delà de 10 mg/kg ou 10 mg/l' ),
		'lupin'        => array( 'Lupin', 'Lupin et produits à base de lupin' ),
	);

	return array_map(
		static fn( array $a ): array => array(
			'label'  => $a[0],
			'detail' => $a[1],
		),
		$allergenes
	);
}

/**
 * Viandes proposées dans le filtre de la catégorie.
 *
 * @return array<string, string>
 */
function bigm_viandes(): array {
	return (array) apply_filters(
		'bigm_viandes',
		array(
			'boeuf'      => 'Bœuf',
			'poulet'     => 'Poulet',
			'poisson'    => 'Poisson',
			'vegetarien' => 'Végétarien',
		)
	);
}

/**
 * Lignes du tableau des valeurs nutritionnelles, dans l'ordre de la fiche produit.
 *
 * @return array<string, string>
 */
function bigm_nutrition_lignes(): array {
	return array(
		'kj'        => 'Énergie (kJ)',
		'kcal'      => 'Énergie (kcal)',
		'lipides'   => 'Matières grasses (g)',
		'satures'   => 'dont saturées (g)',
		'glucides'  => 'Glucides (g)',
		'sucres'    => 'dont sucres (g)',
		'proteines' => 'Protéines (g)',
		'sel'       => 'Sel (g)',
	);
}

/**
 * Icône SVG au format du prototype (trait de 1,9, couleur du texte).
 *
 * @param string $d Tracé (attribut d).
 */
function bigm_icone_depuis_trace( string $d ): string {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $d ) . '"/></svg>';
}

/**
 * Catégories créées à l'activation, avec les icônes du prototype.
 * Burgers a deux sous-catégories, Bœuf et Poulet.
 *
 * @return array<int, array{nom: string, slug: string, titre: string, trace: string, enfants?: array}>
 */
function bigm_categories_par_defaut(): array {
	return array(
		array(
			'nom'   => 'Nouveautés',
			'slug'  => 'nouveautes',
			'titre' => 'Nos nouveautés',
			'trace' => 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z',
		),
		array(
			'nom'   => 'Menus',
			'slug'  => 'menus',
			'titre' => 'Nos menus',
			'trace' => 'M5 8h14l-1.2 12H6.2L5 8z M9 8V6a3 3 0 0 1 6 0v2',
		),
		array(
			'nom'     => 'Burgers',
			'slug'    => 'burgers',
			'titre'   => 'Nos burgers',
			'trace'   => 'M4 10a8 6 0 0 1 16 0z M3 13.5h18 M4.5 17h15v.5a2.5 2.5 0 0 1-2.5 2.5H7a2.5 2.5 0 0 1-2.5-2.5z',
			'enfants' => array(
				array(
					'nom'  => 'Bœuf',
					'slug' => 'boeuf',
				),
				array(
					'nom'  => 'Poulet',
					'slug' => 'burgers-poulet',
				),
			),
		),
		array(
			'nom'   => 'Poulet',
			'slug'  => 'poulet',
			'titre' => 'Notre poulet',
			'trace' => 'M15 3.5a5.5 5.5 0 0 1 3.9 9.4l-2.4 2.4-4.6-4.6.2-.3A5.5 5.5 0 0 1 15 3.5z M11.9 10.7 6.5 16.1 M5 15a2 2 0 1 0 2.5 2.5 2 2 0 1 0 1.5-2.6',
		),
		array(
			'nom'   => 'Tex-Mex',
			'slug'  => 'tex-mex',
			'titre' => 'Notre Tex-Mex',
			'trace' => 'M12 3c1 3 5 4.5 5 9.5a5 5 0 0 1-10 0c0-2.3 1.2-3.7 2.4-4.8 0 2 .9 3.3 2.1 3.3 0-3.3-.9-5.4.5-8z',
		),
		array(
			'nom'   => 'Petite faim',
			'slug'  => 'petite-faim',
			'titre' => 'Petite faim',
			'trace' => 'M6 10h12l-1.5 10h-9z M8 10V4 M11 10V3 M14 10V4 M16.5 10V5.5',
		),
		array(
			'nom'   => 'Desserts',
			'slug'  => 'desserts',
			'titre' => 'Nos desserts',
			'trace' => 'M7.5 10a4.5 4.5 0 0 1 9 0z M7.5 10 12 21l4.5-11',
		),
		array(
			'nom'   => 'Boissons',
			'slug'  => 'boissons',
			'titre' => 'Nos boissons',
			'trace' => 'M7 7h10l-1.2 13H8.2z M6 7h12 M12 7l2-4h3',
		),
	);
}
