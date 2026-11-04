<?php
/**
 * Widget « Big M Slider » : hero (2:1) ou bannière (3:1, 2:1 sur mobile), photo ou vidéo.
 * Balisage de prototype/pages.py (fonctions hs et hs_slide), comportement dans assets/js/slider.js.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Big M Slider.
 */
class Bigm_Widget_Slider extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-slider';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M Slider';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'slider', 'bannière', 'carrousel', 'hero' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-slider' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-slider' );
	}

	/** Réglages. */
	protected function register_controls() {
		$sliders = array( 0 => '— Choisir —' );
		foreach ( bigm_sliders_liste() as $s ) {
			$sliders[ $s->ID ] = get_the_title( $s );
		}

		$this->start_controls_section( 'contenu', array( 'label' => 'Slider' ) );
		$this->add_control(
			'source',
			array(
				'label'   => 'Slides',
				'type'    => Controls_Manager::SELECT,
				'default' => 'choisi',
				'options' => array(
					'choisi'    => 'Un slider du menu Sliders',
					'categorie' => 'Bannières de la page affichée (carte ou catégorie)',
				),
			)
		);
		$this->add_control(
			'slider',
			array(
				'label'       => 'Slider',
				'type'        => Controls_Manager::SELECT,
				'default'     => '0',
				'options'     => $sliders,
				'condition'   => array( 'source' => 'choisi' ),
				'description' => 'Les slides, leurs dates et leurs liens se gèrent dans le menu Sliders.',
			)
		);
		$this->add_control(
			'aide_page',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => 'Sur une catégorie : son slider « Bannières » (Produits → Catégories), sinon celui de sa catégorie parente. Sur /carte/ : « Bannières de la carte » dans Réglages Big M.',
				'content_classes' => 'elementor-descriptor',
				'condition'       => array( 'source' => 'categorie' ),
			)
		);
		$this->add_control(
			'format',
			array(
				'label'       => 'Format',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'hero',
				'options'     => array(
					'hero'     => 'Hero 2:1 (accueil), photo 6 s',
					'banniere' => 'Bannière 3:1, 2:1 sur mobile, photo 5 s',
				),
				'description' => 'Une vidéo passe à la slide suivante quand elle se termine.',
			)
		);
		$this->add_control(
			'libelle',
			array(
				'label'       => 'Nom du carrousel',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'L’actualité Big M',
				'description' => 'Lu par les lecteurs d’écran.',
			)
		);
		$this->add_control(
			'marge',
			array(
				'label'        => 'Espace sous les bannières',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'description'  => 'À désactiver au-dessus des filtres d’une catégorie, comme dans le prototype.',
				'condition'    => array( 'format' => 'banniere' ),
			)
		);
		$this->add_control(
			'titre_cache',
			array(
				'label'       => 'Titre caché de la page (h1)',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => 'Pour l’accueil : un titre h1 invisible, lu par les lecteurs d’écran et les moteurs de recherche.',
				'condition'   => array( 'format' => 'hero' ),
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s         = $this->get_settings_for_display();
		$slider_id = 'categorie' === $s['source'] ? bigm_slider_de_la_page() : (int) $s['slider'];
		$html      = bigm_rendu_slider(
			array(
				'slider_id'   => $slider_id,
				'format'      => $s['format'],
				'libelle'     => (string) $s['libelle'],
				'titre_cache' => (string) $s['titre_cache'],
				'id'          => $this->bigm_id( 'slider' ),
				'marge'       => 'yes' === ( $s['marge'] ?? 'yes' ),
			)
		);
		if ( '' === $html ) {
			$this->bigm_vide( $slider_id ? 'Aucune slide visible en ce moment dans ce slider (dates de début et de fin).' : 'Choisir un slider, ou une page (carte, catégorie) qui a des bannières.' );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
