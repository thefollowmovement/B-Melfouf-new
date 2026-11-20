<?php
/**
 * Widget « Big M — Grille de catégories » : les vignettes de la carte (cat_tile() dans
 * prototype/pages.py), avec la vidéo de fond de chaque catégorie quand elle en a une.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Grille de catégories.
 */
class Bigm_Widget_Grille_Categories extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-grille-categories';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Grille de catégories';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'catégories', 'carte', 'grille', 'vignettes' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-tuiles', 'bigm-carte' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-tuiles-video' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Catégories' ) );
		$this->add_control(
			'source',
			array(
				'label'   => 'Vignettes',
				'type'    => Controls_Manager::SELECT,
				'default' => 'toutes',
				'options' => array(
					'toutes'   => 'Toutes les catégories (premier niveau)',
					'choisies' => 'Choisies une par une',
				),
			)
		);
		$options = $this->bigm_options_categories();
		$item    = new Repeater();
		$item->add_control(
			'categorie',
			array(
				'label'   => 'Catégorie',
				'type'    => Controls_Manager::SELECT,
				'default' => '0',
				'options' => $options,
			)
		);
		$item->add_control(
			'libelle',
			array(
				'label'       => 'Libellé',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'Vide : nom de la catégorie',
			)
		);
		$this->add_control(
			'tuiles',
			array(
				'label'       => 'Vignettes, dans l’ordre',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $item->get_controls(),
				'default'     => array(),
				'title_field' => '{{{ libelle || (' . wp_json_encode( array_map( 'html_entity_decode', array_slice( $options, 1, null, true ) ) ) . ')[ categorie ] || "Catégorie" }}}',
				'condition'   => array( 'source' => 'choisies' ),
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
				'raw'             => 'L’image et la vidéo de chaque vignette se règlent dans Produits → Catégories. Sans image, la photo du premier produit est utilisée.',
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$termes = array();
		if ( 'choisies' === $s['source'] ) {
			foreach ( (array) $s['tuiles'] as $t ) {
				if ( (int) ( $t['categorie'] ?? 0 ) ) {
					$termes[] = array(
						'id'      => (int) $t['categorie'],
						'libelle' => (string) ( $t['libelle'] ?? '' ),
					);
				}
			}
			if ( ! $termes ) {
				$this->bigm_vide( 'Ajouter des vignettes et choisir leur catégorie.' );
				return;
			}
		}
		$html = bigm_rendu_grille_categories(
			array(
				'termes'        => $termes,
				'masquer_vides' => 'yes' === $s['masquer_vides'],
			)
		);
		if ( '' === $html ) {
			$this->bigm_vide( 'Aucune catégorie avec des produits publiés.' );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
