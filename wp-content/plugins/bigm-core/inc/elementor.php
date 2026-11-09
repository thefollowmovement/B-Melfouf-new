<?php
/**
 * Widgets Elementor de Big M : chargement, catégorie, styles et scripts.
 *
 * Chaque widget déclare ses styles et scripts (get_style_depends / get_script_depends) :
 * Elementor ne les charge que sur les pages où le widget est utilisé.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/*
 * Styles et scripts des composants, déclarés sans être chargés.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$v = static function ( string $rel ): string {
			$path = BIGM_CORE_DIR . '/assets/' . $rel;
			return is_file( $path ) ? (string) filemtime( $path ) : BIGM_CORE_VERSION;
		};
		foreach ( array( 'slider', 'tuiles', 'accueil', 'barre-categories', 'carte', 'produits', 'fiche', 'articles', 'contenus', 'formulaires' ) as $nom ) {
			wp_register_style( "bigm-$nom", BIGM_CORE_URL . "assets/css/$nom.css", array(), $v( "css/$nom.css" ) );
		}
		foreach ( array( 'slider', 'carrousel', 'tuiles-video', 'filtres', 'formulaires' ) as $nom ) {
			wp_register_script(
				"bigm-$nom",
				BIGM_CORE_URL . "assets/js/$nom.js",
				array(),
				$v( "js/$nom.js" ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	},
	5
);

add_action(
	'elementor/elements/categories_registered',
	static function ( $manager ) {
		$manager->add_category(
			'bigm',
			array(
				'title' => 'Big M',
				'icon'  => 'eicon-star',
			)
		);
	}
);

add_action(
	'elementor/widgets/register',
	static function ( $widgets ) {
		require_once BIGM_CORE_DIR . '/inc/elementor/class-bigm-widget.php';
		$classes = array(
			'slider'             => 'Bigm_Widget_Slider',
			'bande-appli'        => 'Bigm_Widget_Bande_Appli',
			'restaurants'        => 'Bigm_Widget_Restaurants',
			'services'           => 'Bigm_Widget_Services',
			'classiques'         => 'Bigm_Widget_Classiques',
			'affiches'           => 'Bigm_Widget_Affiches',
			'actualites'         => 'Bigm_Widget_Actualites',
			'rejoindre'          => 'Bigm_Widget_Rejoindre',
			'barre-categories'   => 'Bigm_Widget_Barre_Categories',
			'entete-carte'       => 'Bigm_Widget_Entete_Carte',
			'grille-categories'  => 'Bigm_Widget_Grille_Categories',
			'bandeau-allergenes' => 'Bigm_Widget_Bandeau_Allergenes',
			'grille-produits'    => 'Bigm_Widget_Grille_Produits',
			'produit-entete'     => 'Bigm_Widget_Produit_Entete',
			'fiche-produit'      => 'Bigm_Widget_Fiche_Produit',
			'produits-associes'  => 'Bigm_Widget_Produits_Associes',
			'texte'              => 'Bigm_Widget_Texte',
			'chiffres'           => 'Bigm_Widget_Chiffres',
			'jeu-concours'       => 'Bigm_Widget_Jeu_Concours',
		);
		foreach ( $classes as $fichier => $classe ) {
			require_once BIGM_CORE_DIR . "/inc/elementor/widgets/$fichier.php";
			$widgets->register( new $classe() );
		}
	}
);
