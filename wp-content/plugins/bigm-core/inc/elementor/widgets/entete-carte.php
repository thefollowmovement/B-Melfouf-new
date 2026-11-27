<?php
/**
 * Widget « Big M — En-tête de page » : titre h1, texte et bouton Commander de /carte/ (ou d'une autre page),
 * ou titre et texte de la catégorie affichée (.carte-head de prototype/pages.py).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * En-tête de la carte ou d'une catégorie.
 */
class Bigm_Widget_Entete_Carte extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-entete-carte';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — En-tête de page';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-heading';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'carte', 'catégorie', 'titre' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-carte' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'En-tête' ) );
		$this->add_control(
			'source',
			array(
				'label'       => 'Contenu',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'page',
				'options'     => array(
					'page'   => 'Selon la page',
					'manuel' => 'Saisi ici',
				),
				'description' => 'Selon la page : sur /carte/, le titre et le texte des Réglages Big M (La carte) ; sur une catégorie, son titre de page et sa description (Produits → Catégories), sans bouton.',
			)
		);
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre (h1)',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Notre carte',
				'label_block' => true,
				'condition'   => array( 'source' => 'manuel' ),
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'     => 'Texte',
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => '',
				'rows'      => 3,
				'condition' => array( 'source' => 'manuel' ),
			)
		);
		$this->add_control(
			'bouton',
			array(
				'label'       => 'Bouton',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Commander',
				'description' => 'Vide : pas de bouton.',
			)
		);
		$this->add_control(
			'lien',
			array(
				'label'       => 'Lien du bouton',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : lien Commander des Réglages Big M',
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$args = array(
			'titre'  => (string) $s['titre'],
			'texte'  => (string) $s['texte'],
			'bouton' => (string) $s['bouton'],
			'lien'   => $this->bigm_url( $s['lien'] ),
		);
		if ( 'manuel' !== $s['source'] ) {
			$term = bigm_categorie_affichee();
			if ( $term ) {
				$c    = bigm_categorie( $term );
				$args = array(
					'titre'    => $c['titre'],
					'texte'    => wp_strip_all_tags( $c['description'] ),
					'variante' => 'categorie',
				);
			} else {
				$args['titre'] = (string) bigm_reglage( 'carte_titre' );
				$args['texte'] = (string) bigm_reglage( 'carte_texte' );
			}
		}
		echo bigm_rendu_entete_carte( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
