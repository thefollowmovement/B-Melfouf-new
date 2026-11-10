<?php
/**
 * Widget « Big M — Classiques » : carrousel de vignettes produit (« Découvrez nos classiques »).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Carrousel « Nos classiques ».
 */
class Bigm_Widget_Classiques extends Bigm_Widget {

	/** Identifiant. */
	public function get_name() {
		return 'bigm-classiques';
	}

	/** Nom dans le panneau. */
	public function get_title() {
		return 'Big M — Classiques';
	}

	/** Icône du panneau. */
	public function get_icon() {
		return 'eicon-posts-carousel';
	}

	/** Styles. */
	public function get_style_depends(): array {
		return array( 'bigm-tuiles', 'bigm-accueil' );
	}

	/** Scripts. */
	public function get_script_depends(): array {
		return array( 'bigm-carrousel', 'bigm-tuiles-video' );
	}

	/** Réglages. */
	protected function register_controls() {
		$produits = array();
		foreach ( get_posts(
			array(
				'post_type'      => 'bigm_produit',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		) as $p ) {
			$produits[ $p->ID ] = get_the_title( $p );
		}
		$categories = array( '' => 'Toute la carte' );
		foreach ( get_terms(
			array(
				'taxonomy'   => 'bigm_categorie',
				'hide_empty' => false,
			)
		) as $t ) {
			$categories[ $t->term_id ] = ( $t->parent ? '— ' : '' ) . $t->name;
		}

		$this->start_controls_section( 'contenu', array( 'label' => 'Classiques' ) );
		$this->bigm_controles_entete( 'Découvrez nos classiques', "Grand, géant, au poulet ou à la flamme\u{202F}: il y en a pour tous les goûts." );
		$this->add_control(
			'bouton',
			array(
				'label'   => 'Bouton',
				'type'    => Controls_Manager::TEXT,
				'default' => 'Voir la carte',
			)
		);
		$this->add_control(
			'bouton_lien',
			array(
				'label'       => 'Lien du bouton',
				'type'        => Controls_Manager::URL,
				'placeholder' => 'Vide : la carte',
			)
		);
		$this->add_control(
			'source',
			array(
				'label'     => 'Produits',
				'type'      => Controls_Manager::SELECT,
				'default'   => 'categorie',
				'options'   => array(
					'choix'     => 'Choisis un par un',
					'categorie' => 'D’une catégorie',
				),
				'separator' => 'before',
			)
		);

		$r = new Repeater();
		$r->add_control(
			'produit',
			array(
				'label'   => 'Produit',
				'type'    => Controls_Manager::SELECT,
				'options' => array( '' => '— Choisir —' ) + $produits,
			)
		);
		$this->add_control(
			'produits',
			array(
				'label'       => 'Produits, dans l’ordre',
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $r->get_controls(),
				'title_field' => '{{{ (' . wp_json_encode( array_map( 'html_entity_decode', $produits ) ) . ')[ produit ] || "Produit" }}}',
				'condition'   => array( 'source' => 'choix' ),
			)
		);
		$this->add_control(
			'categorie',
			array(
				'label'       => 'Catégorie',
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $categories,
				'condition'   => array( 'source' => 'categorie' ),
				'description' => 'Dans l’ordre du champ « Ordre » des produits.',
			)
		);
		$this->add_control(
			'nombre',
			array(
				'label'     => 'Nombre maximum',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 9,
				'min'       => 1,
				'max'       => 30,
				'condition' => array( 'source' => 'categorie' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Produits à afficher.
	 *
	 * @param array<string, mixed> $s Réglages.
	 * @return int[]
	 */
	private function produits( array $s ): array {
		if ( 'choix' === $s['source'] ) {
			$ids = array_map( static fn( $r ) => (int) ( $r['produit'] ?? 0 ), (array) $s['produits'] );
			return array_values( array_filter( $ids, static fn( int $id ): bool => 'publish' === get_post_status( $id ) && 'bigm_produit' === get_post_type( $id ) ) );
		}
		$args = array(
			'post_type'      => 'bigm_produit',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) $s['nombre'] ),
			'fields'         => 'ids',
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		);
		if ( $s['categorie'] ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'bigm_categorie',
					'terms'    => (int) $s['categorie'],
				),
			);
		}
		return array_map( 'intval', get_posts( $args ) );
	}

	/** Rendu. */
	protected function render() {
		$s   = $this->get_settings_for_display();
		$ids = $this->produits( $s );
		if ( ! $ids ) {
			$this->bigm_vide( 'Aucun produit publié à montrer : ajouter des produits, ou en choisir.' );
			return;
		}
		$id     = $this->bigm_id( 'classics' );
		$bouton = trim( (string) $s['bouton'] );
		$lien   = $bouton ? '<a class="btn btn--outline" ' . $this->bigm_href( $s['bouton_lien'], (string) get_post_type_archive_link( 'bigm_produit' ) ) . '>' . esc_html( $bouton ) . '</a>' : '';
		?>
<section class="container" aria-labelledby="<?php echo esc_attr( $id ); ?>">
		<?php echo $this->bigm_entete( $s, $id, $lien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
  <div data-carousel>
    <div class="classics__track" data-track>
		<?php
		foreach ( $ids as $pid ) {
			echo bigm_tuile_produit( $pid, '(max-width: 960px) 46vw, 230px' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
    </div>
    <div class="carousel-ctrl"><span></span><div class="arrows"><button type="button" data-prev aria-label="Précédent"><?php echo bigm_core_icone( 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button><button type="button" data-next aria-label="Suivant"><?php echo bigm_core_icone( 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button></div></div>
  </div>
</section>
		<?php
	}
}
