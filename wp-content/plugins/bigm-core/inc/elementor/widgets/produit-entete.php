<?php
/**
 * Widget « Big M — Présentation du produit » : bandeau orange avec la photo et le lien de retour,
 * nom (h1), description et boutons Commander et Trouver un restaurant (page produit.html du prototype).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Présentation du produit.
 */
class Bigm_Widget_Produit_Entete extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-produit-entete';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Présentation du produit';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-single-product';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'produit', 'fiche', 'bandeau', 'commander' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-fiche' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Présentation' ) );
		$this->add_control(
			'retour',
			array(
				'label'        => 'Lien « Revenir à… »',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => 'Vers la catégorie du produit, avec son titre de page : « Revenir à nos burgers ».',
			)
		);
		$this->add_control(
			'commander',
			array(
				'label'       => 'Bouton Commander',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Commander',
				'description' => 'Lien du produit, sinon celui des Réglages Big M. Vide : pas de bouton.',
			)
		);
		$this->add_control(
			'restaurants',
			array(
				'label'       => 'Bouton Restaurants',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Trouver un restaurant',
				'description' => 'Vers la page Restaurants des Réglages Big M. Vide : pas de bouton.',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = $this->bigm_produit_courant();
		if ( ! $id ) {
			$this->bigm_vide( 'Ce widget affiche le produit de la page : le placer dans un modèle de fiche produit (Theme Builder).' );
			return;
		}
		echo bigm_rendu_produit_entete( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
			array(
				'produit'     => $id,
				'retour'      => 'yes' === $s['retour'],
				'commander'   => (string) $s['commander'],
				'restaurants' => (string) $s['restaurants'],
			)
		);
	}
}
