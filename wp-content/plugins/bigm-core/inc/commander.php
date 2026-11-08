<?php
/**
 * Fenêtre « Commander ».
 *
 * Les boutons Commander du site (en-tête, barre d'onglets, fiches produit, widgets) ouvrent une
 * fenêtre : le visiteur choisit son restaurant (géolocalisation ou recherche), puis commande sur
 * l'une des plateformes du restaurant (Uber Eats, Deliveroo…) ou par téléphone si le restaurant
 * livre lui-même.
 *
 * - Plateformes : Réglages Big M → Commande (nom, texte, couleur, logo, ordre).
 * - Liens de chaque restaurant : bloc « Commander » de la fiche du restaurant, ou
 *   Restaurants → Liens de commande (tous les restaurants sur une page). Sans lien saisi, le
 *   lien Commander de StoreLocatore (tfmsl_order_url) est rangé sous sa plateforme
 *   (ubereats.com → Uber Eats…), ou proposé en « Commander en ligne ».
 * - Le restaurant choisi reste dans le navigateur (localStorage, clé tfmsl_promo_store de
 *   StoreLocatore) : les promos du plugin s'ouvrent sur lui, et inversement.
 * - Données : GET /wp-json/bigm/v1/commander, lu à la première ouverture.
 * - Sans JavaScript, ou fenêtre désactivée, les boutons gardent le lien Commander par défaut.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plateformes proposées au départ.
 *
 * @return array<int, array{id: string, nom: string, texte: string, couleur: string, logo: int}>
 */
function bigm_commande_plateformes_defaut(): array {
	return array(
		array(
			'id'      => 'ubereats',
			'nom'     => 'Uber Eats',
			'texte'   => 'Livraison',
			'couleur' => '#06c167',
			'logo'    => 0,
		),
		array(
			'id'      => 'deliveroo',
			'nom'     => 'Deliveroo',
			'texte'   => 'Livraison',
			'couleur' => '#00ccbc',
			'logo'    => 0,
		),
	);
}

/**
 * Plateformes de commande, dans l'ordre des réglages.
 *
 * @return array<int, array{id: string, nom: string, texte: string, couleur: string, logo: int}>
 */
function bigm_commande_plateformes(): array {
	$liste = bigm_reglage( 'commande_plateformes' );
	return is_array( $liste ) ? array_values( $liste ) : bigm_commande_plateformes_defaut();
}

/**
 * Domaines des liens de chaque plateforme, pour ranger le lien Commander de StoreLocatore.
 * Une plateforme ajoutée dans les réglages est reconnue par le domaine de son nom
 * (« Just Eat » → just-eat, justeat).
 *
 * @param array{id: string, nom: string} $plateforme Plateforme.
 * @return string[]
 */
function bigm_commande_domaines( array $plateforme ): array {
	$connus = array(
		'ubereats'  => array( 'ubereats.com' ),
		'deliveroo' => array( 'deliveroo.fr', 'deliveroo.com', 'deliveroo.co.uk' ),
	);
	$nom    = sanitize_title( $plateforme['nom'] );
	$liste  = $connus[ $plateforme['id'] ] ?? array( $nom, str_replace( '-', '', $nom ) );
	return (array) apply_filters( 'bigm_commande_domaines', $liste, $plateforme );
}

/**
 * Le lien vise-t-il un de ces domaines (ou un sous-domaine) ?
 *
 * @param string   $url      Lien.
 * @param string[] $domaines Domaines, ou nom de domaine sans extension (« just-eat »).
 */
