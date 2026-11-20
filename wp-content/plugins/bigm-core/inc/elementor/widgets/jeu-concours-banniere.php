<?php
/**
 * Widget « Big M — Bannière jeu concours » : bande du jeu en cours (vidéo ou affiche, compte à
 * rebours, boutons), par exemple sur l'accueil. Rien quand aucun jeu n'est en cours : le widget
 * peut rester en place d'un jeu à l'autre.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Bannière jeu concours.
 */
class Bigm_Widget_Jeu_Concours_Banniere extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-jeu-concours-banniere';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Bannière jeu concours';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-banner';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'jeu', 'concours', 'bannière', 'gagner' );
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
		$this->start_controls_section( 'contenu', array( 'label' => 'Bannière jeu concours' ) );
		$this->add_control(
			'jeu',
			array(
				'label'       => 'Jeu',
				'type'        => Controls_Manager::SELECT,
				'options'     => $jeux,
				'default'     => '0',
				'description' => 'Rien ne s’affiche quand aucun jeu n’est en cours : le widget peut rester sur la page.',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$html = bigm_rendu_concours_banniere( (int) $s['jeu'] );
		if ( '' === $html ) {
			$this->bigm_vide( 'Aucun jeu concours en cours : la bannière apparaîtra pendant le prochain jeu (menu « Jeux concours »).' );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_rendu_concours_bande().
	}
}
