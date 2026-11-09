<?php
/**
 * Test des jeux concours (bigm-core, inc/concours.php). Sur un site de test avec bigm-core (et
 * StoreLocatore pour les fiches des restaurants) :
 *
 *   wp eval-file tests/wp/test-bigm-concours.php --user=<administrateur>
 *
 * @package BigM\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( "À lancer avec wp eval-file.\n" );
}
if ( ! function_exists( 'bigm_concours' ) ) {
	WP_CLI::error( 'Il faut bigm-core 0.10 ou plus.' );
}

$echecs = 0;
$ok     = static function ( bool $cond, string $quoi ) use ( &$echecs ): void {
	if ( $cond ) {
		WP_CLI::log( '  ok    ' . $quoi );
	} else {
		++$echecs;
		WP_CLI::warning( 'ÉCHEC ' . $quoi );
	}
};
$a_supprimer = array();

WP_CLI::log( 'Champs' );
$ok( post_type_exists( 'bigm_concours' ) && ! get_post_type_object( 'bigm_concours' )->public && get_post_type_object( 'bigm_concours' )->show_in_rest, 'type de contenu « Jeux concours » : privé, dans l’admin et l’API REST' );
$ok( '2026-11-22 23:59' === bigm_concours_nettoyer( 'fin', '2026-11-22T23:59' ) && '' === bigm_concours_nettoyer( 'fin', 'demain' ), 'dates : format du champ datetime-local accepté, texte libre refusé' );
$ok( '#2a1650' === bigm_concours_nettoyer( 'couleur1', '#2a1650' ) && '' === bigm_concours_nettoyer( 'couleur1', 'violet' ), 'couleurs : hexadécimal seulement' );
$ok( array( 3, 7 ) === bigm_concours_nettoyer( 'restaurants', array( '3', '0', '7', '3' ) ), 'restaurants : identifiants, sans doublon ni zéro' );
$ok( '' === bigm_concours_nettoyer( 'lien', 'javascript:alert(1)' ), 'lien pour jouer : adresse web seulement' );

WP_CLI::log( 'Données' );
$restos = post_type_exists( 'tfmsl_store' ) ? get_posts(
	array(
		'post_type'      => 'tfmsl_store',
		'post_status'    => 'publish',
		'posts_per_page' => 2,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
) : array();
$jeu    = wp_insert_post(
	array(
		'post_type'   => 'bigm_concours',
		'post_status' => 'publish',
		'post_title'  => 'Jeu de test',
	)
);
$a_supprimer[] = $jeu;
$champs        = array(
	'partenaire'  => 'Boisson test',
	'accroche'    => 'Des lots à gagner.',
	'condition'   => 'Avec obligation d’achat.',
	'debut'       => '2026-09-15 08:00',
	'fin'         => '2026-11-22 23:59',
	'tirage'      => '2026-11-23',
	'lien'        => 'https://exemple.test/jeu',
	'bouton'      => 'Je joue',
	'etapes'      => "Commandez : un menu.\nScannez : le QR code.\nUne étape sans titre",
	'lots'        => "15 | Jeux | Instants gagnants\n*1 | Casque | Tirage au sort",
	'faq'         => "Faut-il acheter ?\nOui.\n\nQui peut jouer ?\nLes majeurs.\nEn France.",
	'mentions'    => 'Organisé par une société test.',
	'restaurants' => $restos ? array( $restos[0] ) : array(),
);
foreach ( $champs as $cle => $v ) {
	update_post_meta( $jeu, '_bigm_jc_' . $cle, bigm_concours_nettoyer( $cle, $v ) );
}
$c = bigm_concours( $jeu );
$ok( $c && ( new DateTimeImmutable( '2026-11-22 23:59:59', new DateTimeZone( 'Europe/Paris' ) ) )->getTimestamp() === $c['fin_ts'] || 'Europe/Paris' !== wp_timezone_string(), 'fin : 23:59:59, heure de Paris' );
$ok( 3 === count( $c['etapes_liste'] ) && 'Commandez' === $c['etapes_liste'][0]['titre'] && 'Un menu.' === $c['etapes_liste'][0]['texte'] && '' === $c['etapes_liste'][2]['titre'], 'étapes : « Titre : texte », majuscule au début du texte, titre facultatif' );
$ok( 2 === count( $c['lots_liste'] ) && '15' === $c['lots_liste'][0]['quantite'] && ! $c['lots_liste'][0]['principal'] && $c['lots_liste'][1]['principal'] && 'Casque' === $c['lots_liste'][1]['nom'], 'lots : « Quantité | Lot | Précision », étoile pour le lot principal' );
$ok( 2 === count( $c['faq_liste'] ) && "Les majeurs.\nEn France." === $c['faq_liste'][1]['reponse'], 'questions : un bloc par question, réponse sur plusieurs lignes' );
$avant   = ( new DateTimeImmutable( '2026-09-01 12:00', wp_timezone() ) )->getTimestamp();
$pendant = ( new DateTimeImmutable( '2026-11-10 12:00', wp_timezone() ) )->getTimestamp();
$apres   = ( new DateTimeImmutable( '2026-11-23 00:00:30', wp_timezone() ) )->getTimestamp();
$ok( 'a_venir' === bigm_concours_etat( $c, $avant ) && 'en_cours' === bigm_concours_etat( $c, $pendant ) && 'termine' === bigm_concours_etat( $c, $apres ), 'état : à venir, en cours, terminé (à la seconde près)' );
$ids = static fn( array $jeux ): array => wp_list_pluck( $jeux, 'id' );
$ok( in_array( $jeu, $ids( bigm_concours_a_montrer( 30, 30, $pendant ) ), true ) && ! in_array( $jeu, $ids( bigm_concours_a_montrer( 30, 30, $apres + 31 * DAY_IN_SECONDS ) ), true ) && ! in_array( $jeu, $ids( bigm_concours_a_montrer( 7, 30, $avant ) ), true ), 'page : jeu montré pendant, plus 30 jours après la fin, pas plus de N jours avant' );

WP_CLI::log( 'Page Jeu concours' );
$c['etat'] = 'en_cours';
$html      = bigm_rendu_concours( $c );
$ok( 1 === substr_count( $html, '<h1' ) && str_contains( $html, 'Jeu de test' ), 'un seul titre h1 : le nom du jeu' );
$ok( str_contains( $html, 'data-fin="' . gmdate( 'c', $c['fin_ts'] ) . '"' ) && str_contains( $html, 'data-jc-compte' ), 'compte à rebours : date de fin dans le HTML (pages en cache), calculé par le JS' );
$ok( (bool) preg_match( '#<a class="btn jc-btn" href="https://exemple\.test/jeu" target="_blank" rel="noopener" data-jc-jouer>#', $html ) && str_contains( $html, '(nouvel onglet)' ), 'bouton pour jouer : nouvel onglet annoncé' );
$ok( str_contains( $html, 'jc-lot--principal' ) && str_contains( $html, 'Lot principal' ) && str_contains( $html, 'data-jc-compteur="15"' ), 'lots : lot principal mis en avant, nombres animés' );
$ok( 2 === substr_count( $html, '<details class="jc-faq__item">' ), 'questions : sections dépliables' );
if ( $restos ) {
	$ok( 1 === substr_count( $html, 'data-jc-resto' ) && str_contains( $html, get_permalink( $restos[0] ) ), 'restaurants participants : lien vers leur fiche, recherche' );
}
$ok( str_contains( bigm_rendu_page_concours( 999999999, 'Rien', 'Revenez bientôt.' ), 'Revenez bientôt.' ), 'sans jeu : titre et texte d’attente' );

WP_CLI::log( 'Fiches des restaurants' );
if ( $restos && count( $restos ) > 1 ) {
	$ok( in_array( $jeu, $ids( bigm_concours_du_restaurant( $restos[0], $pendant ) ), true ) && ! in_array( $jeu, $ids( bigm_concours_du_restaurant( $restos[1], $pendant ) ), true ), 'bande seulement sur les fiches des restaurants participants' );
	$ok( ! in_array( $jeu, $ids( bigm_concours_du_restaurant( $restos[0], $apres + 2 * DAY_IN_SECONDS ) ), true ), 'bande retirée après la fin (le JS la cache dès la fin)' );
	$bande = bigm_rendu_concours_bande( array_merge( $c, array( 'etat' => 'a_venir' ) ), $restos[0] );
	$ok( str_contains( $bande, 'data-jc-bande' ) && (bool) preg_match( '#<section class="jc-bande"[^>]* hidden#', $bande ), 'bande d’un jeu qui n’a pas commencé : cachée, montrée par le JS au début' );
	update_post_meta( $jeu, '_bigm_jc_tous', '1' );
	$ok( count( bigm_concours( $jeu )['restaurants'] ) === count( get_posts( array( 'post_type' => 'tfmsl_store', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) ) ), '« Tous les restaurants » : toutes les fiches publiées' );
	update_post_meta( $jeu, '_bigm_jc_tous', '0' );
} else {
	WP_CLI::log( '  --    moins de deux restaurants : fiches non testées' );
}

WP_CLI::log( 'Bannière et bouton flottant' );
$banniere = bigm_rendu_concours_bande( $c, 0 );
$ok( str_contains( $banniere, 'jc-bande--banniere' ) && str_contains( $banniere, 'Jeu concours avec Boisson test' ) && ! str_contains( $banniere, 'dans ce restaurant' ), 'bannière (accueil) : même bande, sans « dans ce restaurant »' );
$bouton = bigm_rendu_concours_bouton( $c );
$ok( (bool) preg_match( '#<aside class="jc-flottant"[^>]* hidden>#', $bouton ) && str_contains( $bouton, 'data-fin="' ) && str_contains( $bouton, 'data-jc-fermer' ) && str_contains( $bouton, 'aria-label="Fermer : ne plus afficher ce bouton"' ), 'bouton flottant : caché dans le HTML (le JS le montre pendant le jeu), croix nommée' );
$ok( str_contains( $bouton, 'href="' . esc_url( bigm_concours_page_url() ) . '"' ), 'bouton flottant : lien vers la page du jeu' );
if ( function_exists( 'bigm_cookies_categories' ) ) {
	$ok( str_contains( wp_json_encode( bigm_cookies_categories()['necessaires']['cookies'] ), 'bigm_jeu_ferme_' ), 'bandeau des cookies : bouton fermé listé avec les stockages nécessaires' );
}

WP_CLI::log( 'Cache' );
update_post_meta( $jeu, '_bigm_jc_debut', gmdate( 'Y-m-d H:i', time() + DAY_IN_SECONDS + (int) wp_timezone()->getOffset( new DateTime() ) ) );
update_post_meta( $jeu, '_bigm_jc_fin', gmdate( 'Y-m-d H:i', time() + 3 * DAY_IN_SECONDS + (int) wp_timezone()->getOffset( new DateTime() ) ) );
bigm_concours_programmer( $jeu );
$prevus = array();
foreach ( (array) _get_cron_array() as $quand => $taches ) {
	foreach ( $taches['bigm_concours_changement'] ?? array() as $tache ) {
		if ( (int) $tache['args'][0] === $jeu ) {
			$prevus[] = $quand;
		}
	}
}
$ok( 2 === count( $prevus ), 'cache vidé au début et à la fin du jeu (tâches planifiées)' );

WP_CLI::log( 'Référencement' );
$page_jeu      = wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'Page jeu test',
	)
);
$a_supprimer[] = $page_jeu;
update_post_meta( $page_jeu, '_elementor_data', wp_slash( '[{"elType":"widget","widgetType":"bigm-jeu-concours","settings":{"jeu":"0"}}]' ) );
update_post_meta( $page_jeu, '_bigm_seo_description', 'Aucun jeu en cours pour le moment.' );
$GLOBALS['wp_query']     = new WP_Query( array( 'page_id' => $page_jeu ) );
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
$seo                     = function_exists( 'bigm_seo_page' ) ? bigm_seo_page( true ) : null;
$ok( bigm_concours_page_du_jeu( $page_jeu ) && null === bigm_concours_bouton_jeu(), 'page du jeu reconnue : pas de bouton flottant dessus' );
$montre = array_values( array_filter( bigm_concours_a_montrer(), static fn( $j ) => 'termine' !== $j['etat'] ) )[0] ?? null;
$ok( $seo && $montre && str_starts_with( $seo['description'], html_entity_decode( $montre['titre'], ENT_QUOTES ) . ' jusqu’au ' ) && 'Aucun jeu en cours pour le moment.' !== $seo['description'], 'page du jeu : description du jeu affiché, à la place de celle saisie pour la page sans jeu' );
wp_reset_query();
wp_update_post(
	array(
		'ID'          => $jeu,
		'post_status' => 'draft',
	)
);
bigm_concours_programmer( $jeu );
$reste = 0;
foreach ( (array) _get_cron_array() as $taches ) {
	foreach ( $taches['bigm_concours_changement'] ?? array() as $tache ) {
		$reste += (int) ( (int) $tache['args'][0] === $jeu );
	}
}
$ok( 0 === $reste, 'jeu dépublié : plus de vidage prévu' );

foreach ( $a_supprimer as $id ) {
	wp_delete_post( $id, true );
}
if ( $echecs ) {
	WP_CLI::error( "$echecs vérification(s) en échec." );
}
WP_CLI::success( 'Jeux concours : toutes les vérifications passent.' );
