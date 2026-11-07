<?php
/**
 * Widget « Big M — Actualités » : les derniers articles (News, Presse) en cartes.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Actualités et presse.
 */
class Bigm_Widget_Actualites extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-actualites';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Actualités';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-post-list';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-accueil', 'bigm-articles' );
	}

	/** Réglages. */
	protected function register_controls() {
		$categories = array();
		foreach ( get_categories( array( 'hide_empty' => false ) ) as $c ) {
			$categories[ $c->term_id ] = $c->name;
		}
		$this->start_controls_section( 'contenu', array( 'label' => 'Actualités' ) );
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Actualités et presse',
				'label_block' => true,
			)
		);
		$this->add_control(
			'lien_texte',
			array(
				'label'   => 'Lien',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Toutes les actualités',
			)
		);
		$this->add_control(
			'lien',
			array(
				'label'       => 'Adresse du lien',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : la page des articles (Réglages → Lecture)',
			)
		);
		$this->add_control(
			'categories',
			array(
				'label'       => 'Catégories',
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $categories,
				'label_block' => true,
				'description' => 'Vide : tous les articles.',
			)
		);
		$this->add_control(
			'nombre',
			array(
				'label'   => 'Nombre d’articles',
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 12,
			)
		);
		$this->bigm_controle_ancre( 'actus' );
		$this->end_controls_section();
	}

	/** Rendu. */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$args  = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, (int) $s['nombre'] ),
			'ignore_sticky_posts' => true,
		);
		$cats  = array_filter( array_map( 'intval', (array) $s['categories'] ) );
		if ( $cats ) {
			$args['category__in'] = $cats;
		}
		$posts = get_posts( $args );
		if ( ! $posts ) {
			$this->bigm_vide( 'Aucun article publié pour l’instant.' );
			return;
		}
		$id      = $this->bigm_id( 'actus' );
		$archive = (int) get_option( 'page_for_posts' ) ? (string) get_permalink( (int) get_option( 'page_for_posts' ) ) : '/actualites/';
		$lien    = trim( (string) $s['lien_texte'] ) ? '<a class="link" ' . $this->bigm_href( $s['lien'], $archive ) . '>' . esc_html( (string) $s['lien_texte'] ) . ' ' . bigm_core_icone( 'arrow' ) . '</a>' : '';
		$s['texte'] = '';
		?>
<section class="container section"<?php echo $this->bigm_attr_ancre( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-labelledby="<?php echo esc_attr( $id ); ?>">
		<?php echo $this->bigm_entete( $s, $id, $lien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <div class="news-cards">
		<?php
		foreach ( $posts as $i => $p ) {
			echo bigm_carte_article( $p, $i ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
		}
		?>
  </div>
</section>
		<?php
	}
}
