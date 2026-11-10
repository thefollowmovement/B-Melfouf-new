<?php
/**
 * Widget « Big M — Nos Box » : les Box en cartes (photo et titre) ; la Box choisie
 * montre son contenu en grand. Onglets accessibles (role tablist / tab / tabpanel).
 *
 * Ordinateur : grand visuel à gauche, titre et cartes à droite.
 * Mobile et tablette : titre, cartes qui défilent au doigt, puis le visuel.
 *
 * @package BigM\Box
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Nos Box.
 */
class Bigm_Box_Widget extends \Elementor\Widget_Base {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-box';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Nos Box';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	/** Catégorie du panneau. */
	public function get_categories() {
		return array( 'bigm' );
	}

	/** Mots-clés de recherche. */
	public function get_keywords() {
		return array( 'big m', 'bigm', 'box', 'menu', 'partager', 'offre' );
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-box' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-box' );
	}

	/** Pas d'enveloppe .elementor-widget-container : le widget rend une section complète. */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Le lien Commander vient des Réglages Big M : pas de cache d'élément d'Elementor. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/** Réglages. */
	protected function register_controls() {
		$this->start_controls_section( 'contenu', array( 'label' => 'Nos Box' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Nos Box',
				'label_block' => true,
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'   => 'Texte sous le titre',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => 'Pensées pour les soirées match, les commandes groupées et les repas à partager. Choisissez une Box pour voir ce qu’elle contient.',
				'rows'    => 3,
			)
		);

		$r = new Repeater();
		$r->add_control(
			'titre',
			array(
				'label'       => 'Nom de la Box',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Box',
				'label_block' => true,
			)
		);
		$r->add_control(
			'photo',
			array(
				'label'       => 'Photo de la carte',
				'type'        => Controls_Manager::MEDIA,
				'description' => 'Vignette carrée (1:1), 600 × 600 px ou plus. Fond uni de préférence, comme les vignettes de la carte.',
			)
		);
		$r->add_control(
			'contenu',
			array(
				'label'       => 'Visuel du contenu',
				'type'        => Controls_Manager::MEDIA,
				'description' => 'Image affichée en grand quand la Box est choisie. Paysage 4:3, 1600 × 1200 px de préférence. Sans visuel : la photo de la carte.',
			)
		);
		$r->add_control(
			'texte',
			array(
				'label'       => 'Ce qu’elle contient',
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'description' => 'Une ou deux phrases sous le visuel. Facultatif.',
			)
		);
		$r->add_control(
			'badge',
			array(
				'label'       => 'Badge',
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'Nouveau, Édition limitée…',
				'description' => 'Pastille jaune sur la carte. Vide : pas de pastille.',
			)
		);
		$r->add_control(
			'lien',
			array(
				'label'       => 'Lien du bouton',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : lien Commander des Réglages Big M',
			)
		);
		$this->add_control(
			'boxes',
			array(
				'label'       => 'Box, dans l’ordre',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ titre }}}',
				'default'     => array(
					array( 'titre' => 'Box 1' ),
					array( 'titre' => 'Box 2' ),
					array( 'titre' => 'Box 3' ),
				),
				'separator'   => 'before',
			)
		);
		$this->add_control(
			'bouton',
			array(
				'label'       => 'Texte du bouton',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Commander',
				'description' => 'Sous le visuel. Vide : pas de bouton. Le bouton n’apparaît que si la Box a un lien, ou si le lien Commander est rempli dans Réglages Big M.',
				'separator'   => 'before',
			)
		);
		$this->add_control(
			'ancre',
			array(
				'label'       => 'Ancre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'box',
				'description' => 'Permet un lien direct vers la section (ex. /#box). Une seule fois par page.',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section( 'apparence', array( 'label' => 'Apparence' ) );
		$this->add_control(
			'fond',
			array(
				'label'   => 'Fond',
				'type'    => Controls_Manager::SELECT,
				'default' => 'orange',
				'options' => array(
					'orange' => 'Aplat orange',
					'creme'  => 'Aplat crème',
					'aucun'  => 'Sans fond',
				),
			)
		);
		$this->add_control(
			'cadrage',
			array(
				'label'       => 'Visuel du contenu',
				'type'        => Controls_Manager::SELECT,
				'default'     => 'cover',
				'options'     => array(
					'cover'   => 'Remplit le cadre (bords recadrés)',
					'contain' => 'Image entière',
				),
				'description' => '« Image entière » pour un visuel qui n’est pas en 4:3 et ne doit pas être recadré.',
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Mode édition ou aperçu d'Elementor.
	 */
	private function dans_l_editeur(): bool {
		$plugin = \Elementor\Plugin::$instance;
		return ( $plugin->editor && $plugin->editor->is_edit_mode() ) || ( $plugin->preview && $plugin->preview->is_preview_mode() );
	}

	/**
	 * Image d'un réglage « média » : srcset et dimensions si elle vient de la médiathèque.
	 *
	 * @param mixed                 $media Réglage (id, url).
	 * @param array<string, string> $attr  Attributs (alt, class, sizes…).
	 * @param string                $size  Taille WordPress.
	 */
	private function image( $media, array $attr, string $size ): string {
		$media = is_array( $media ) ? $media : array();
		$id    = (int) ( $media['id'] ?? 0 );
		if ( $id && wp_attachment_is_image( $id ) ) {
			return (string) wp_get_attachment_image( $id, $size, false, $attr + array( 'loading' => 'lazy' ) );
		}
		$url = (string) ( $media['url'] ?? '' );
		if ( '' === $url || str_contains( $url, 'placeholder.png' ) ) {
			return '';
		}
		$html = '<img src="' . esc_url( $url ) . '"';
		foreach ( $attr + array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		) as $k => $v ) {
			$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		return $html . '>';
	}

	/**
	 * Lien du bouton : celui de la Box, sinon le lien Commander des Réglages Big M.
	 *
	 * @param mixed $lien Réglage « lien » de la Box.
	 * @return array{url: string, externe: bool, nofollow: bool}
	 */
	private function lien( $lien ): array {
		$lien = is_array( $lien ) ? $lien : array();
		$url  = trim( (string) ( $lien['url'] ?? '' ) );
		if ( '' !== $url ) {
			return array(
				'url'      => ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) ? home_url( $url ) : $url,
				'externe'  => ! empty( $lien['is_external'] ),
				'nofollow' => ! empty( $lien['nofollow'] ),
			);
		}
		$global = function_exists( 'bigm_reglage' ) ? trim( (string) bigm_reglage( 'commander_url' ) ) : '';
		return array(
			'url'      => (string) apply_filters( 'bigm_box_lien_commander', $global ),
			'externe'  => false,
			'nofollow' => false,
		);
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$boxes = array_values(
			array_filter(
				(array) ( $s['boxes'] ?? array() ),
				static fn( $b ): bool => is_array( $b ) && '' !== trim( (string) ( $b['titre'] ?? '' ) )
			)
		);
		if ( ! $boxes ) {
			if ( $this->dans_l_editeur() ) {
				echo '<div style="padding:24px;border:2px dashed #c8400a;border-radius:20px;color:#6b4a3a;text-align:center">Ajoutez une Box (avec un nom) dans le panneau de gauche.</div>';
			}
			return;
		}

		$uid     = $this->get_id();
		$titre   = 't-box-' . $uid;
		$fond    = in_array( $s['fond'] ?? '', array( 'orange', 'creme', 'aucun' ), true ) ? $s['fond'] : 'orange';
		$cadrage = 'contain' === ( $s['cadrage'] ?? '' ) ? 'contain' : 'cover';
		$ancre   = sanitize_title( (string) ( $s['ancre'] ?? '' ) );
		$texte   = trim( (string) ( $s['texte'] ?? '' ) );
		$bouton  = trim( (string) ( $s['bouton'] ?? '' ) );
		?>
<section class="container section"<?php echo $ancre ? ' id="' . esc_attr( $ancre ) . '"' : ''; ?> aria-labelledby="<?php echo esc_attr( $titre ); ?>">
  <div class="bigm-boxes bigm-boxes--<?php echo esc_attr( $fond ); ?> bigm-boxes--<?php echo esc_attr( $cadrage ); ?>" data-bigm-boxes data-reveal>
    <div class="bigm-boxes__head">
      <h2 id="<?php echo esc_attr( $titre ); ?>"><?php echo esc_html( (string) ( $s['titre'] ?? '' ) ); ?></h2>
		<?php if ( '' !== $texte ) : ?>
      <p><?php echo esc_html( $texte ); ?></p>
		<?php endif; ?>
    </div>
    <div class="bigm-boxes__tabs" role="tablist" aria-labelledby="<?php echo esc_attr( $titre ); ?>" data-bigm-boxes-tabs>
		<?php
		foreach ( $boxes as $i => $b ) :
			$pid   = 'bbox-' . $uid . '-' . $i;
			$badge = trim( (string) ( $b['badge'] ?? '' ) );
			$photo = $this->image( $b['photo'] ?? array(), array( 'alt' => '', 'sizes' => '(max-width: 960px) 40vw, 180px' ), 'medium_large' );
			?>
      <button type="button" role="tab" class="bigm-boxes__card" id="<?php echo esc_attr( $pid ); ?>-tab" aria-controls="<?php echo esc_attr( $pid ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>">
        <span class="bigm-boxes__thumb"><?php echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image ou balise échappée. ?></span>
        <span class="bigm-boxes__name"><?php echo esc_html( trim( (string) $b['titre'] ) ); ?></span>
			<?php if ( '' !== $badge ) : ?>
        <span class="bigm-boxes__badge"><?php echo esc_html( $badge ); ?></span>
			<?php endif; ?>
      </button>
		<?php endforeach; ?>
    </div>
    <div class="bigm-boxes__stage">
		<?php
		foreach ( $boxes as $i => $b ) :
			$pid    = 'bbox-' . $uid . '-' . $i;
			$nom    = trim( (string) $b['titre'] );
			$media  = ! empty( $b['contenu']['url'] ) && ! str_contains( (string) $b['contenu']['url'], 'placeholder.png' ) ? $b['contenu'] : ( $b['photo'] ?? array() );
			// Visuel de la première Box : visible dès l'arrivée (souvent l'élément le plus grand de l'écran
			// sur mobile), donc chargé tout de suite. Les autres attendent (box.js les précharge).
			// Premier visuel : souvent le plus grand élément de l'écran sur mobile (LCP), chargé tout de suite et en priorité.
			$charge = 0 === $i ? array( 'loading' => 'eager', 'fetchpriority' => 'high' ) : array();
			$visuel = $this->image( $media, array( 'alt' => $this->alt( $media, $nom ), 'sizes' => '(max-width: 960px) 92vw, 680px' ) + $charge, 'large' );
			$desc   = trim( (string) ( $b['texte'] ?? '' ) );
			$lien   = $bouton ? $this->lien( $b['lien'] ?? array() ) : array( 'url' => '' );
			?>
      <div class="bigm-boxes__panel" role="tabpanel" id="<?php echo esc_attr( $pid ); ?>" aria-labelledby="<?php echo esc_attr( $pid ); ?>-tab" tabindex="0"<?php echo 0 === $i ? '' : ' hidden'; ?>>
        <div class="bigm-boxes__visual"><?php echo $visuel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- idem. ?></div>
        <div class="bigm-boxes__body">
          <div class="bigm-boxes__info">
            <h3><?php echo esc_html( $nom ); ?></h3>
			<?php if ( '' !== $desc ) : ?>
            <p><?php echo nl2br( esc_html( $desc ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- texte échappé. ?></p>
			<?php endif; ?>
          </div>
			<?php if ( '' !== $lien['url'] ) : ?>
          <a class="btn" href="<?php echo esc_url( $lien['url'] ); ?>"<?php echo $lien['externe'] ? ' target="_blank"' : ''; ?><?php echo $this->rel( $lien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attribut échappé. ?>><?php echo esc_html( $bouton ); ?><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
			<?php endif; ?>
        </div>
      </div>
		<?php endforeach; ?>
    </div>
  </div>
</section>
		<?php
	}

	/**
	 * Texte alternatif du visuel : celui de la médiathèque, sinon « Contenu de la {nom} ».
	 *
	 * @param mixed  $media Réglage « média ».
	 * @param string $nom   Nom de la Box.
	 */
	private function alt( $media, string $nom ): string {
		$id  = is_array( $media ) ? (int) ( $media['id'] ?? 0 ) : 0;
		$alt = $id ? trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : '';
		return '' !== $alt ? $alt : sprintf( 'Contenu de la %s', $nom );
	}

	/**
	 * Attribut rel d'un lien qui s'ouvre ailleurs ou ne doit pas être suivi.
	 *
	 * @param array{externe: bool, nofollow: bool} $lien Lien.
	 */
	private function rel( array $lien ): string {
		$rel = array();
		if ( ! empty( $lien['externe'] ) ) {
			$rel[] = 'noopener';
		}
		if ( ! empty( $lien['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		return $rel ? ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : '';
	}
}
