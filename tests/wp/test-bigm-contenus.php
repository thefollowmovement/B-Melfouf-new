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

	$maj = new WP_REST_Request( 'POST', '/wp/v2/tfmsl_store/' . $resto );
	$maj->set_body_params( array( 'meta' => array( 'tfmsl_gmb_place_id' => ' ChIJ_test-123 ' ) ) );
	$ok( 200 === rest_do_request( $maj )->get_status() && 'ChIJ_test-123' === get_post_meta( $resto, 'tfmsl_gmb_place_id', true ) && 'Restaurant test' === get_the_title( $resto ), 'Place ID modifiable par l’API REST (nettoyé), fiche inchangée' );
	$maj->set_body_params( array( 'meta' => array( 'tfmsl_lat' => '48.90310123456', 'tfmsl_lng' => '2,4828' ) ) );
	$ok( 200 === rest_do_request( $maj )->get_status() && '48.9031012' === get_post_meta( $resto, 'tfmsl_lat', true ) && '2.4828' === get_post_meta( $resto, 'tfmsl_lng', true ), 'coordonnées GPS modifiables par l’API REST (7 décimales, virgule acceptée)' );
	$moi = get_current_user_id();
	wp_set_current_user( 0 );
	$maj->set_body_params( array( 'meta' => array( 'tfmsl_gmb_place_id' => 'ChIJpirate' ) ) );
	rest_do_request( $maj );
	$ok( 'ChIJ_test-123' === get_post_meta( $resto, 'tfmsl_gmb_place_id', true ), 'Place ID : fermé aux visiteurs' );
	wp_set_current_user( $moi );

	$cle_avant = get_option( 'tfmsl_google_api_key', false );
	delete_option( 'tfmsl_google_api_key' );
	$ok( is_wp_error( bigm_restaurant_place_candidats( $resto ) ), 'Place ID possibles : erreur claire sans clé Google' );
	update_option( 'tfmsl_google_api_key', 'CLE-TEST' );
	update_post_meta( $resto, 'tfmsl_lat', '48.9022' );
	update_post_meta( $resto, 'tfmsl_lng', '2.4828' );
	$appel  = array();
	$google = static function ( $pre, $args, $url ) use ( &$appel ) {
		if ( ! str_contains( (string) $url, 'places.googleapis.com' ) ) {
			return $pre;
		}
		$appel = array( 'cle' => $args['headers']['X-Goog-Api-Key'] ?? '', 'corps' => json_decode( (string) $args['body'], true ) );
		return array(
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'places' => array( array( 'id' => 'ChIJabc', 'displayName' => array( 'text' => 'Big M - Bondy' ), 'formattedAddress' => '24 Rue Jules Guesde, 93140 Bondy, France', 'location' => array( 'latitude' => 48.9031, 'longitude' => 2.4828 ), 'businessStatus' => 'OPERATIONAL' ) ) ) ),
			'cookies'  => array(),
		);
	};
	add_filter( 'pre_http_request', $google, 10, 3 );
	$reponse = rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/restaurants/' . $resto . '/place-id' ) );
	remove_filter( 'pre_http_request', $google, 10 );
	$c = $reponse->get_data()['candidats'][0] ?? array();
	$ok( 200 === $reponse->get_status() && 'ChIJabc' === ( $c['place_id'] ?? '' ) && 100 === ( $c['distance_m'] ?? 0 ) && 48.9031 === ( $c['lat'] ?? null ) && 'CLE-TEST' === ( $appel['cle'] ?? '' ) && str_starts_with( (string) ( $appel['corps']['textQuery'] ?? '' ), 'Big M ' ) && isset( $appel['corps']['locationBias'] ), 'Place ID possibles : recherche Google avec la clé du plugin, près de la fiche, distance calculée' );
	add_filter( 'pre_http_request', $google, 10, 3 );
	$req = new WP_REST_Request( 'GET', '/bigm/v1/restaurants/' . $resto . '/place-id' );
	$req->set_query_params( array( 'q' => 'Big Canteen Mérignac' ) );
	rest_do_request( $req );
	remove_filter( 'pre_http_request', $google, 10 );
	$ok( 'Big Canteen Mérignac' === ( $appel['corps']['textQuery'] ?? '' ), 'Place ID possibles : recherche libre (?q=)' );
	false === $cle_avant ? delete_option( 'tfmsl_google_api_key' ) : update_option( 'tfmsl_google_api_key', $cle_avant );

	WP_CLI::log( 'Food court (Big Canteen)' );
	$ok( taxonomy_exists( 'bigm_food_court' ) && '' === bigm_rendu_food_court( $resto ), 'food courts déclarés ; restaurant hors food court : pas de bloc' );
	$fc = wp_insert_term( 'Big Canteen test', 'bigm_food_court', array( 'description' => 'Plusieurs enseignes du groupe sous un même toit.' ) );
	$fc = is_wp_error( $fc ) ? 0 : (int) $fc['term_id'];
	update_term_meta( $fc, '_bigm_fc_marques', "Big M\nFatburger\nBuffalo's Café" );
	update_term_meta( $fc, '_bigm_fc_site', 'https://bigcanteen.example.test/' );
	wp_set_object_terms( $resto, array( $fc ), 'bigm_food_court' );
	$bloc = bigm_rendu_food_court( $resto );
	$ok( str_contains( $bloc, 'Ce Big M est au Big Canteen test' ) && 3 === substr_count( $bloc, '<li>' ) && str_contains( $bloc, 'Plusieurs enseignes' ) && str_contains( $bloc, 'href="https://bigcanteen.example.test/"' ), 'bloc Food court : nom, présentation, marques du food court, site' );
	update_post_meta( $resto, '_bigm_fc_marques', "Big M\nADS" );
	$bloc = bigm_rendu_food_court( $resto );
	$ok( 2 === substr_count( $bloc, '<li>' ) && str_contains( $bloc, '<li>ADS</li>' ), 'marques sur place propres au restaurant' );
	$page( array( 'p' => $resto, 'post_type' => 'tfmsl_store' ) );
	$GLOBALS['wp_query']->is_singular = true;
	$ok( in_array( 'bigm-food-court', $assets(), true ), 'fiche d’un restaurant de food court : CSS du bloc chargé' );
	ob_start();
	do_action( 'tfmsl_after_store_hero', $resto );
	$ok( str_contains( (string) ob_get_clean(), 'class="bigm-fc"' ), 'bloc affiché sous le bandeau de la fiche (crochet de StoreLocatore)' );
	wp_delete_term( $fc, 'bigm_food_court' );

	WP_CLI::log( 'Fenêtre Commander' );
	$ok( 'https://www.ubereats.com/fr/brand/big-m?diningMode=PICKUP' === bigm_lien_commande_propre( 'https://www.ubereats.com/fr-en/brand/big-m?srsltid=AYCX9v&utm_source=google&diningMode=PICKUP' ), 'lien nettoyé : sans srsltid ni utm_, page française d’Uber Eats, autres paramètres gardés' );
	$ok( 'https://deliveroo.fr/fr-en/menu/x' === bigm_lien_commande_propre( 'https://deliveroo.fr/fr-en/menu/x?gclid=1' ), 'lien nettoyé : /fr-en/ seulement chez Uber Eats' );
	$ok( 'https://www.ubereats.com/fr/store/big-m-agen/TD7b?diningMode=DELIVERY' === bigm_lien_commande_propre( 'https://www.ubereats.com/fr/store/big-m-agen/TD7b?diningMode=DELIVERY&pl=JTdCJTIy&ps=1' ) && 'https://deliveroo.fr/fr/menu/angers/doutre/big-m-angers' === bigm_lien_commande_propre( 'https://deliveroo.fr/fr/menu/angers/doutre/big-m-angers?geohash=gbrw43&day=today&time=ASAP' ), 'lien nettoyé : sans l’adresse ni l’heure de livraison de la personne qui l’a copié' );
	update_post_meta( $resto, 'tfmsl_order_url', 'https://www.ubereats.com/fr-en/store/big-m-test/abc' );
	update_post_meta( $resto, 'tfmsl_phone', '01 23 45 67 89' );
	$c = bigm_restaurant_commande( $resto );
	$ok( 'https://www.ubereats.com/fr/store/big-m-test/abc' === ( $c['liens']['ubereats'] ?? '' ) && '' === $c['autre'] && '' === $c['tel'], 'lien Commander du plugin rangé sous Uber Eats ; pas d’appel sans la case' );
	update_post_meta( $resto, 'tfmsl_order_url', 'https://commande.example.test/bigm' );
	$c = bigm_restaurant_commande( $resto );
	$ok( empty( $c['liens'] ) && 'https://commande.example.test/bigm' === $c['autre'], 'lien Commander d’une autre plateforme : « Commander en ligne »' );
	bigm_restaurant_commande_enregistrer(
		$resto,
		array(
			'liens' => array(
				'deliveroo' => 'https://deliveroo.fr/fr/menu/paris/test?utm_medium=x',
				'inconnue'  => 'https://ailleurs.example/',
			),
			'tel'   => '1',
		)
	);
	$c = bigm_restaurant_commande( $resto );
	$ok( array( 'deliveroo' => 'https://deliveroo.fr/fr/menu/paris/test' ) === $c['liens'] && '01 23 45 67 89' === $c['tel'], 'liens saisis (nettoyés, plateformes connues seulement) ; appel au numéro de la fiche' );
	bigm_restaurant_commande_enregistrer( $resto, array( 'liens' => array( 'deliveroo' => 'https://deliveroo.fr/fr/menu/paris/test' ), 'tel' => '1', 'numero' => '06 00 00 00 00' ) );
	$donnees = bigm_commande_donnees();
	$ligne   = current( wp_list_filter( $donnees['restaurants'], array( 'id' => $resto ) ) );
	$ok( $ligne && '06 00 00 00 00' === $ligne['tel'] && 'Restaurant test' === $ligne['nom'] && str_starts_with( $ligne['url'], home_url( '/restaurants/' ) ), 'données de la fenêtre : restaurant, numéro saisi, fiche' );
	$ok( wp_list_pluck( bigm_commande_plateformes(), 'id' ) === wp_list_pluck( $donnees['plateformes'], 'id' ), 'données de la fenêtre : plateformes des réglages, dans leur ordre' );
	$reponse = rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/commander' ) );
	$ok( 200 === $reponse->get_status() && str_contains( (string) ( $reponse->get_headers()['Cache-Control'] ?? '' ), 'public' ), 'GET /wp-json/bigm/v1/commander : public, mis en cache 5 minutes' );
	$envoi = new WP_REST_Request( 'POST', '/bigm/v1/commander/restaurants' );
	$envoi->set_body_params( array( 'restaurants' => array( $resto => array( 'liens' => array( 'ubereats' => 'https://www.ubereats.com/fr-en/store/big-m-test/xyz?srsltid=1' ) ) ) ) );
	$r = rest_do_request( $envoi );
	$c = bigm_restaurant_commande( $resto );
	$ok( 200 === $r->get_status() && 'https://www.ubereats.com/fr/store/big-m-test/xyz' === ( $c['liens']['ubereats'] ?? '' ) && 'https://deliveroo.fr/fr/menu/paris/test' === ( $c['liens']['deliveroo'] ?? '' ) && '06 00 00 00 00' === $c['tel'], 'POST /bigm/v1/commander/restaurants : lien ajouté et nettoyé, autres réglages gardés' );
	$envoi->set_body_params( array( 'restaurants' => array( $resto => array( 'liens' => array( 'deliveroo' => '' ), 'tel' => '' ) ) ) );
	rest_do_request( $envoi );
	$c = bigm_restaurant_commande( $resto );
	$ok( array( 'ubereats' ) === array_keys( $c['liens'] ) && '' === $c['tel'], 'lien vide et case décochée : bouton retiré' );
	$lecture = rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/commander/restaurants' ) );
	$ok( 200 === $lecture->get_status() && (bool) wp_list_filter( $lecture->get_data(), array( 'id' => $resto ) ), 'GET /bigm/v1/commander/restaurants : liens saisis' );
	$anonyme = get_current_user_id();
	wp_set_current_user( 0 );
	$ok( 401 === rest_do_request( $envoi )->get_status() && 401 === rest_do_request( new WP_REST_Request( 'GET', '/bigm/v1/commander/restaurants' ) )->get_status(), 'liens saisis : fermés aux visiteurs' );
	wp_set_current_user( $anonyme );
	update_post_meta( $resto, 'tfmsl_order_url', 'https://www.ubereats.com/fr/store/big-m-test/xyz/' );
	$c = bigm_restaurant_commande( $resto );
	$ok( '' === $c['autre'] && 1 === count( $c['liens'] ), 'lien StoreLocatore vers une plateforme déjà remplie : pas de « Commander en ligne » en double' );
	update_post_meta( $resto, 'tfmsl_order_url', 'https://commande.example.test/bigm' );
	$plateformes = bigm_commande_nettoyer_plateformes(
		array(
			array( 'id' => 'ubereats', 'nom' => 'Uber Eats (nouveau nom)', 'couleur' => '#06c167' ),
			array( 'id' => '', 'nom' => 'Just Eat', 'couleur' => 'rouge' ),
			array( 'id' => '', 'nom' => 'Just Eat' ),
			array( 'id' => '', 'nom' => '' ),
		)
	);
	$ok( array( 'ubereats', 'justeat', 'justeat2' ) === wp_list_pluck( $plateformes, 'id' ) && '#2b1406' === $plateformes[1]['couleur'], 'plateformes : identifiant gardé au renommage, créé pour une nouvelle, sans doublon ; ligne vide retirée' );
	$ok( bigm_commande_lien_vers( 'https://www.just-eat.fr/menu/big-m', bigm_commande_domaines( $plateformes[1] ) ), 'plateforme ajoutée reconnue par son nom (just-eat.fr)' );
	$page( array( 'p' => $posts[0] ) );
	$ok( in_array( 'bigm-commander', $assets(), true ) && str_contains( (string) wp_scripts()->get_inline_script_data( 'bigm-commander', 'before' ), 'bigm/v1/commander' ), 'script de la fenêtre sur toutes les pages, avec l’adresse des données' );
	$avant = get_option( 'bigm_reglages', array() );
	update_option( 'bigm_reglages', array_merge( (array) $avant, array( 'commande_fenetre' => '0' ) ) );
	$ok( ! in_array( 'bigm-commander', $assets(), true ), 'fenêtre désactivée : pas de script, les boutons gardent le lien' );
	update_option( 'bigm_reglages', $avant );
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
