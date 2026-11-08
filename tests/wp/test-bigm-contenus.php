<?php
/**
 * Test de l'étape 6 : restaurants (plugin StoreLocatore), actualités, sections de texte et chiffres,
 * formulaires de la démo. Sur un site de test avec Elementor, bigm-core et le thème Big M
 * (StoreLocatore et bigm-demo facultatifs : leurs vérifications sont alors sautées) :
 *
 *   wp eval-file tests/wp/test-bigm-contenus.php --user=<administrateur>
 *
 * Crée des articles, une page et un restaurant, puis les supprime.
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'bigm_carte_article' ) ) {
	WP_CLI::error( 'Il faut Elementor et bigm-core (0.5 ou plus) actifs.' );
}

$echecs = 0;
$posts  = array();
$termes = array();
$ok     = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};
$rendu  = static function ( string $type, array $settings ): string {
	$el = \Elementor\Plugin::$instance->elements_manager->create_element_instance(
		array(
			'elType'     => 'widget',
			'widgetType' => $type,
			'id'         => substr( md5( $type . wp_json_encode( $settings ) ), 0, 7 ),
			'settings'   => $settings,
		)
	);
	if ( ! $el ) {
		return '';
	}
	ob_start();
	$el->print_element();
	return (string) ob_get_clean();
};
$page   = static function ( array $query ): void {
	$GLOBALS['wp_query']     = new WP_Query( $query ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
};
// Styles et scripts mis en file pour une page simulée.
$assets = static function (): array {
	foreach ( array( 'wp_styles', 'wp_scripts' ) as $g ) {
		$GLOBALS[ $g ] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
	wp_styles();
	wp_scripts();
	do_action( 'wp_enqueue_scripts' );
	return array_merge( wp_styles()->queue, wp_scripts()->queue );
};

WP_CLI::log( 'Widgets' );
$wm = \Elementor\Plugin::$instance->widgets_manager;
$ok( (bool) $wm->get_widget_types( 'bigm-texte' ) && (bool) $wm->get_widget_types( 'bigm-chiffres' ), 'Section texte et Chiffres de la franchise enregistrés' );
$texte = $rendu(
	'bigm-texte',
	array(
		'titre'  => 'Pourquoi Big M',
		'texte'  => '<p>Un <strong>concept</strong> clé en main.</p><script>alert(1)</script>',
		'bouton' => 'Candidater',
		'lien'   => array( 'url' => '#candidature' ),
		'fond'   => 'yes',
		'ancre'  => 'pourquoi',
	)
);
$ok( str_contains( $texte, 'id="pourquoi"' ) && str_contains( $texte, 'prose-section--carte' ) && str_contains( $texte, '<strong>concept</strong>' ) && ! str_contains( $texte, '<script' ), 'section texte : ancre, carte blanche, texte mis en forme et nettoyé' );
$ok( str_contains( $texte, 'href="#candidature"' ), 'section texte : bouton vers une ancre' );
$ok( '' === trim( $rendu( 'bigm-texte', array() ) ), 'section texte vide : rien sur le site' );
$chiffres = $rendu( 'bigm-chiffres', array() );
$ok( 5 === substr_count( $chiffres, 'class="chiffre"' ) && str_contains( $chiffres, esc_html( bigm_reglage_texte( 'franchise_droit_entree' ) ) ), 'chiffres : les 5 chiffres des Réglages Big M, à la française' );
$ok( 2 === substr_count( $rendu( 'bigm-chiffres', array( 'chiffres' => array( 'franchise_duree', 'franchise_formation' ) ) ), 'class="chiffre"' ), 'chiffres : seulement ceux choisis' );
$commande = bigm_rendu_entete_carte(
	array(
		'titre'  => 'T',
		'bouton' => 'Commander',
	)
);
$postuler = bigm_rendu_entete_carte(
	array(
		'titre'  => 'T',
		'bouton' => 'Postuler',
		'lien'   => '#candidature',
	)
);
$ok( str_contains( $commande, '<svg' ) && ! str_contains( $postuler, '<svg' ) && str_contains( $postuler, 'href="#candidature"' ), 'en-tête de page : icône de sac seulement pour Commander' );

WP_CLI::log( 'Actualités' );
$presse = wp_insert_term( 'Presse test', 'category', array( 'slug' => 'test-presse' ) );
$news   = wp_insert_term( 'News test', 'category', array( 'slug' => 'test-news' ) );
$termes = array( (int) $presse['term_id'], (int) $news['term_id'] );
foreach ( array( array( 'Article A', $termes[0], '2026-10-01 10:00:00' ), array( 'Article B', $termes[0], '2026-10-02 10:00:00' ), array( 'Article C', $termes[1], '2026-10-03 10:00:00' ), array( 'Sans catégorie', (int) get_option( 'default_category' ), '2026-10-04 10:00:00' ) ) as $a ) {
	$posts[] = wp_insert_post(
		array(
			'post_title'    => $a[0],
			'post_status'   => 'publish',
			'post_date'     => $a[2],
			'post_category' => array( $a[1] ),
			'post_content'  => '<p>Texte de ' . $a[0] . '</p>',
		)
	);
}
$carte = bigm_carte_article( $posts[0] );
$ok( str_contains( $carte, 'class="news-card"' ) && str_contains( $carte, 'Presse test · ' . get_the_date( 'j F Y', $posts[0] ) ), 'carte d’article : catégorie et date' );
$ok( str_contains( bigm_carte_article( $posts[3] ), '<small>' . get_the_date( 'j F Y', $posts[3] ) . '</small>' ), 'catégorie par défaut (« Non classé ») non affichée' );
$page( array( 'cat' => $termes[0] ) );
$liste = bigm_page_actualites();
$ok( str_contains( $liste, '<h1>Presse test</h1>' ) && 2 === substr_count( $liste, 'class="news-card"' ), 'catégorie : titre et ses articles' );
$ok( (bool) preg_match( '#href="' . preg_quote( esc_url( get_category_link( $termes[0] ) ), '#' ) . '" aria-current="page"#', $liste ) && str_contains( $liste, '>Tout</a>' ), 'pastilles : Tout, catégories, la catégorie affichée allumée' );
$ok( ! str_contains( $liste, '>' . esc_html( get_cat_name( (int) get_option( 'default_category' ) ) ) . '</a>' ), 'pastilles : sans la catégorie par défaut' );
$ok( in_array( 'bigm-articles', $assets(), true ), 'catégorie : styles des actualités' );
$page( array( 'p' => $posts[1] ) );
$GLOBALS['wp_query']->the_post();
$article = bigm_page_article();
wp_reset_postdata();
$ok( str_contains( $article, 'Toutes les actualités' ) && str_contains( $article, '<h1>Article B</h1>' ) && str_contains( $article, 'Texte de Article B' ), 'article : retour, titre, texte' );
// Le seul autre article de la même catégorie (A) vient en premier, même s'il est plus ancien.
preg_match_all( '#<h3>(.*?)</h3>#', (string) strstr( $article, 'À lire aussi' ), $plus );
$ok( 3 === count( $plus[1] ) && 'Article A' === $plus[1][0] && ! in_array( 'Article B', $plus[1], true ), 'à lire aussi : 3 articles, sans l’article lui-même, sa catégorie d’abord' );

$page( array( 'post_type' => 'post' ) );
$GLOBALS['wp_query']->is_home = true;
$pastilles = do_shortcode( '[bigm_categories_articles]' );
$ok( '' === $pastilles || ( str_contains( $pastilles, 'class="news-tabs"' ) && str_contains( $pastilles, 'aria-current="page">Tout</a>' ) ), '[bigm_categories_articles] : pastilles Tout / catégories, « Tout » actif sur la liste' );
$ok( 'Lire l’article : Titre' === sprintf( __( 'Read more about %s', 'elementor-pro' ), 'Titre' ), 'widget Articles d’Elementor Pro : nom caché du lien « Lire l’article » en français' ); // phpcs:ignore WordPress.WP.I18n

WP_CLI::log( 'Formulaires (pages avec le widget Formulaire d’Elementor Pro)' );
$form_page = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_title'  => 'Test formulaire',
		'post_status' => 'publish',
	)
);
$posts[]   = $form_page;
update_post_meta( $form_page, '_elementor_data', wp_slash( wp_json_encode( array( array( 'elType' => 'widget', 'widgetType' => 'form' ) ) ) ) );
$page( array( 'page_id' => $form_page ) );
$a = $assets();
$ok( in_array( 'bigm-formulaires', $a, true ), 'page avec formulaire : habillage des formulaires' );
$page( array( 'p' => $posts[0] ) );
$ok( ! in_array( 'bigm-formulaires', $assets(), true ), 'page sans formulaire : pas d’habillage des formulaires' );
if ( function_exists( 'bigm_demo_formulaire' ) ) {
	$f      = bigm_demo_formulaire(
		'Test',
		'Sujet',
		'Envoyer',
		array(
			bigm_demo_champ(
				'fichiers',
				'upload',
				'CV',
				array(
					'file_types'      => 'pdf,docx',
					'file_sizes'      => 5,
					'max_files'       => 3,
					'attachment_type' => 'attach',
				)
			),
			bigm_demo_champ( 'email', 'email', 'E-mail' ),
		)
	);
	$types  = wp_list_pluck( $f['settings']['form_fields'], 'field_type' );
	$accept = $f['settings']['form_fields'][ array_search( 'acceptance', $types, true ) ];
	$ok( 'form' === $f['widgetType'] && in_array( 'honeypot', $types, true ) && in_array( 'recaptcha_v3', $types, true ), 'démo : formulaire Elementor Pro avec champ piège et reCAPTCHA v3' );
	$ok( 'true' === $accept['required'] && str_contains( $accept['acceptance_text'], 'politique-de-confidentialite' ), 'démo : consentement obligatoire, lien vers la politique de confidentialité' );
	$ok( array( 'email', 'save-to-database' ) === $f['settings']['submit_actions'] && 'email' === $f['settings']['email_reply_to'], 'démo : envoi par e-mail, soumissions enregistrées, réponse à l’e-mail saisi' );
	$ok( count( array_unique( wp_list_pluck( $f['settings']['form_fields'], '_id' ) ) ) === count( $f['settings']['form_fields'] ), 'démo : chaque champ a son identifiant de ligne' );
	$ok( '#faf8f5' === $f['settings']['field_background_color'] && '#c8400a' === $f['settings']['button_background_color'] && 'Fredoka' === $f['settings']['button_font_family'], 'démo : couleurs et polices de la charte dans les réglages du formulaire' );
} else {
	WP_CLI::log( '  --    bigm-demo absent : formulaires de la démo non vérifiés' );
}

WP_CLI::log( 'Restaurants (plugin StoreLocatore)' );
if ( function_exists( 'bigm_storelocator_actif' ) && bigm_storelocator_actif() ) {
	$type = get_post_type_object( 'tfmsl_store' );
	$ok( 'restaurants' === $type->rewrite['slug'] && ! $type->has_archive && 'Restaurants' === $type->labels->name, 'fiches sous /restaurants/…, pas de liste du plugin, libellés « Restaurants »' );
	$ok( 'site' === apply_filters( 'default_option_tfmsl_store_style', 'dark', 'tfmsl_store_style', true ) && '0' === apply_filters( 'default_option_tfmsl_mobile_bar', '1', 'tfmsl_mobile_bar', true ), 'plugin : fiche aux couleurs du site et barre mobile coupée par défaut' );
	$ok( 'Voir le restaurant' === __( 'Voir la boutique →', 'storelocatore' ) && 'Aucun restaurant ne correspond à cette recherche.' === __( 'Aucune boutique trouvée pour cette recherche.', 'storelocatore' ), 'textes du plugin : « restaurant » au lieu de « boutique »' );
	$resto   = wp_insert_post(
		array(
			'post_type'   => 'tfmsl_store',
			'post_title'  => 'Restaurant test',
			'post_status' => 'publish',
		)
	);
	$posts[] = $resto;
	$ok( 'Burgers halal' === get_post_meta( $resto, 'tfmsl_cuisine_type', true ), 'type de cuisine par défaut (données structurées « Restaurant »)' );
	update_post_meta( $resto, 'tfmsl_cuisine_type', 'Fast-food' );
	$ok( 'Fast-food' === get_post_meta( $resto, 'tfmsl_cuisine_type', true ), 'type de cuisine saisi : conservé' );
	$ok( str_starts_with( (string) get_permalink( $resto ), home_url( '/restaurants/' ) ), 'fiche d’un restaurant : /restaurants/{restaurant}/' );
	$page( array( 'p' => $posts[0] ) );
	$ok( ! in_array( 'tfmsl-public', $assets(), true ), 'page sans restaurant : fichiers du plugin (jQuery, Leaflet) non chargés' );
	$page( array( 'p' => $resto, 'post_type' => 'tfmsl_store' ) );
	$ok( in_array( 'tfmsl-public', $assets(), true ), 'fiche d’un restaurant : fichiers du plugin chargés' );
	$ok( null === bigm_seo_page( true ), 'fiche d’un restaurant : référencement laissé au plugin (titre, description, LocalBusiness)' );
} else {
	WP_CLI::log( '  --    StoreLocatore inactif : restaurants non vérifiés' );
}

wp_reset_postdata();
foreach ( $posts as $id ) {
	wp_delete_post( $id, true );
}
foreach ( $termes as $id ) {
	wp_delete_term( $id, 'category' );
}

if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'restaurants, actualités, contenus et formulaires : toutes les vérifications passent.' );
