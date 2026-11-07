<?php
/**
 * Widget « Big M — Chiffres de la franchise » : contrat, droit d'entrée, formation, redevances,
 * tirés de Réglages Big M (section Franchise), mis en forme à la française.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Chiffres de la franchise.
 */
class Bigm_Widget_Chiffres extends Bigm_Widget {

	/** Chiffres proposés : clé de réglage => libellé. */
	private const CHIFFRES = array(
		'franchise_duree'                   => 'Contrat',
		'franchise_droit_entree'            => 'Droit d’entrée',
		'franchise_formation'               => 'Formation',
		'franchise_redevance_enseigne'      => 'Redevance d’enseigne',
		'franchise_redevance_communication' => 'Redevance communication',
	);

	/** Identifiant. */
	public function get_name() {
		return 'bigm-chiffres';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Chiffres de la franchise';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-counter';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'franchise', 'chiffres', 'redevance' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-contenus' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Chiffres' ) );
		$this->bigm_controles_entete( 'Les chiffres clés', '' );
		$this->add_control(
			'chiffres',
			array(
				'label'       => 'Chiffres affichés',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'default'     => array_keys( self::CHIFFRES ),
				'options'     => self::CHIFFRES,
				'description' => 'Les valeurs se modifient dans Réglages Big M → Franchise.',
			)
		);
		$this->add_control(
			'note',
			array(
				'label'   => 'Note sous les chiffres',
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$this->bigm_controle_ancre( 'chiffres' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = '';
		$i     = 0;
		foreach ( (array) $s['chiffres'] as $cle ) {
			$valeur = isset( self::CHIFFRES[ $cle ] ) ? bigm_reglage_texte( $cle ) : '';
			if ( '' !== $valeur ) {
				$items .= '<li class="chiffre" data-reveal style="--i:' . ( $i++ % 5 ) . '"><b>' . esc_html( $valeur ) . '</b><span>' . esc_html( self::CHIFFRES[ $cle ] ) . '</span></li>';
			}
		}
		if ( '' === $items ) {
			$this->bigm_vide( 'Aucun chiffre à afficher : les saisir dans Réglages Big M → Franchise.' );
			return;
		}
		$id   = $this->bigm_id( 'chiffres' );
		$note = trim( (string) $s['note'] );
		echo '<section class="container section chiffres-section"' . $this->bigm_attr_ancre( $s ) . ' aria-labelledby="' . esc_attr( $id ) . '">' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. $this->bigm_entete( $s, $id ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. '<ul class="chiffres">' . $items . '</ul>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			. ( '' !== $note ? '<p class="chiffres__note">' . esc_html( $note ) . '</p>' : '' )
			. '</section>';
	}
}
