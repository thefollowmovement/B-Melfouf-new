<?php
/**
 * Widget « Big M — Nous rejoindre » : cartes Franchise (avec les chiffres des Réglages Big M) et Recrutement.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Franchise et recrutement.
 */
class Bigm_Widget_Rejoindre extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-rejoindre';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Nous rejoindre';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-call-to-action';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil' );
	}

	/**
	 * Champs d'une carte.
	 *
	 * @param string $p      Préfixe (franchise, recrutement).
	 * @param string $label  Nom de la section.
	 * @param array  $defaut Titre, texte, bouton, lien, ancre.
	 */
	private function carte( string $p, string $label, array $defaut ): void {
		$this->start_controls_section( $p, array( 'label' => $label ) );
		$this->add_control(
			"{$p}_image",
			array(
				'label' => 'Photo de fond',
				'type'  => Controls_Manager::MEDIA,
			)
		);
		$this->add_control(
			"{$p}_titre",
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => $defaut[0],
				'label_block' => true,
			)
		);
		$this->add_control(
			"{$p}_texte",
			array(
				'label'   => 'Texte',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => $defaut[1],
			)
		);
		if ( 'franchise' === $p ) {
			$this->add_control(
				'franchise_chiffres',
				array(
					'label'        => 'Chiffres clés',
					'type'         => Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'label_on'     => 'Oui',
					'label_off'    => 'Non',
					'description'  => 'Durée du contrat, droit d’entrée et redevance, tirés de Réglages Big M.',
					'return_value' => 'yes',
				)
			);
		}
		$this->add_control(
			"{$p}_bouton",
			array(
				'label'   => 'Bouton',
				'type'    => Controls_Manager::TEXT,
				'default' => $defaut[2],
			)
		);
		$this->add_control(
			"{$p}_lien",
			array(
				'label'       => 'Lien du bouton',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : ' . $defaut[3],
			)
		);
		$this->add_control(
			"{$p}_ancre",
			array(
				'label'   => 'Ancre',
				'type'    => Controls_Manager::TEXT,
				'default' => $defaut[4],
			)
		);
		$this->end_controls_section();
	}

	/** Réglages. */
	protected function register_controls() {
		$nnbsp = "\u{202F}";
		$this->carte( 'franchise', 'Franchise', array( 'Devenez franchisé Big M', 'Créez, transformez ou reprenez un restaurant avec un concept clé en main, une formation complète et des campagnes TV.', 'Déposer ma candidature', '/devenir-franchise/', 'franchise' ) );
		$this->carte( 'recrutement', 'Recrutement', array( "Recrutez-nous{$nnbsp}!", "Équipiers, managers, formateurs{$nnbsp}: rejoignez Big M près de chez vous.", 'Postuler', '/recrutement/', 'recrutement' ) );
	}

	/** Rendu. */
	protected function render() {
		$s       = $this->get_settings_for_display();
		$chiffres = '';
		if ( 'yes' === $s['franchise_chiffres'] ) {
			foreach ( array(
				'franchise_duree'              => 'de contrat',
				'franchise_droit_entree'       => 'd\'entrée',
				'franchise_redevance_enseigne' => 'de redevance',
			) as $cle => $suite ) {
				$valeur = bigm_reglage_texte( $cle );
				if ( '' !== $valeur ) {
					$chiffres .= '<span><b>' . esc_html( $valeur ) . '</b> ' . esc_html( $suite ) . '</span>';
				}
			}
		}
		$cartes = array(
			'franchise'   => array( 'btn', '/devenir-franchise/' ),
			'recrutement' => array( 'btn btn--light', '/recrutement/' ),
		);
		?>
<section class="container" aria-label="Nous rejoindre">
  <div class="join">
		<?php
		$i = 0;
		foreach ( $cartes as $p => $c ) :
			$ancre = sanitize_title( (string) $s[ "{$p}_ancre" ] );
			?>
    <div class="join__card"<?php echo $ancre ? ' id="' . esc_attr( $ancre ) . '"' : ''; ?> data-reveal<?php echo $i ? ' style="--i:' . (int) $i . '"' : ''; ?>>
			<?php
			echo $this->bigm_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$s[ "{$p}_image" ],
				array(
					'alt'   => '',
					'sizes' => '(max-width: 960px) 100vw, 700px',
				)
			);
			?>
      <div class="join__body">
        <h2><?php echo esc_html( (string) $s[ "{$p}_titre" ] ); ?></h2>
        <p><?php echo esc_html( (string) $s[ "{$p}_texte" ] ); ?></p>
			<?php if ( 'franchise' === $p && $chiffres ) : ?>
        <div class="figures"><?php echo $chiffres; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $s[ "{$p}_bouton" ] ) ) : ?>
        <a class="<?php echo esc_attr( $c[0] ); ?>" <?php echo $this->bigm_href( $s[ "{$p}_lien" ], $c[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( (string) $s[ "{$p}_bouton" ] ); ?></a>
			<?php endif; ?>
      </div>
    </div>
			<?php
			++$i;
		endforeach;
		?>
  </div>
</section>
		<?php
	}
}
