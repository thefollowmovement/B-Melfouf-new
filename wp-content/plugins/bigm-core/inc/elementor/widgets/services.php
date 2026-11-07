<?php
/**
 * Widget « Big M — Services » : cartes photo, titre, texte et lien (sur place, click & collect, livraison).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Services.
 */
class Bigm_Widget_Services extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-services';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Services';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Services' ) );
		$this->bigm_controles_entete( 'Choisissez le service qui vous ressemble' );

		$r = new Repeater();
		$r->add_control(
			'image',
			array(
				'label' => 'Photo',
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$r->add_control(
			'cadrage',
			array(
				'label'       => 'Cadrage',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => '50% 50%',
				'description' => 'Point de la photo gardé visible : horizontal puis vertical.',
			)
		);
		$r->add_control(
			'titre',
			array(
				'label' => 'Titre',
				'type'  => Controls_Manager::TEXT,
			)
		);
		$r->add_control(
			'texte',
			array(
				'label' => 'Texte',
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 2,
			)
		);
		$r->add_control(
			'lien_texte',
			array(
				'label' => 'Texte du lien',
				'type'  => Controls_Manager::TEXT,
			)
		);
		$r->add_control(
			'lien',
			array(
				'label' => 'Lien',
				'type'  => Controls_Manager::URL,
			)
		);
		$this->add_control(
			'cartes',
			array(
				'label'       => 'Cartes',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ titre }}}',
				'default'     => array(
					array(
						'titre'      => 'Sur place',
						'texte'      => 'Installez-vous dans le Big M le plus proche.',
						'lien_texte' => 'Trouver un restaurant',
						'lien'       => array( 'url' => '/restaurants/' ),
						'cadrage'    => '30% 50%',
					),
					array(
						'titre'      => 'Click & collect',
						'texte'      => 'Commandez sur l\'appli, récupérez votre menu sans attendre.',
						'lien_texte' => 'Commander',
						'lien'       => array( 'url' => '/#appli' ),
					),
					array(
						'titre'      => 'Livraison',
						'texte'      => 'Vos burgers livrés chez vous, avec les offres de l\'appli.',
						'lien_texte' => 'Se faire livrer',
						'lien'       => array( 'url' => '/#appli' ),
					),
				),
			)
		);
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = $this->bigm_id( 'services' );
		?>
<section class="container section" aria-labelledby="<?php echo esc_attr( $id ); ?>">
		<?php echo $this->bigm_entete( $s, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <div class="services">
		<?php
		foreach ( (array) $s['cartes'] as $i => $c ) :
			$cadrage = preg_match( '/^\d{1,3}% \d{1,3}%$/', trim( (string) ( $c['cadrage'] ?? '' ) ) ) ? 'object-position: ' . trim( (string) $c['cadrage'] ) : '';
			$attr    = array(
				'alt'   => '',
				'sizes' => '(max-width: 960px) 100vw, 400px',
			);
			if ( $cadrage ) {
				$attr['style'] = $cadrage;
			}
			$img = $this->bigm_image( $c['image'] ?? array(), $attr );
			?>
    <a class="service" <?php echo $this->bigm_href( $c['lien'] ?? array(), '/' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-reveal<?php echo $i ? ' style="--i:' . (int) $i . '"' : ''; ?>><div class="service__img"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="service__body"><h3><?php echo esc_html( (string) ( $c['titre'] ?? '' ) ); ?></h3><p><?php echo esc_html( (string) ( $c['texte'] ?? '' ) ); ?></p><span class="link"><?php echo esc_html( (string) ( $c['lien_texte'] ?? '' ) ); ?> <?php echo bigm_core_icone( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></div></a>
		<?php endforeach; ?>
  </div>
</section>
		<?php
	}
}
