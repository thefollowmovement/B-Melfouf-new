<?php
/**
 * Base des widgets Big M : catégorie, images, liens, en-têtes de section.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Widget Big M.
 */
abstract class Bigm_Widget extends \Elementor\Widget_Base {

	/** Catégorie du panneau. */
	public function get_categories() {
		return array( 'bigm' );
	}

	/** Mots-clés communs. */
	public function get_keywords() {
		return array( 'big m', 'bigm' );
	}

	/** Pas d'enveloppe .elementor-widget-container : le widget rend une section complète. */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/** Le rendu dépend des contenus du site : pas de cache d'élément d'Elementor. */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Mode édition ou aperçu d'Elementor.
	 */
	protected function bigm_dans_l_editeur(): bool {
		$plugin = \Elementor\Plugin::$instance;
		return ( $plugin->editor && $plugin->editor->is_edit_mode() ) || ( $plugin->preview && $plugin->preview->is_preview_mode() );
	}

	/**
	 * Produit de la page (fiche produit ou modèle « Publication unique » du Theme Builder).
	 * Dans l'éditeur, sans produit, le dernier produit publié sert d'aperçu.
	 */
	protected function bigm_produit_courant(): int {
		foreach ( array( (int) get_the_ID(), (int) get_queried_object_id() ) as $id ) {
			if ( $id && 'bigm_produit' === get_post_type( $id ) ) {
				return $id;
			}
		}
		if ( $this->bigm_dans_l_editeur() ) {
			$ids = get_posts(
				array(
					'post_type'      => 'bigm_produit',
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			return (int) ( $ids[0] ?? 0 );
		}
		return 0;
	}

	/**
	 * Catégorie de la page (archive /carte/burgers/). Dans l'éditeur, sans catégorie,
	 * la première catégorie qui a des produits sert d'aperçu.
	 */
	protected function bigm_categorie_courante(): ?WP_Term {
		$term = bigm_categorie_affichee();
		if ( ! $term && $this->bigm_dans_l_editeur() ) {
			$terms = bigm_categories( 0, true );
			$term  = $terms[0] ?? null;
		}
		return $term;
	}

	/**
	 * Catégories de la carte pour une liste déroulante, sous-catégories en retrait.
	 *
	 * @return array<int|string, string>
	 */
	protected function bigm_options_categories(): array {
		$options = array( 0 => '— Choisir —' );
		foreach ( bigm_categories( 0 ) as $term ) {
			$options[ $term->term_id ] = $term->name;
			foreach ( bigm_categories( $term->term_id ) as $enfant ) {
				$options[ $enfant->term_id ] = '— ' . $enfant->name;
			}
		}
		return $options;
	}

	/**
	 * Message affiché dans l'éditeur seulement, quand il n'y a rien à montrer.
	 *
	 * @param string $message Explication.
	 */
	protected function bigm_vide( string $message ): void {
		if ( $this->bigm_dans_l_editeur() ) {
			echo '<div style="padding:24px;border:2px dashed #c8400a;border-radius:20px;color:#6b4a3a;text-align:center">' . esc_html( $message ) . '</div>';
		}
	}

	/**
	 * Image d'un réglage « média » : srcset et dimensions si elle vient de la médiathèque.
	 *
	 * @param mixed                 $media Réglage (id, url).
	 * @param array<string, string> $attr  Attributs (alt, class, sizes, style…).
	 * @param string                $size  Taille WordPress.
	 */
	protected function bigm_image( $media, array $attr = array(), string $size = 'large' ): string {
		$media = is_array( $media ) ? $media : array();
		$id    = (int) ( $media['id'] ?? 0 );
		if ( $id && wp_attachment_is_image( $id ) ) {
			// WordPress pose lui-même loading et decoding : les passer ici les doublerait.
			return wp_get_attachment_image( $id, $size, false, $attr );
		}
		$url = (string) ( $media['url'] ?? '' );
		if ( '' === $url || str_contains( $url, 'placeholder.png' ) ) {
			return '';
		}
		$attr += array( 'loading' => 'lazy' );
		$html = '<img src="' . esc_url( $url ) . '"';
		foreach ( $attr as $k => $val ) {
			$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $val ) . '"';
		}
		return $html . '>';
	}

	/**
	 * URL d'un réglage « lien », ou un chemin du site par défaut.
	 *
	 * @param mixed  $lien   Réglage (url, is_external, nofollow).
	 * @param string $defaut Chemin ou URL par défaut.
	 */
	protected function bigm_url( $lien, string $defaut = '' ): string {
		$url = is_array( $lien ) ? trim( (string) ( $lien['url'] ?? '' ) ) : '';
		$url = '' !== $url ? $url : $defaut;
		return ( str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' ) ) ? home_url( $url ) : $url;
	}

	/**
	 * Attributs d'un lien : href, plus target et rel si le lien s'ouvre ailleurs.
	 *
	 * @param mixed  $lien   Réglage « lien ».
	 * @param string $defaut Chemin par défaut.
	 */
	protected function bigm_href( $lien, string $defaut = '' ): string {
		$attr = 'href="' . esc_url( $this->bigm_url( $lien, $defaut ) ) . '"';
		if ( is_array( $lien ) && ! empty( $lien['is_external'] ) ) {
			$attr .= ' target="_blank"';
		}
		$rel = array();
		if ( is_array( $lien ) && ! empty( $lien['is_external'] ) ) {
			$rel[] = 'noopener';
		}
		if ( is_array( $lien ) && ! empty( $lien['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}
		return $rel ? $attr . ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"' : $attr;
	}

	/**
	 * Identifiant HTML propre au widget (titres reliés par aria-labelledby).
	 *
	 * @param string $nom Suffixe.
	 */
	protected function bigm_id( string $nom ): string {
		return 't-' . $nom . '-' . $this->get_id();
	}

	/**
	 * Champs d'en-tête de section : titre et texte.
	 *
	 * @param string $titre Titre par défaut.
	 * @param string $texte Texte par défaut (vide : pas de texte).
	 */
	protected function bigm_controles_entete( string $titre, string $texte = '' ): void {
		$this->add_control(
			'titre',
			array(
				'label'       => 'Titre',
				'type'        => Controls_Manager::TEXT,
				'default'     => $titre,
				'label_block' => true,
			)
		);
		$this->add_control(
			'texte',
			array(
				'label'   => 'Texte sous le titre',
				'type'    => Controls_Manager::TEXTAREA,
				'default' => $texte,
				'rows'    => 3,
			)
		);
	}

	/**
	 * Champ d'ancre (pour les liens du menu comme /#appli).
	 *
	 * @param string $defaut Ancre par défaut.
	 */
	protected function bigm_controle_ancre( string $defaut ): void {
		$this->add_control(
			'ancre',
			array(
				'label'       => 'Ancre',
				'type'        => Controls_Manager::TEXT,
				'default'     => $defaut,
				'description' => 'Permet un lien direct vers la section (ex. /#' . $defaut . '). Une seule fois par page.',
			)
		);
	}

	/**
	 * Attribut id à partir du réglage d'ancre.
	 *
	 * @param array<string, mixed> $s Réglages.
	 */
	protected function bigm_attr_ancre( array $s ): string {
		$ancre = sanitize_title( (string) ( $s['ancre'] ?? '' ) );
		return $ancre ? ' id="' . esc_attr( $ancre ) . '"' : '';
	}

	/**
	 * En-tête de section du prototype : titre, texte, lien à droite.
	 *
	 * @param array<string, mixed> $s    Réglages (titre, texte).
	 * @param string               $id   Identifiant du titre.
	 * @param string               $lien HTML du lien ou bouton de droite (déjà échappé).
	 */
	protected function bigm_entete( array $s, string $id, string $lien = '' ): string {
		$titre = '<h2 id="' . esc_attr( $id ) . '">' . esc_html( (string) ( $s['titre'] ?? '' ) ) . '</h2>';
		$texte = trim( (string) ( $s['texte'] ?? '' ) );
		$gauche = '' !== $texte ? '<div>' . $titre . '<p>' . esc_html( $texte ) . '</p></div>' : $titre;
		return '<div class="section-head">' . $gauche . $lien . '</div>';
	}
}
