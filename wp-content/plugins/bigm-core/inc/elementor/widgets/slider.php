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
					'categorie' => 'Bannières de la catégorie affichée',
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
		$slider_id = 0;
		if ( 'categorie' === $s['source'] ) {
			$term      = get_queried_object();
			$cat       = $term instanceof WP_Term && 'bigm_categorie' === $term->taxonomy ? bigm_categorie( $term ) : null;
			$slider_id = $cat ? (int) $cat['slider_id'] : 0;
		} else {
			$slider_id = (int) $s['slider'];
		}
		$slides = $slider_id ? bigm_slides_actives( $slider_id ) : array();
		if ( ! $slides ) {
			$this->bigm_vide( $slider_id ? 'Aucune slide visible en ce moment dans ce slider (dates de début et de fin).' : 'Choisir un slider, ou une catégorie qui a des bannières.' );
			return;
		}

		$hero   = 'banniere' !== $s['format'];
		$sizes  = $hero ? '(max-width: 760px) 88vw, min(1040px, 84vw)' : '(max-width: 760px) 90vw, min(1176px, 90vw)';
		$track  = '';
		foreach ( $slides as $i => $slide ) {
			$track .= '<div class="hs__slide"><a' . ( $slide['lien'] ? ' href="' . esc_url( $slide['lien'] ) . '"' : '' ) . ' draggable="false">' . $this->media( $slide, 0 === $i, $sizes ) . '</a></div>';
		}
		$titre = $hero ? trim( (string) $s['titre_cache'] ) : '';
		$id    = $this->bigm_id( 'slider' );

		$html = '<div class="hs' . ( $hero ? '' : ' hs--banner' ) . '" data-hs data-interval="' . ( $hero ? 6000 : 5000 ) . '" aria-roledescription="carrousel" aria-label="' . esc_attr( (string) $s['libelle'] ) . '">'
			. '<div class="hs__viewport"><div class="hs__track">' . $track . '</div>'
			. '<button class="hs__arrow hs__arrow--prev" type="button" aria-label="Diapositive précédente">' . bigm_core_icone( 'left' ) . '</button>'
			. '<button class="hs__arrow hs__arrow--next" type="button" aria-label="Diapositive suivante">' . bigm_core_icone( 'right' ) . '</button></div>'
			. '<div class="hs__bar"><div class="hs__dots"></div><button class="hs__pause" type="button"></button></div></div>';

		if ( $hero ) {
			$html = '<section class="hs-section"' . ( $titre ? ' aria-labelledby="' . esc_attr( $id ) . '"><h1 class="sr-only" id="' . esc_attr( $id ) . '">' . esc_html( $titre ) . '</h1>' : '>' ) . $html . '</section>';
		} else {
			$html = '<section class="hs-banners" aria-label="' . esc_attr( (string) $s['libelle'] ) . '">' . $html . '</section>';
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}

	/**
	 * Média d'une slide : image (avec image mobile) ou vidéo chargée par le script.
	 *
	 * @param array<string, mixed> $slide   Slide de bigm_slides_actives().
	 * @param bool                 $premier Première slide : chargée tout de suite (LCP).
	 * @param string               $sizes   Attribut sizes.
	 */
	private function media( array $slide, bool $premier, string $sizes ): string {
		$style = 'object-position:' . $slide['cadrage'];
		if ( 'video' === $slide['type'] ) {
			$src = $slide['mp4'] ? $slide['mp4'] : $slide['webm'];
			return '<video class="hs__media" data-src="' . esc_url( $src ) . '"' . ( $slide['mp4'] && $slide['webm'] ? ' data-src-webm="' . esc_url( $slide['webm'] ) . '"' : '' )
				. ( $slide['poster'] ? ' poster="' . esc_url( $slide['poster'] ) . '"' : '' )
				. ' muted playsinline preload="none" aria-label="' . esc_attr( $slide['alt'] ) . '" style="' . esc_attr( $style ) . '"></video>';
		}
		$img = wp_get_attachment_image(
			(int) $slide['image_id'],
			'full',
			false,
			array(
				'class'         => 'hs__media',
				'alt'           => $slide['alt'],
				'style'         => $style,
				'draggable'     => 'false',
				'sizes'         => $sizes,
			) + ( $premier ? array(
				// Première slide : chargée tout de suite et en priorité (LCP). Sans attribut
				// loading, WordPress n'ajoute pas de « lazy » à une image en fetchpriority high.
				'loading'       => false,
				'fetchpriority' => 'high',
			) : array() )
		);
		if ( $slide['image_mobile_id'] && $slide['image_mobile_id'] !== $slide['image_id'] ) {
			$srcset = wp_get_attachment_image_srcset( (int) $slide['image_mobile_id'], 'full' );
			$srcset = $srcset ? $srcset : (string) wp_get_attachment_image_url( (int) $slide['image_mobile_id'], 'full' );
			$img    = '<picture><source media="(max-width: 760px)" srcset="' . esc_attr( $srcset ) . '" sizes="90vw">' . $img . '</picture>';
		}
		return $img;
	}
}