function bigm_commande_lien_vers( string $url, array $domaines ): bool {
	$hote = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $hote ) {
		return false;
	}
	foreach ( $domaines as $d ) {
		$d = strtolower( trim( (string) $d ) );
		if ( '' === $d ) {
			continue;
		}
		if ( str_contains( $d, '.' ) ? ( $hote === $d || str_ends_with( $hote, '.' . $d ) ) : (bool) preg_match( '/(^|\.)' . preg_quote( $d, '/' ) . '\.[a-z.]+$/', $hote ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Lien de commande nettoyé : sans paramètres de suivi (srsltid de Google, utm_…, gclid,
 * fbclid), et page française d'Uber Eats (/fr/ au lieu de /fr-en/).
 *
 * @param string $url Lien saisi.
 */
function bigm_lien_commande_propre( string $url ): string {
	$url = esc_url_raw( trim( $url ) );
	if ( '' === $url ) {
		return '';
	}
	$requete = (string) wp_parse_url( $url, PHP_URL_QUERY );
	if ( '' !== $requete ) {
		parse_str( $requete, $params );
		$suivi = array_filter(
			array_keys( $params ),
			static fn( $k ) => in_array( strtolower( (string) $k ), array( 'srsltid', 'gclid', 'fbclid', 'msclkid' ), true ) || str_starts_with( strtolower( (string) $k ), 'utm_' )
		);
		if ( $suivi ) {
			$url = remove_query_arg( $suivi, $url );
		}
	}
	if ( bigm_commande_lien_vers( $url, array( 'ubereats.com' ) ) ) {
		$url = (string) preg_replace( '#^(https?://[^/]+)/fr-en/#i', '$1/fr/', $url );
	}
	return $url;
}

/**
 * Plateformes envoyées par la page des réglages, nettoyées. Une ligne sans nom est retirée.
 *
 * @param mixed $lignes Lignes reçues.
 * @return array<int, array{id: string, nom: string, texte: string, couleur: string, logo: int}>
 */
function bigm_commande_nettoyer_plateformes( $lignes ): array {
	$out = array();
	$ids = array();
	foreach ( is_array( $lignes ) ? $lignes : array() as $l ) {
		$nom = sanitize_text_field( (string) ( $l['nom'] ?? '' ) );
		if ( '' === $nom ) {
			continue;
		}
		// L'identifiant ne change plus une fois créé : les liens des restaurants y sont rangés.
		$id = sanitize_key( (string) ( $l['id'] ?? '' ) );
		$id = '' !== $id ? $id : str_replace( '-', '', sanitize_title( $nom ) );
		$id = '' !== $id ? $id : 'plateforme';
		$base = $id;
		for ( $i = 2; in_array( $id, $ids, true ); $i++ ) {
			$id = $base . $i;
		}
		$ids[]   = $id;
		$couleur = sanitize_hex_color( (string) ( $l['couleur'] ?? '' ) );
		$out[]   = array(
			'id'      => $id,
			'nom'     => $nom,
			'texte'   => sanitize_text_field( (string) ( $l['texte'] ?? '' ) ),
			'couleur' => $couleur ? $couleur : '#2b1406',
			'logo'    => bigm_nettoyer_media( $l['logo'] ?? 0, 'image/' ),
		);
	}
	return $out;
}

/**
 * Façons de commander dans un restaurant.
 *
 * @param int $id Restaurant (tfmsl_store).
 * @return array{liens: array<string, string>, autre: string, tel: string}
 *   liens : plateforme => lien ; autre : lien Commander de StoreLocatore qui n'est d'aucune
 *   plateforme ; tel : numéro à appeler si le restaurant prend les commandes par téléphone.
 */
function bigm_restaurant_commande( int $id ): array {
	$saisis = get_post_meta( $id, '_bigm_commande_liens', true );
	$saisis = is_array( $saisis ) ? $saisis : array();
	$plugin = trim( (string) get_post_meta( $id, 'tfmsl_order_url', true ) );
	$liens  = array();
	foreach ( bigm_commande_plateformes() as $p ) {
		$lien = (string) ( $saisis[ $p['id'] ] ?? '' );
		if ( '' === $lien && '' !== $plugin && bigm_commande_lien_vers( $plugin, bigm_commande_domaines( $p ) ) ) {
			$lien   = $plugin;
			$plugin = '';
		}
		if ( '' !== $lien ) {
			$liens[ $p['id'] ] = bigm_lien_commande_propre( $lien );
		}
	}
	if ( '' !== $plugin && in_array( $plugin, $liens, true ) ) {
		$plugin = '';
	}
	$tel = '';
	if ( '1' === (string) get_post_meta( $id, '_bigm_commande_tel', true ) ) {
		$tel = trim( (string) get_post_meta( $id, '_bigm_commande_numero', true ) );
		$tel = '' !== $tel ? $tel : trim( (string) get_post_meta( $id, 'tfmsl_phone', true ) );
	}
	return array(
		'liens' => $liens,
		'autre' => '' !== $plugin ? bigm_lien_commande_propre( $plugin ) : '',
		'tel'   => $tel,
	);
}

/**
 * Enregistre les façons de commander d'un restaurant.
 *
 * @param int                  $id      Restaurant.
 * @param array<string, mixed> $donnees liens (plateforme => lien), tel ('1'), numero.
 */
function bigm_restaurant_commande_enregistrer( int $id, array $donnees ): void {
	$liens = array();
	foreach ( bigm_commande_plateformes() as $p ) {
		$lien = bigm_lien_commande_propre( (string) ( $donnees['liens'][ $p['id'] ] ?? '' ) );
		if ( '' !== $lien ) {
			$liens[ $p['id'] ] = $lien;
		}
	}
	// Les liens d'une plateforme retirée des réglages sont gardés, au cas où elle revient.
	$avant = get_post_meta( $id, '_bigm_commande_liens', true );
	foreach ( is_array( $avant ) ? $avant : array() as $pid => $lien ) {
		if ( ! in_array( $pid, wp_list_pluck( bigm_commande_plateformes(), 'id' ), true ) ) {
			$liens[ $pid ] = $lien;
		}
	}
	if ( $liens ) {
		update_post_meta( $id, '_bigm_commande_liens', $liens );
	} else {
		delete_post_meta( $id, '_bigm_commande_liens' );
	}
	if ( ! empty( $donnees['tel'] ) ) {
		update_post_meta( $id, '_bigm_commande_tel', '1' );
	} else {
		delete_post_meta( $id, '_bigm_commande_tel' );
	}
	if ( array_key_exists( 'numero', $donnees ) ) {
		$numero = sanitize_text_field( (string) $donnees['numero'] );
		if ( '' !== $numero ) {
			update_post_meta( $id, '_bigm_commande_numero', $numero );
		} else {
			delete_post_meta( $id, '_bigm_commande_numero' );
		}
	}
	delete_transient( 'bigm_commande_donnees' );
}

/**
 * Nom court d'un restaurant, comme les promos de StoreLocatore : sans le nom du site en tête
 * (« Big M Bondy » → « Bondy »).
 *
 * @param string $nom Nom du restaurant.
 */
function bigm_restaurant_nom_court( string $nom ): string {
	$site = trim( html_entity_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) );
	if ( '' !== $site && 0 === stripos( $nom, $site . ' ' ) ) {
		$court = ltrim( substr( $nom, strlen( $site ) ), " \t-–—:" );
		return '' !== $court ? $court : $nom;
	}
	return $nom;
}

/**
 * Données de la fenêtre : textes, plateformes, restaurants publiés.
 *
 * @return array{plateformes: array<int, array<string, string>>, restaurants: array<int, array<string, mixed>>}
 */
function bigm_commande_donnees(): array {
	$cache = get_transient( 'bigm_commande_donnees' );
	if ( is_array( $cache ) ) {
		return $cache;
	}
	$plateformes = array();
	foreach ( bigm_commande_plateformes() as $p ) {
		$plateformes[] = array(
			'id'      => $p['id'],
			'nom'     => $p['nom'],
			'texte'   => $p['texte'],
			'couleur' => $p['couleur'],
			'logo'    => $p['logo'] ? (string) wp_get_attachment_image_url( $p['logo'], 'thumbnail' ) : '',
		);
	}
	$restaurants = array();
	if ( bigm_storelocator_actif() ) {
		$ids = get_posts(
			array(
				'post_type'      => 'tfmsl_store',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			$m     = static fn( string $cle ): string => trim( (string) get_post_meta( $id, $cle, true ) );
			$nom   = html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' );
			$lat   = $m( 'tfmsl_lat' );
			$lng   = $m( 'tfmsl_lng' );
			$c     = bigm_restaurant_commande( $id );
			$restaurants[] = array(
				'id'      => $id,
				'nom'     => $nom,
				'court'   => bigm_restaurant_nom_court( $nom ),
				'adresse' => trim( $m( 'tfmsl_address' ) . ', ' . trim( $m( 'tfmsl_zip' ) . ' ' . $m( 'tfmsl_city' ) ), ' ,' ),
				'ville'   => $m( 'tfmsl_city' ),
				'cp'      => $m( 'tfmsl_zip' ),
				'lat'     => is_numeric( $lat ) ? (float) $lat : null,
				'lng'     => is_numeric( $lng ) ? (float) $lng : null,
				'url'     => (string) get_permalink( $id ),
				'liens'   => (object) $c['liens'],
				'autre'   => $c['autre'],
				'tel'     => $c['tel'],
			);
		}
	}
	$donnees = array(
		'plateformes' => $plateformes,
		'restaurants' => $restaurants,
	);
	set_transient( 'bigm_commande_donnees', $donnees, DAY_IN_SECONDS );
	return $donnees;
}

/*
 * Données à recalculer quand un restaurant ou les réglages changent.
 */
foreach ( array( 'save_post_tfmsl_store', 'deleted_post', 'trashed_post', 'untrashed_post' ) as $bigm_crochet ) {
	add_action( $bigm_crochet, static fn() => delete_transient( 'bigm_commande_donnees' ) );
}
unset( $bigm_crochet );
add_action( 'update_option_bigm_reglages', static fn() => delete_transient( 'bigm_commande_donnees' ) );
add_action( 'update_option_blogname', static fn() => delete_transient( 'bigm_commande_donnees' ) );

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/commander',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function () {
					$r = new WP_REST_Response( bigm_commande_donnees() );
					$r->header( 'Cache-Control', 'public, max-age=300' );
					return $r;
				},
			)
		);
	}
);

