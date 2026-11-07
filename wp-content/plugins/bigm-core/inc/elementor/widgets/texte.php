<?php
/**
 * Widget « Big M — Section texte » : titre, texte mis en forme et bouton, dans la largeur du site.
 * Pour les pages de contenu (franchise, recrutement, pages légales), avec les styles de la charte.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Section texte.
 */
class Bigm_Widget_Texte extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-texte';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Section texte';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-text';
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'texte', 'paragraphe', 'section' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-contenus' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Section' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre (h2)',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'   => 'Texte',
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			)
		);
		$this->add_control(
			'bouton',
			array(
				'label'       => 'Bouton',
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => 'Vide : pas de bouton.',
			)
		);
		$this->add_control(
			'lien',
			array(
				'label'     => 'Lien du bouton',
				'type'      => Controls_Manager::URL,
				'condition' => array( 'bouton!' => '' ),
			)
		);
		$this->add_control(
			'fond',
			array(
				'label'        => 'Sur une carte blanche',
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
			)
		);
		$this->bigm_controle_ancre( '' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$titre = trim( (string) $s['titre'] );
		$texte = trim( (string) $s['texte'] );
		if ( '' === $titre && '' === $texte ) {
			$this->bigm_vide( 'Saisir un titre ou un texte.' );
			return;
		}
		$id     = $this->bigm_id( 'texte' );
		$bouton = trim( (string) $s['bouton'] );
		?>
<section class="container section prose-section<?php echo 'yes' === $s['fond'] ? ' prose-section--carte' : ''; ?>"<?php echo $this->bigm_attr_ancre( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo '' !== $titre ? ' aria-labelledby="' . esc_attr( $id ) . '"' : ''; ?>>
  <div class="prose-section__inner">
		<?php if ( '' !== $titre ) : ?>
    <h2 id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $titre ); ?></h2>
		<?php endif; ?>
		<?php if ( '' !== $texte ) : ?>
    <div class="prose page-content"><?php echo wp_kses_post( $this->parse_text_editor( $texte ) ); ?></div>
		<?php endif; ?>
		<?php if ( '' !== $bouton ) : ?>
    <a class="btn" <?php echo $this->bigm_href( $s['lien'], '#' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $bouton ); ?></a>
		<?php endif; ?>
  </div>
</section>
		<?php
	}
}
