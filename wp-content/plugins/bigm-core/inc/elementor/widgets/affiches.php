<?php
/**
 * Widget « Big M — Affiches » : « Big M les a réunis pour vous », affiches datées.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Affiches.
 */
class Bigm_Widget_Affiches extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-affiches';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Affiches';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-gallery-justified';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Affiches' ) );
		$this->bigm_controles_entete( 'Big M les a réunis pour vous', "Né à Bondy en 2019, Big M est l'un des pionniers du fast-food halal en France. L'idée\u{202F}: réunir le meilleur du burger au même endroit." );

		$r = new Repeater();
		$r->add_control(
			'annee',
			array(
				'label' => 'Année',
				'type'  => Controls_Manager::TEXT,
			)
		);
		$r->add_control(
			'image',
			array(
				'label'       => 'Affiche',
				'type'        => Controls_Manager::MEDIA,
				'description' => 'Format portrait 9:16.',
			)
		);
		$r->add_control(
			'legende',
			array(
				'label' => 'Légende',
				'type'  => Controls_Manager::TEXT,
			)
		);
		$this->add_control(
			'affiches',
			array(
				'label'       => 'Affiches',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ annee }}} · {{{ legende }}}',
				'default'     => array(
					array(
						'annee'   => '1967',
						'legende' => 'Certains les préfèrent grand',
					),
					array(
						'annee'   => '1971',
						'legende' => 'D\'autres les aiment géant',
					),
					array(
						'annee'   => '1993',
						'legende' => 'La plupart au poulet',
					),
					array(
						'annee'   => '1957',
						'legende' => 'Et pour d\'autres, à la flamme',
					),
				),
			)
		);
		$this->bigm_controle_ancre( 'legendes' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = $this->bigm_id( 'legends' );
		?>
<section class="legends section"<?php echo $this->bigm_attr_ancre( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $id ); ?>">
  <div class="container">
		<?php echo $this->bigm_entete( $s, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <div class="legends__grid">
		<?php
		foreach ( (array) $s['affiches'] as $i => $a ) :
			$legende = (string) ( $a['legende'] ?? '' );
			$media   = $a['image'] ?? array();
			$alt     = ! empty( $media['id'] ) ? (string) get_post_meta( (int) $media['id'], '_wp_attachment_image_alt', true ) : '';
			$img     = $this->bigm_image(
				$media,
				array(
					'alt'   => '' !== $alt ? $alt : 'Affiche : ' . $legende,
					'sizes' => '(max-width: 960px) 46vw, 290px',
				)
			);
			?>
      <div class="legend" data-reveal<?php echo $i ? ' style="--i:' . (int) $i . '"' : ''; ?>><b><?php echo esc_html( (string) ( $a['annee'] ?? '' ) ); ?></b><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( $legende ); ?></span></div>
		<?php endforeach; ?>
    </div>
  </div>
</section>
		<?php
	}
}
