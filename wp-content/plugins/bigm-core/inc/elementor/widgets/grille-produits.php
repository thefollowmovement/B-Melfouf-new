<?php
/**
 * Widget « Big M — Grille de produits » : produits d'une catégorie regroupés par sous-catégorie,
 * filtres par viande et par allergène, message quand rien ne correspond (page categorie.html
 * du prototype). Le filtrage se fait dans le navigateur (assets/js/filtres.js).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Grille de produits.
 */
class Bigm_Widget_Grille_Produits extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-grille-produits';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Grille de produits';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-products';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'produits', 'catégorie', 'allergènes', 'filtres' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-tuiles', 'bigm-produits' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-tuiles-video', 'bigm-filtres' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Produits' ) );
		$this->add_control(
			'source',
			array(
				'label'   => 'Catégorie',
				'type'    => Controls_Manager::SELECT,
				'default' => 'page',
				'options' => array(
					'page'    => 'Celle de la page affichée',
					'choisie' => 'Choisie ici',
				),
			)
		);
		$this->add_control(
			'categorie',
			array(
				'label'     => 'Catégorie',
				'type'      => Controls_Manager::SELECT,
				'default'   => '0',
				'options'   => $this->bigm_options_categories(),
				'condition' => array( 'source' => 'choisie' ),
			)
		);
		$this->add_control(
			'grouper',
			array(
				'label'        => 'Regrouper par sous-catégorie',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);
		$this->add_control(
			'filtres',
			array(
				'label'        => 'Filtres viande et allergènes',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => 'Les boutons de viande n’apparaissent que si la catégorie en compte au moins deux (champ « Viande » des produits).',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'textes',
			array(
				'label'     => 'Textes des filtres',
				'condition' => array( 'filtres' => 'yes' ),
			)
		);
		$textes = array(
			'filtre_libelle' => array( 'Bouton des allergènes', 'Filtrer par allergène' ),
			'panneau_titre'  => array( 'Titre du panneau', "Masquer les produits qui contiennent\u{202F}:" ),
			'panneau_note'   => array( 'Note du panneau', 'Les traces éventuelles sont indiquées sur chaque fiche produit.' ),
			'vide_titre'     => array( 'Aucun résultat : titre', 'Aucun produit ne correspond' ),
			'vide_texte'     => array( 'Aucun résultat : texte', 'Retirez un allergène ou changez de filtre.' ),
			'vide_bouton'    => array( 'Aucun résultat : bouton', 'Réinitialiser les filtres' ),
		);
		foreach ( $textes as $cle => $t ) {
			$this->add_control(
				$cle,
				array(
					'label'       => $t[0],
					'type'        => Controls_Manager::TEXT,
					'default'     => $t[1],
					'label_block' => true,
				)
			);
		}
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$term = 'choisie' === $s['source'] ? get_term( (int) $s['categorie'], 'bigm_categorie' ) : $this->bigm_categorie_courante();
		if ( ! $term instanceof WP_Term ) {
			$this->bigm_vide( 'choisie' === $s['source'] ? 'Choisir une catégorie.' : 'Ce widget affiche les produits de la catégorie de la page : le placer dans un modèle d’archive des catégories (Theme Builder).' );
			return;
		}
		$args = array(
			'terme'   => $term,
			'grouper' => 'yes' === $s['grouper'],
			'filtres' => 'yes' === $s['filtres'],
		);
		foreach ( array( 'filtre_libelle', 'panneau_titre', 'panneau_note', 'vide_titre', 'vide_texte', 'vide_bouton' ) as $cle ) {
			if ( isset( $s[ $cle ] ) ) {
				$args[ $cle ] = (string) $s[ $cle ];
			}
		}
		echo bigm_rendu_grille_produits( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
