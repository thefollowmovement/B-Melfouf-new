<?php
/**
 * Widget « Big M — Bande appli » : aplat orange, téléphone, avantages, badges des stores.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Bande appli.
 */
class Bigm_Widget_Bande_Appli extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-bande-appli';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Bande appli';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-device-mobile';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil' );
	}

	/** Réglages. */
	protected function register_controls() {
		$nnbsp = "\u{202F}";
		$this->start_controls_section( 'contenu', array( 'label' => 'Bande appli' ) );
		$this->add_control(
			'image',
			array(
				'label' => 'Image du téléphone',
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Profitez d\'offres exclusives',
				'label_block' => true,
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'   => 'Texte',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Commandez sur l\'application Big M auprès de votre restaurant préféré.',
			)
		);
		$this->add_control(
			'puces',
			array(
				'label'       => 'Avantages',
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => "Livraison ou click & collect\n5{$nnbsp}€ offerts tous les 100{$nnbsp}€ d'achat\nDes offres réservées à l'appli",
				'description' => 'Un avantage par ligne. Les liens des stores se règlent dans Réglages Big M.',
				'rows'        => 4,
			)
		);
		$this->bigm_controle_ancre( 'appli' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$id    = $this->bigm_id( 'app' );
		$puces = array_filter( array_map( 'trim', preg_split( '/\R/', (string) $s['puces'] ) ) );
		$img   = $this->bigm_image( $s['image'], array( 'sizes' => '(max-width: 960px) 90vw, 440px' ) ); // Texte alternatif : celui de la médiathèque.
		?>
<section class="container section"<?php echo $this->bigm_attr_ancre( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $id ); ?>">
  <div class="app-band" data-reveal>
    <div class="app-band__img"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
    <div class="app-band__text">
      <h2 id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( (string) $s['titre'] ); ?></h2>
      <p><?php echo esc_html( (string) $s['texte'] ); ?></p>
		<?php if ( $puces ) : ?>
      <ul><?php foreach ( $puces as $puce ) : ?><li><?php echo esc_html( $puce ); ?></li><?php endforeach; ?></ul>
		<?php endif; ?>
		<?php echo bigm_core_stores_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
  </div>
</section>
		<?php
	}
}
