<?php
/**
 * Widget « Big M — Restaurants » : recherche d'adresse (vers /restaurants/?q=…) et photo.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Bloc « Tous nos restaurants ».
 */
class Bigm_Widget_Restaurants extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-restaurants';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Restaurants';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-google-maps';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil' );
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Recherche' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Tous nos restaurants',
				'label_block' => true,
			)
		);
		$this->add_control(
			'libelle',
			array(
				'label'       => 'Consigne',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Entrez une adresse pour trouver le Big M le plus proche',
				'label_block' => true,
			)
		);
		$this->add_control(
			'placeholder',
			array(
				'label'   => 'Exemple dans le champ',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Ville, code postal ou adresse',
			)
		);
		$this->add_control(
			'bouton',
			array(
				'label'       => 'Bouton',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Rechercher',
				'description' => 'La recherche ouvre la page Restaurants (Réglages Big M) avec l’adresse saisie : /restaurants/?q=…',
			)
		);
		$this->add_control(
			'lien1_texte',
			array(
				'label'   => 'Premier lien',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Autour de moi',
			)
		);
		$this->add_control(
			'lien1',
			array(
				'label'       => 'Adresse du premier lien',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : la page Restaurants',
			)
		);
		$this->add_control(
			'lien2_texte',
			array(
				'label'   => 'Second lien',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Voir tous les restaurants',
			)
		);
		$this->add_control(
			'lien2',
			array(
				'label'       => 'Adresse du second lien',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : la page Restaurants',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'photo_section', array( 'label' => 'Photo' ) );
		$this->add_control(
			'photo',
			array(
				'label' => 'Photo',
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$this->add_control(
			'legende',
			array(
				'label'   => 'Légende',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Big M Saint-Ouen',
			)
		);
		$this->bigm_controle_ancre( 'restaurants' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$id     = $this->bigm_id( 'restos' );
		$champ  = 'adresse-' . $this->get_id();
		$restos = (string) bigm_reglage( 'restaurants_url' );
		$restos = '' !== $restos ? $restos : '/restaurants/';
		$photo  = $this->bigm_image( $s['photo'], array( 'sizes' => '(max-width: 960px) 100vw, 610px' ) );
		?>
<section class="container"<?php echo $this->bigm_attr_ancre( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $id ); ?>">
  <div class="restos">
    <form class="restos__search" action="<?php echo esc_url( $this->bigm_url( null, $restos ) ); ?>" method="get" role="search" data-reveal>
      <h2 id="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( (string) $s['titre'] ); ?></h2>
      <label for="<?php echo esc_attr( $champ ); ?>"><?php echo esc_html( (string) $s['libelle'] ); ?></label>
      <div class="search">
        <input id="<?php echo esc_attr( $champ ); ?>" name="q" type="text" placeholder="<?php echo esc_attr( (string) $s['placeholder'] ); ?>" autocomplete="street-address">
        <button class="btn" type="submit"><?php echo esc_html( (string) $s['bouton'] ); ?></button>
      </div>
      <div class="restos__links">
		<?php if ( '' !== trim( (string) $s['lien1_texte'] ) ) : ?>
        <a class="link" <?php echo $this->bigm_href( $s['lien1'], $restos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo bigm_core_icone( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( (string) $s['lien1_texte'] ); ?></a>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $s['lien2_texte'] ) ) : ?>
        <a class="link" <?php echo $this->bigm_href( $s['lien2'], $restos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( (string) $s['lien2_texte'] ); ?> <?php echo bigm_core_icone( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<?php endif; ?>
      </div>
    </form>
		<?php if ( $photo ) : ?>
    <figure class="restos__photo" data-reveal style="--i:1"><?php echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ( '' !== trim( (string) $s['legende'] ) ) : ?><figcaption><?php echo esc_html( (string) $s['legende'] ); ?></figcaption><?php endif; ?></figure>
		<?php endif; ?>
  </div>
</section>
		<?php
	}
}