/**
 * La fenêtre est-elle active ?
 */
function bigm_commande_fenetre_active(): bool {
	return '1' === (string) bigm_reglage( 'commande_fenetre' );
}

/*
 * Script de la fenêtre, sur toutes les pages (le bouton Commander est dans l'en-tête).
 * Différé, sans dépendance ; son CSS n'est chargé qu'à la première ouverture.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! bigm_commande_fenetre_active() ) {
			return;
		}
		$v = static fn( string $rel ): string => is_file( BIGM_CORE_DIR . '/assets/' . $rel ) ? (string) filemtime( BIGM_CORE_DIR . '/assets/' . $rel ) : BIGM_CORE_VERSION;
		wp_enqueue_script(
			'bigm-commander',
			BIGM_CORE_URL . 'assets/js/commander.js',
			array(),
			$v( 'js/commander.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$config = array(
			'api'      => rest_url( 'bigm/v1/commander' ),
			'css'      => add_query_arg( 'ver', $v( 'css/commander.css' ), BIGM_CORE_URL . 'assets/css/commander.css' ),
			'defaut'   => (string) bigm_reglage( 'commander_url' ),
			// Liens qui ouvrent la fenêtre : le lien Commander, ou à défaut celui du thème (bande appli).
			'cibles'   => array( '' !== (string) bigm_reglage( 'commander_url' ) ? (string) bigm_reglage( 'commander_url' ) : home_url( '/#appli' ) ),
			'titre'    => (string) bigm_reglage( 'commande_titre' ),
			'texte'    => (string) bigm_reglage( 'commande_texte' ),
			'telTitre' => (string) bigm_reglage( 'commande_tel_titre' ),
			'telTexte' => (string) bigm_reglage( 'commande_tel_texte' ),
		);
		wp_add_inline_script( 'bigm-commander', 'window.bigmCommander = ' . wp_json_encode( $config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . ';', 'before' );
	},
	20
);

/*
 * Mise à jour 0.7 : lien Commander nettoyé (copié depuis Google : ?srsltid=…, page /fr-en/).
 */
