<?php
/**
 * Widget « Big M — Fiche produit » : ingrédients, allergènes (contient et traces) et valeurs
 * nutritionnelles en sections dépliables (.acc de prototype/pages.py). Une section vide est masquée.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Bloc fiche produit.
 */
class Bigm_Widget_Fiche_Produit extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-fiche-produit';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Fiche produit';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-accordion';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'produit', 'ingrédients', 'allergènes', 'nutrition' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-fiche' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Sections' ) );
		$textes = array(
			'titre_ingredients' => array( 'Titre : ingrédients', 'Ingrédients' ),
			'titre_allergenes'  => array( 'Titre : allergènes', 'Allergènes' ),
			'contient'          => array( 'Allergènes contenus', 'Contient' ),
			'traces'            => array( 'Traces', 'Peut contenir des traces de' ),
			'titre_nutrition'   => array( 'Titre : valeurs nutritionnelles', 'Valeurs nutritionnelles' ),
		);
		foreach ( $textes as $cle => $t ) {
			$this->add_control(
				$cle,
				array(
					'label'       => $t[0],
					'type'        => Controls_Manager::TEXT,
					'default'     => $t[1],
					'label_block' => true,
				)
			);
		}
		$this->add_control(
			'note',
			array(
				'label'       => 'Note sous les allergènes',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => '',
				'placeholder' => 'Vide : Réglages Big M (La carte)',
				'rows'        => 3,
			)
		);
		$this->add_control(
			'ouvertes',
			array(
				'label'    => 'Ouvertes à l’arrivée',
				'type'     => Controls_Manager::SELECT2,
				'multiple' => true,
				'default'  => array( 'ingredients', 'allergenes' ),
				'options'  => array(
					'ingredients' => 'Ingrédients',
					'allergenes'  => 'Allergènes',
					'nutrition'   => 'Valeurs nutritionnelles',
				),
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
		$note = trim( (string) $s['note'] );
		$html = bigm_rendu_fiche_produit(
			array(
				'produit'           => $id,
				'titre_ingredients' => (string) $s['titre_ingredients'],
				'titre_allergenes'  => (string) $s['titre_allergenes'],
				'titre_nutrition'   => (string) $s['titre_nutrition'],
				'contient'          => (string) $s['contient'],
				'traces'            => (string) $s['traces'],
				'note'              => '' !== $note ? $note : (string) bigm_reglage( 'allergenes_note' ),
				'ouvertes'          => array_values( (array) $s['ouvertes'] ),
			)
		);
		if ( '' === $html ) {
			$this->bigm_vide( 'Ce produit n’a encore ni ingrédients, ni allergènes, ni valeurs nutritionnelles.' );
			return;
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	}
}
