<?php
/**
 * Widget « Big M — Bandeau des allergènes » : bandeau brun du bas de la carte,
 * avec le lien vers le filtre d'une catégorie (.allergy-band de prototype/pages.py).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Bandeau des allergènes.
 */
class Bigm_Widget_Bandeau_Allergenes extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-bandeau-allergenes';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Bandeau des allergènes';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-info-box';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'allergènes', 'carte', 'bandeau' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-carte' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Bandeau' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'Vide : Réglages Big M',
				'label_block' => true,
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'       => 'Texte',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'placeholder' => 'Vide : Réglages Big M',
				'rows'        => 3,
			)
		);
		$this->add_control(
			'bouton',
			array(
				'label'   => 'Bouton',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Filtrer par allergène',
			)
		);
		$this->add_control(
			'lien',
			array(
				'label'       => 'Lien du bouton',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : filtre de la catégorie Burgers',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$titre = trim( (string) $s['titre'] );
		$texte = trim( (string) $s['texte'] );
		echo bigm_rendu_bandeau_allergenes( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
			array(
				'titre'  => '' !== $titre ? $titre : (string) bigm_reglage( 'allergenes_titre' ),
				'texte'  => '' !== $texte ? $texte : (string) bigm_reglage( 'allergenes_texte' ),
				'bouton' => (string) $s['bouton'],
				'lien'   => $this->bigm_url( $s['lien'] ),
				'id'     => $this->bigm_id( 'allerg' ),
			)
		);
	}
}