add_action(
	'init',
	static function () {
		if ( get_option( 'bigm_commande_nettoye' ) ) {
			return;
		}
		$reglages = get_option( 'bigm_reglages' );
		if ( is_array( $reglages ) && ! empty( $reglages['commander_url'] ) ) {
			$propre = bigm_lien_commande_propre( (string) $reglages['commander_url'] );
			if ( $propre !== $reglages['commander_url'] ) {
				$reglages['commander_url'] = $propre;
				update_option( 'bigm_reglages', $reglages );
			}
		}
		update_option( 'bigm_commande_nettoye', 1, false );
	},
	5
);

/* ---------------------------------------------------------------------------------------------
 * Admin : bloc « Commander » de la fiche d'un restaurant.
 * ------------------------------------------------------------------------------------------ */

add_action(
	'add_meta_boxes_tfmsl_store',
	static function () {
		add_meta_box( 'bigm-commande', 'Commander (fenêtre du site)', 'bigm_commande_boite', 'tfmsl_store', 'side', 'default' );
	}
);

/**
 * Bloc « Commander » de la fiche d'un restaurant.
 *
 * @param WP_Post $post Restaurant.
 */
function bigm_commande_boite( WP_Post $post ): void {
	$saisis = get_post_meta( $post->ID, '_bigm_commande_liens', true );
	$saisis = is_array( $saisis ) ? $saisis : array();
	$c      = bigm_restaurant_commande( $post->ID );
	wp_nonce_field( 'bigm_commande_' . $post->ID, 'bigm_commande_nonce' );
	echo '<p class="description">Boutons de la fenêtre Commander pour ce restaurant. Plateformes : Réglages Big M → Commande. Tous les restaurants : Restaurants → Liens de commande.</p>';
	foreach ( bigm_commande_plateformes() as $p ) {
		$id       = 'bigm-commande-' . $p['id'];
		$detecte  = '' === (string) ( $saisis[ $p['id'] ] ?? '' ) && ! empty( $c['liens'][ $p['id'] ] );
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="url" class="widefat" id="%1$s" name="bigm_commande[liens][%3$s]" value="%4$s" placeholder="%5$s"></p>',
			esc_attr( $id ),
			esc_html( $p['nom'] ),
			esc_attr( $p['id'] ),
			esc_attr( (string) ( $saisis[ $p['id'] ] ?? '' ) ),
			esc_attr( $detecte ? $c['liens'][ $p['id'] ] : 'https://…' )
		);
	}
	$tel = '1' === (string) get_post_meta( $post->ID, '_bigm_commande_tel', true );
	printf(
		'<p><label><input type="checkbox" name="bigm_commande[tel]" value="1"%s> Commande par téléphone (le restaurant livre lui-même)</label></p>',
		checked( $tel, true, false )
	);
	printf(
		'<p><label for="bigm-commande-numero">Numéro à appeler</label><br><input type="tel" class="widefat" id="bigm-commande-numero" name="bigm_commande[numero]" value="%s" placeholder="%s"></p>',
		esc_attr( (string) get_post_meta( $post->ID, '_bigm_commande_numero', true ) ),
		esc_attr( (string) get_post_meta( $post->ID, 'tfmsl_phone', true ) ?: 'Téléphone de la fiche' )
	);
	if ( $c['autre'] ) {
		echo '<p class="description">Lien Commander de la fiche, proposé en « Commander en ligne » : ' . esc_html( $c['autre'] ) . '</p>';
	}
}

