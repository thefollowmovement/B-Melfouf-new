<?php
/**
 * Widgets Elementor « Big M — En-tête » et « Big M — Pied de page ».
 *
 * Même rendu que celui du thème, pour un modèle du Theme Builder d'Elementor Pro.
 * Sans réglage : les liens viennent d'Apparence → Menus et de Réglages Big M.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * En-tête en capsule.
 */
class Bigm_Child_Widget_Entete extends \Elementor\Widget_Base {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-entete';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — En-tête';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-header';
	}

	/** Catégorie du panneau. */
	public function get_categories() {
		return array( 'bigm' );
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'en-tête', 'header', 'menu', 'navigation' );
	}

	/** Une explication à la place des réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'infos', array( 'label' => 'Big M — En-tête' ) );
		$this->add_control(
			'aide',
			array(
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => 'Liens : Apparence → Menus (« En-tête »). Lien Commander et page Restaurants : Réglages Big M. Logo : Apparence → Personnaliser → Identité du site.',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		echo bigm_child_entete_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Pied de page brun.
 */
class Bigm_Child_Widget_Pied extends \Elementor\Widget_Base {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-pied';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Pied de page';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-footer';
	}

	/** Catégorie du panneau. */
	public function get_categories() {
		return array( 'bigm' );
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'pied de page', 'footer' );
	}

	/** Une explication à la place des réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'infos', array( 'label' => 'Big M — Pied de page' ) );
		$this->add_control(
			'aide',
			array(
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw'  => 'Colonnes : Apparence → Menus (« Pied de page »). Réseaux sociaux, applis, contact et mention : Réglages Big M. La mention mangerbouger.fr est obligatoire et fixe.',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		echo bigm_child_pied_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
