<?php
/**
 * Elementor : polices locales et charte dans les réglages globaux du site.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/*
 * Fredoka et Figtree figurent dans la liste Google Fonts d'Elementor : sans ce
 * filtre, les choisir dans un widget les ferait charger depuis Google (RGPD).
 * On les range dans un groupe à part, servi par nos @font-face (tokens.css).
 */
add_filter(
	'elementor/fonts/additional_fonts',
	static function ( array $fonts ): array {
		foreach ( bigm_child_fonts() as $family ) {
			$fonts[ $family ] = 'bigm';
		}
		return $fonts;
	}
);

add_filter(
	'elementor/fonts/groups',
	static fn( array $groups ): array => array( 'bigm' => 'Big M (auto-hébergées)' ) + $groups
);

/*
 * Google Fonts coupé, quel que soit le réglage choisi dans Elementor > Réglages.
 */
add_filter( 'pre_option_elementor_google_font', static fn() => '0' );
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );

/**
 * Réglages du kit Elementor tirés de la charte.
 *
 * Couleurs et polices « système » : Elementor les applique par défaut aux
 * widgets (titres = Primary, texte = Text, boutons = Accent).
 * Couleurs et polices « personnalisées » : le reste de la charte, préfixé
 * « bigm » pour pouvoir les remplacer sans toucher à celles ajoutées à la main.
 *
 * @return array<string, mixed>
 */
function bigm_child_elementor_kit_tokens(): array {
	$c = bigm_child_colors();
	$f = bigm_child_fonts();

	$color = static fn( string $id, string $title, string $hex ): array => array(
		'_id'   => $id,
		'title' => $title,
		'color' => strtoupper( $hex ),
	);

	$font = static fn( string $id, string $title, string $family, string $weight ): array => array(
		'_id'                    => $id,
		'title'                  => $title,
		'typography_typography'  => 'custom',
		'typography_font_family' => $family,
		'typography_font_weight' => $weight,
	);

	$px = static fn( int $size ): array => array(
		'unit'  => 'px',
		'size'  => $size,
		'sizes' => array(),
	);

	return array(
		'system_colors'          => array(
			$color( 'primary', 'Titres (--ink)', $c['ink'] ),
			$color( 'secondary', 'Texte secondaire (--muted)', $c['muted'] ),
			$color( 'text', 'Texte (--ink)', $c['ink'] ),
			$color( 'accent', 'Boutons (--btn)', $c['btn'] ),
		),
		'custom_colors'          => array(
			$color( 'bigmbg', 'Fond (--bg)', $c['bg'] ),
			$color( 'bigmtile', 'Vignettes (--tile)', $c['tile'] ),
			$color( 'bigmcard', 'Cartes (--card)', $c['card'] ),
			$color( 'bigmorange', 'Orange, aplats (--orange)', $c['orange'] ),
			$color( 'bigmbtnhover', 'Bouton au survol (--btn-hover)', $c['btn-hover'] ),
			$color( 'bigmyellow', 'Jaune, badges (--yellow)', $c['yellow'] ),
			$color( 'bigmfooterink', 'Texte du pied de page (--footer-ink)', $c['footer-ink'] ),
		),
		'system_typography'      => array(
			$font( 'primary', 'Titres (Fredoka 600)', $f['display'], '600' ),
			$font( 'secondary', 'Sous-titres (Fredoka 500)', $f['display'], '500' ),
			$font( 'text', 'Texte (Figtree 400)', $f['text'], '400' ),
			$font( 'accent', 'Boutons (Fredoka 600)', $f['display'], '600' ),
		),
		'custom_typography'      => array(
			$font( 'bigmtextbold', 'Texte gras (Figtree 600)', $f['text'], '600' ),
		),
		// Largeur du contenu : --max du prototype, pleine largeur en dessous.
		'container_width'        => $px( 1240 ),
		'container_width_tablet' => $px( 960 ),
		'container_width_mobile' => $px( 760 ),
		// Points de rupture du prototype : mobile ≤ 760 px, tablette ≤ 960 px.
		'viewport_mobile'        => 760,
		'viewport_tablet'        => 960,
	);
}

/**
 * Applique la charte au kit Elementor actif.
 *
 * Les couleurs et polices ajoutées à la main dans Elementor sont conservées ;
 * seules les entrées système et celles préfixées « bigm » sont réécrites.
 *
 * @return true|WP_Error
 */
function bigm_child_sync_elementor_kit() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return new WP_Error( 'bigm_no_elementor', 'Elementor n’est pas actif.' );
	}

	$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
	if ( ! $kit || ! $kit->get_id() ) {
		return new WP_Error( 'bigm_no_kit', 'Aucun kit Elementor actif.' );
	}

	if ( ! $kit->is_editable_by_current_user() ) {
		return new WP_Error( 'bigm_cannot_edit', 'Il faut un compte administrateur (en ligne de commande : --user=<identifiant>).' );
	}

	$settings = get_post_meta( $kit->get_id(), '_elementor_page_settings', true );
	$settings = is_array( $settings ) ? $settings : array();
	$tokens   = bigm_child_elementor_kit_tokens();

	$without_bigm = static fn( $items ): array => array_values(
		array_filter(
			is_array( $items ) ? $items : array(),
			static fn( $item ): bool => ! str_starts_with( (string) ( $item['_id'] ?? '' ), 'bigm' )
		)
	);

	foreach ( array( 'custom_colors', 'custom_typography' ) as $key ) {
		$tokens[ $key ] = array_merge( $without_bigm( $settings[ $key ] ?? array() ), $tokens[ $key ] );
	}

	// Kit::save() recompile les feuilles d'Elementor quand les points de rupture changent.
	if ( ! $kit->save( array( 'settings' => array_merge( $settings, $tokens ) ) ) ) {
		return new WP_Error( 'bigm_save_failed', 'Elementor a refusé l’enregistrement du kit.' );
	}

	\Elementor\Plugin::$instance->files_manager->clear_cache();
	update_option( 'bigm_child_kit_synced', BIGM_CHILD_VERSION, false );

	return true;
}

/*
 * La charte est appliquée une fois : à l'activation du thème, ou au premier
 * passage d'un administrateur si Elementor n'était pas encore actif.
 * Ensuite, c'est à la demande (wp bigm kit), pour ne pas écraser les réglages
 * faits dans Elementor.
 */
$bigm_child_sync_once = static function () {
	if ( get_option( 'bigm_child_kit_synced' ) || wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( true === bigm_child_sync_elementor_kit() ) {
		set_transient( 'bigm_child_kit_notice', 1, HOUR_IN_SECONDS );
	}
};
add_action( 'after_switch_theme', $bigm_child_sync_once );
add_action( 'admin_init', $bigm_child_sync_once, 20 );

add_action(
	'admin_notices',
	static function () {
		if ( ! get_transient( 'bigm_child_kit_notice' ) ) {
			return;
		}
		delete_transient( 'bigm_child_kit_notice' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Big M</strong> : charte appliquée à Elementor (couleurs et polices globales, largeur 1240 px, points de rupture 760 / 960 px).</p></div>';
	}
);

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bigm kit',
		static function () {
			$result = bigm_child_sync_elementor_kit();
			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
			WP_CLI::success( 'Charte Big M appliquée au kit Elementor (couleurs, polices, largeur, points de rupture).' );
		},
		array( 'shortdesc' => 'Applique la charte Big M au kit Elementor actif.' )
	);
}
