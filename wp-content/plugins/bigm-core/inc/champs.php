<?php
/**
 * Champs de l'admin : sélecteurs de médias, nettoyage des valeurs, scripts.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sélecteur de média de la médiathèque : un identifiant caché, un aperçu, deux boutons.
 *
 * @param string $name  Nom du champ.
 * @param int    $id    Identifiant de la pièce jointe (0 si vide).
 * @param string $type  « image », « video/mp4 » ou « video/webm ».
 * @param string $label Libellé.
 * @param string $aide  Texte d'aide, facultatif.
 */
function bigm_champ_media( string $name, int $id, string $type, string $label, string $aide = '' ): void {
	$url     = $id ? wp_get_attachment_url( $id ) : '';
	$preview = '';
	if ( $id && $url ) {
		$preview = str_starts_with( $type, 'image' )
			? wp_get_attachment_image( $id, 'thumbnail' )
			: '<span class="bigm-media__file">' . esc_html( wp_basename( $url ) ) . '</span>';
	}
	?>
	<div class="bigm-media" data-type="<?php echo esc_attr( $type ); ?>">
		<span class="bigm-media__label"><?php echo esc_html( $label ); ?></span>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $id ? (int) $id : ''; ?>">
		<span class="bigm-media__preview"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image ou nom échappé. ?></span>
		<button type="button" class="button bigm-media__choose"><?php echo $id ? 'Remplacer' : 'Choisir'; ?></button>
		<button type="button" class="button-link bigm-media__remove"<?php echo $id ? '' : ' hidden'; ?>>Retirer</button>
		<?php if ( $aide ) : ?>
			<span class="description"><?php echo esc_html( $aide ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Identifiant de pièce jointe valide, ou 0.
 *
 * @param mixed  $value Valeur reçue.
 * @param string $mime  Début du type attendu (« image/ », « video/mp4 »…), ou vide pour tout accepter.
 */
function bigm_nettoyer_media( $value, string $mime = '' ): int {
	$id = absint( $value );
	if ( ! $id || 'attachment' !== get_post_type( $id ) ) {
		return 0;
	}
	return ( '' === $mime || str_starts_with( (string) get_post_mime_type( $id ), $mime ) ) ? $id : 0;
}

/**
 * Vidéo de vignette : MP4, WebM et image d'aperçu.
 *
 * @param mixed $value Tableau reçu.
 * @return array{mp4: int, webm: int, poster: int}
 */
function bigm_nettoyer_video( $value ): array {
	$value = is_array( $value ) ? $value : array();
	return array(
		'mp4'    => bigm_nettoyer_media( $value['mp4'] ?? 0, 'video/mp4' ),
		'webm'   => bigm_nettoyer_media( $value['webm'] ?? 0, 'video/webm' ),
		'poster' => bigm_nettoyer_media( $value['poster'] ?? 0, 'image/' ),
	);
}

/**
 * Balises et attributs SVG acceptés pour les icônes.
 *
 * @return array<string, array<string, bool>>
 */
function bigm_svg_autorise(): array {
	$commun = array_fill_keys(
		array( 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'fill-rule', 'clip-rule', 'opacity', 'transform' ),
		true
	);
	return array(
		'svg'      => $commun + array_fill_keys( array( 'viewbox', 'xmlns', 'width', 'height', 'aria-hidden', 'focusable' ), true ),
		'g'        => $commun,
		'path'     => $commun + array( 'd' => true ),
		'circle'   => $commun + array_fill_keys( array( 'cx', 'cy', 'r' ), true ),
		'ellipse'  => $commun + array_fill_keys( array( 'cx', 'cy', 'rx', 'ry' ), true ),
		'rect'     => $commun + array_fill_keys( array( 'x', 'y', 'width', 'height', 'rx', 'ry' ), true ),
		'line'     => $commun + array_fill_keys( array( 'x1', 'y1', 'x2', 'y2' ), true ),
		'polyline' => $commun + array( 'points' => true ),
		'polygon'  => $commun + array( 'points' => true ),
	);
}

/**
 * Icône SVG nettoyée. Accepte un SVG complet, ou seulement un tracé (attribut d),
 * mis alors au format des icônes du prototype.
 *
 * @param mixed $value Texte reçu, déjà passé par wp_unslash().
 */
function bigm_nettoyer_svg( $value ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( ! str_contains( $value, '<' ) ) {
		return preg_match( '/^[MmLlHhVvCcSsQqTtAaZz0-9.,\s-]+$/', $value ) ? bigm_icone_depuis_trace( $value ) : '';
	}
	// Balises dangereuses retirées avec leur contenu, puis liste blanche.
	$value = (string) preg_replace( '#<(script|style|foreignObject|title|desc)\b[^>]*>.*?</\1\s*>#is', '', $value );
	$svg   = wp_kses( $value, bigm_svg_autorise() );
	return str_contains( $svg, '<svg' ) ? trim( $svg ) : '';
}

/**
 * Valeur nutritionnelle saisie telle qu'elle figure sur la fiche fournisseur
 * (chiffres, virgule, « < 0,5 »…).
 *
 * @param mixed $value Texte reçu.
 */
function bigm_nettoyer_nutrition( $value ): string {
	$value = preg_replace( '/[^0-9,.<>~ ]/', '', (string) $value );
	return substr( trim( (string) $value ), 0, 12 );
}

/**
 * Date et heure d'un champ datetime-local, au format « AAAA-MM-JJ HH:MM » (heure du site).
 *
 * @param mixed $value Texte reçu.
 */
function bigm_nettoyer_date( $value ): string {
	$value = str_replace( 'T', ' ', trim( (string) $value ) );
	$date  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $value, wp_timezone() );
	return $date ? $date->format( 'Y-m-d H:i' ) : '';
}

/**
 * Liste de slugs autorisés.
 *
 * @param mixed    $value     Tableau reçu.
 * @param string[] $autorises Slugs autorisés.
 * @return string[]
 */
function bigm_nettoyer_slugs( $value, array $autorises ): array {
	$value = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
	return array_values( array_intersect( $autorises, $value ) );
}

/*
 * Scripts et styles de l'admin, seulement sur les écrans du plugin.
 */
add_action(
	'admin_enqueue_scripts',
	static function ( string $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$ours = in_array( $screen->post_type, array( 'bigm_produit', 'bigm_slider' ), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$ours = $ours || ( 'bigm_categorie' === $screen->taxonomy && in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) );
		$ours = $ours || 'toplevel_page_bigm-reglages' === $hook;
		$ours = $ours || 'tfmsl_store_page_bigm-commande' === $hook;
		// Bloc Référencement (image de partage) des pages et articles.
		$ours = $ours || ( in_array( $screen->post_type, array( 'page', 'post' ), true ) && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) );
		if ( ! $ours ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'bigm-core-admin', BIGM_CORE_URL . 'assets/admin.css', array(), BIGM_CORE_VERSION );
		wp_enqueue_script( 'bigm-core-admin', BIGM_CORE_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), BIGM_CORE_VERSION, true );
	}
);
