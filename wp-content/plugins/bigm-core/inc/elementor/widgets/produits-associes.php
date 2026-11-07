<?php
/**
 * Widget « Big M — Ça va bien avec » : vignettes des produits associés à la fiche
 * (bloc « Ça va bien avec » du produit, .with de prototype/pages.py).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Produits associés.
 */
class Bigm_Widget_Produits_Associes extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-produits-associes';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Ça va bien avec';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-product-related';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'produit', 'associés', 'ça va bien avec' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-tuiles', 'bigm-fiche' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-tuiles-video' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Ça va bien avec' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Ça va bien avec',
				'label_block' => true,
				'description' => 'Les produits se choisissent dans la fiche du produit (bloc « Ça va bien avec »).',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$id   = $this->bigm_produit_courant();
		$html = $id ? bigm_rendu_produits_associes(
			array(
				'produit' => $id,
				'titre'   => (string) $s['titre'],
				'id'      => $this->bigm_id( 'with' ),
			)
		) : '';
		if ( '' === $html ) {
			$this->bigm_vide( $id ? 'Ce produit n’a pas de produits associés.' : 'Ce widget affiche le produit de la page : le placer dans un modèle de fiche produit (Theme Builder).' );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