add_action(
	'save_post_tfmsl_store',
	static function ( int $post_id ) {
		if ( ! isset( $_POST['bigm_commande_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bigm_commande_nonce'] ) ), 'bigm_commande_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$donnees = isset( $_POST['bigm_commande'] ) && is_array( $_POST['bigm_commande'] ) ? wp_unslash( $_POST['bigm_commande'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par bigm_restaurant_commande_enregistrer().
		bigm_restaurant_commande_enregistrer( $post_id, $donnees );
	},
	20
);

/* ---------------------------------------------------------------------------------------------
 * Admin : Restaurants → Liens de commande (tous les restaurants sur une page).
 * ------------------------------------------------------------------------------------------ */

add_action(
	'admin_menu',
	static function () {
		if ( bigm_storelocator_actif() ) {
			add_submenu_page( 'edit.php?post_type=tfmsl_store', 'Liens de commande', 'Liens de commande', 'edit_posts', 'bigm-commande', 'bigm_commande_page' );
		}
	},
	20
);

/**
 * Page « Liens de commande ».
 */
function bigm_commande_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$plateformes = bigm_commande_plateformes();
	$ids         = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	?>
	<div class="wrap bigm-commande">
		<h1>Liens de commande</h1>
		<p>Boutons de la fenêtre Commander, restaurant par restaurant. Un champ vide n’affiche pas le bouton. Cochez « Téléphone » pour les restaurants qui livrent eux-mêmes : la fenêtre propose alors de les appeler (numéro de la fiche, ou celui saisi dans le bloc « Commander » de la fiche).</p>
		<p>Plateformes, textes et logos : <a href="<?php echo esc_url( admin_url( 'admin.php?page=bigm-reglages#commande' ) ); ?>">Réglages Big M → Commande</a>. En gris : lien Commander de la fiche StoreLocatore, utilisé tant que le champ est vide.</p>
		<?php if ( isset( $_GET['enregistre'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( '%d restaurant(s) enregistré(s).', absint( $_GET['enregistre'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
		<?php endif; ?>
		<p><label for="bigm-commande-filtre">Filtrer</label> <input type="search" id="bigm-commande-filtre" class="bigm-filtre regular-text" data-filtre=".bigm-commande tbody tr" placeholder="Nom, ville…"></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bigm_commande_enregistrer">
			<?php wp_nonce_field( 'bigm_commande_page' ); ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col">Restaurant</th>
						<?php foreach ( $plateformes as $p ) : ?>
							<th scope="col"><?php echo esc_html( $p['nom'] ); ?></th>
						<?php endforeach; ?>
						<th scope="col">Téléphone</th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $ids as $id ) :
						if ( ! current_user_can( 'edit_post', $id ) ) {
							continue;
						}
						$saisis = get_post_meta( $id, '_bigm_commande_liens', true );
						$saisis = is_array( $saisis ) ? $saisis : array();
						$c      = bigm_restaurant_commande( $id );
						$phone  = trim( (string) get_post_meta( $id, '_bigm_commande_numero', true ) ) ?: trim( (string) get_post_meta( $id, 'tfmsl_phone', true ) );
						$titre  = get_the_title( $id );
						?>
						<tr>
							<th scope="row">
								<a href="<?php echo esc_url( (string) get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $titre ); ?></a>
								<br><span class="description"><?php echo esc_html( (string) get_post_meta( $id, 'tfmsl_city', true ) ); ?><?php echo 'publish' !== get_post_status( $id ) ? ' — brouillon' : ''; ?></span>
								<input type="hidden" name="c[<?php echo (int) $id; ?>][present]" value="1">
							</th>
							<?php foreach ( $plateformes as $p ) : ?>
								<td>
									<input type="url" class="widefat" name="c[<?php echo (int) $id; ?>][liens][<?php echo esc_attr( $p['id'] ); ?>]" value="<?php echo esc_attr( (string) ( $saisis[ $p['id'] ] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( '' === (string) ( $saisis[ $p['id'] ] ?? '' ) && ! empty( $c['liens'][ $p['id'] ] ) ? $c['liens'][ $p['id'] ] : '' ); ?>" aria-label="<?php echo esc_attr( $p['nom'] . ' — ' . $titre ); ?>">
								</td>
							<?php endforeach; ?>
							<td>
								<label><input type="checkbox" name="c[<?php echo (int) $id; ?>][tel]" value="1"<?php checked( '1' === (string) get_post_meta( $id, '_bigm_commande_tel', true ) ); ?> aria-label="<?php echo esc_attr( 'Commande par téléphone — ' . $titre ); ?>"> <?php echo esc_html( $phone ? $phone : 'pas de numéro' ); ?></label>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php submit_button( 'Enregistrer les liens' ); ?>
		</form>
	</div>
	<?php
}

add_action(
	'admin_post_bigm_commande_enregistrer',
	static function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Action non autorisée.' );
		}
		check_admin_referer( 'bigm_commande_page' );
		$lignes = isset( $_POST['c'] ) && is_array( $_POST['c'] ) ? wp_unslash( $_POST['c'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par bigm_restaurant_commande_enregistrer().
		$n      = 0;
		foreach ( $lignes as $id => $donnees ) {
			$id = absint( $id );
			if ( ! $id || 'tfmsl_store' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) || ! is_array( $donnees ) ) {
				continue;
			}
			unset( $donnees['numero'] );
			bigm_restaurant_commande_enregistrer( $id, $donnees );
			++$n;
		}
		wp_safe_redirect( add_query_arg( 'enregistre', $n, admin_url( 'edit.php?post_type=tfmsl_store&page=bigm-commande' ) ) );
		exit;
	}
);
