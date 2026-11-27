<?php
/**
 * Widget « Big M — Barre de catégories » : capsule collante sous l'en-tête, avec les icônes
 * des catégories (tabs() dans prototype/build.py). La catégorie de la page est allumée.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Barre de catégories.
 */
class Bigm_Widget_Barre_Categories extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-barre-categories';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Barre de catégories';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-tabs';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'catégories', 'carte', 'onglets', 'menu' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-barre-categories' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Barre de catégories' ) );
		$this->add_control(
			'toute_la_carte',
			array(
				'label'   => 'Premier lien',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Toute la carte',
			)
		);
		$this->add_control(
			'masquer_vides',
			array(
				'label'        => 'Masquer les catégories sans produit',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);
		$this->add_control(
			'aide',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Les catégories, leur ordre et leurs icônes se gèrent dans Produits → Catégories. La barre colle sous l’en-tête tant que son conteneur est à l’écran : la placer dans le même conteneur que le reste de la page.',
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s = $this->get_settings_for_display();
		echo bigm_rendu_barre_categories( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
			array(
				'toute_la_carte' => (string) $s['toute_la_carte'],
				'masquer_vides'  => 'yes' === $s['masquer_vides'],
			)
		);
	}
}
