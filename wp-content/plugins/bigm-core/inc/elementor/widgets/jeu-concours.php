<?php
/**
 * Widget « Big M — Jeu concours » : le jeu en cours (menu Jeux concours), avec son compte à
 * rebours, sa vidéo, ses étapes, ses lots, les restaurants participants, les questions et le
 * règlement. Sans jeu à montrer : un titre et un texte d'attente.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Jeu concours.
 */
class Bigm_Widget_Jeu_Concours extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-jeu-concours';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Jeu concours';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-countdown';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'jeu', 'concours', 'gagner', 'lots' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-concours' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-concours' );
	}

	/** Réglages. */
	protected function register_controls() {
		$jeux = array( '0' => 'Automatique : le jeu en cours' );
		foreach ( get_posts(
			array(
				'post_type'      => 'bigm_concours',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
			)
		) as $p ) {
			$jeux[ (string) $p->ID ] = get_the_title( $p );
		}
		$this->start_controls_section( 'contenu', array( 'label' => 'Jeu concours' ) );
		$this->add_control(
			'jeu',
			array(
				'label'       => 'Jeu',
				'type'        => Controls_Manager::SELECT,
				'options'     => $jeux,
				'default'     => '0',
				'description' => 'Les jeux se créent dans le menu « Jeux concours ». Automatique : le jeu en cours, sinon le prochain, sinon le dernier terminé depuis moins de 30 jours.',
			)
		);
		$this->add_control(
			'vide_titre',
			array(
				'label'       => 'Titre sans jeu',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Jeu concours',
				'label_block' => true,
			)
		);
		$this->add_control(
			'vide_texte',
			array(
				'label'   => 'Texte sans jeu',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Pas de jeu concours en ce moment. Les prochains seront annoncés ici et sur nos réseaux sociaux.',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s = $this->get_settings_for_display();
		echo bigm_rendu_page_concours( (int) $s['jeu'], (string) $s['vide_titre'], (string) $s['vide_texte'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_rendu_page_concours().
	}
}
