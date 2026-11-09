<?php
/**
 * Fichier llms.txt à la racine du site (https://llmstxt.org) : un résumé du site en Markdown
 * pour les assistants d'IA (ChatGPT, Claude, Perplexity…), tenu à jour à partir du contenu.
 *
 * - Contenu : résumé (Réglages Big M → Référencement), informations clés (halal, pas de prix,
 *   allergènes, commande), la carte par catégorie avec chaque produit et sa description, les
 *   restaurants avec leur adresse et leur téléphone, la franchise (chiffres des réglages), le
 *   recrutement, les derniers articles, puis les pages légales.
 * - Fichier réel ABSPATH/llms.txt, servi par le serveur sans WordPress. Réécrit à la fin d'une
 *   requête qui a modifié un produit, un restaurant, une page, un article, une catégorie ou les
 *   réglages ; vérifié chaque jour et au passage d'un administrateur (au plus une fois par heure).
 * - L'extension Hostinger écrit aussi un llms.txt (articles seulement) : désactiver son option
 *   LLMs.txt. Si elle réécrit le fichier, la vérification suivante remet le nôtre (repéré par
 *   le commentaire de la dernière ligne) et Réglages Big M le signale.
 * - POST /wp-json/bigm/v1/llms : réécrit le fichier tout de suite (administrateurs).
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

const BIGM_LLMS_MARQUE = '<!-- llms.txt de bigm-core : Réglages Big M → Référencement -->';

/**
 * Chemin du fichier.
 */
function bigm_llms_chemin(): string {
	return (string) apply_filters( 'bigm_llms_chemin', ABSPATH . 'llms.txt' );
}

/**
 * Texte d'une ligne de Markdown : sans balises, sans entités, sur une ligne, raccourci.
 *
 * @param string $texte  Texte ou HTML.
 * @param int    $limite Nombre de caractères au plus (0 : pas de limite).
 */
function bigm_llms_texte( string $texte, int $limite = 0 ): string {
	$t = html_entity_decode( wp_strip_all_tags( $texte ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$t = trim( (string) preg_replace( '/\s+/u', ' ', $t ) );
	if ( $limite && mb_strlen( $t ) > $limite ) {
		$t = rtrim( mb_substr( $t, 0, $limite - 1 ), " ,;:.-" ) . '…';
	}
	return $t;
}

/**
 * Adresse d'une page du site par son adresse courte, si elle est publiée.
 *
 * @param string $slug Adresse de la page.
 */
function bigm_llms_page( string $slug ): ?WP_Post {
	$page = function_exists( 'bigm_post_par_adresse' ) ? bigm_post_par_adresse( $slug, 'page' ) : null;
	return $page && 'publish' === $page->post_status ? $page : null;
}

/**
 * Contenu du fichier llms.txt.
 */
function bigm_llms_txt(): string {
	$nom    = bigm_llms_texte( (string) get_bloginfo( 'name' ) );
	$nom    = '' !== $nom ? $nom : 'Big M';
	$resume = bigm_llms_texte( (string) bigm_reglage( 'llms_resume' ) );
	$lien   = static fn( string $titre, string $url, string $note = '' ): string => '- [' . str_replace( array( '[', ']' ), '', bigm_llms_texte( $titre ) ) . '](' . esc_url_raw( $url ) . ')' . ( '' !== $note ? ': ' . $note : '' );

	// Restaurants publiés, par nom.
	$restos = function_exists( 'bigm_storelocator_actif' ) && bigm_storelocator_actif() ? get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	) : array();

	// Produits de la carte, sous la catégorie de premier niveau de leur catégorie principale.
	$racines  = bigm_categories( 0, true );
	$par_cat  = array();
	$nouveaux = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'bigm_produit',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		)
	) as $post ) {
		$principale = bigm_categorie_principale( $post->ID );
		if ( ! $principale ) {
			continue; // Sans catégorie : pas sur la carte.
		}
		$p = bigm_produit( $post );
		if ( ! $p ) {
			continue;
		}
		$par_cat[ bigm_categorie_racine( $principale )->term_id ][] = $p;
		if ( function_exists( 'bigm_produit_est_nouveau' ) && bigm_produit_est_nouveau( $p ) ) {
			$nouveaux[] = $p['titre'];
		}
	}

	// Dans chaque catégorie, l'ordre de sa page : sous-catégories dans l'ordre de la carte.
	foreach ( $par_cat as $cat_id => $produits ) {
		$terme = get_term( (int) $cat_id, 'bigm_categorie' );
		if ( ! $terme instanceof WP_Term ) {
			continue;
		}
		$rang = array();
		foreach ( bigm_groupes_produits( $terme ) as $g ) {
			foreach ( $g['produits'] as $p ) {
				$rang[ $p['id'] ] = $rang[ $p['id'] ] ?? count( $rang );
			}
		}
		usort( $produits, static fn( array $a, array $b ): int => ( $rang[ $a['id'] ] ?? PHP_INT_MAX ) <=> ( $rang[ $b['id'] ] ?? PHP_INT_MAX ) );
		$par_cat[ $cat_id ] = $produits;
	}

	$l   = array();
	$l[] = '# ' . $nom;
	$l[] = '';
	if ( '' !== $resume ) {
		$l[] = '> ' . $resume;
		$l[] = '';
	}
	$l[] = '- Site officiel : ' . home_url( '/' );
	if ( $restos ) {
		$l[] = '- ' . count( $restos ) . ' restaurants Big M. Adresse, horaires, téléphone, services et liens de commande : page de chaque restaurant (liste plus bas).';
	}
	$l[] = '- Tous les produits sont 100 % halal. Le site n’affiche pas de prix : ils varient selon les restaurants.';
	$l[] = '- Allergènes : chaque fiche produit indique les 14 allergènes à déclaration obligatoire (« contient » et « traces éventuelles »). Les informations viennent des fournisseurs ; en cas d’allergie, demander confirmation au restaurant.';
	$l[] = '- Commander : sur place, à emporter, ou en livraison (Uber Eats, Deliveroo selon les restaurants), depuis le bouton « Commander » du site ou la page du restaurant.';
	if ( $nouveaux ) {
		$l[] = '- Nouveautés : ' . implode( ', ', array_map( 'bigm_llms_texte', array_unique( $nouveaux ) ) ) . '.';
	}
	$l[] = '';

	$l[] = '## La carte';
	$l[] = '';
	$l[] = $lien( 'Notre carte', (string) get_post_type_archive_link( 'bigm_produit' ), 'toutes les catégories et le bandeau des allergènes' );
	foreach ( $racines as $cat ) {
		if ( empty( $par_cat[ $cat->term_id ] ) ) {
			continue;
		}
		$titre = (string) get_term_meta( $cat->term_id, '_bigm_titre', true );
		$l[]   = $lien( '' !== $titre ? $titre : $cat->name, (string) get_term_link( $cat ), count( $par_cat[ $cat->term_id ] ) . ' produits' );
	}
	$l[] = '';

	foreach ( $racines as $cat ) {
		if ( empty( $par_cat[ $cat->term_id ] ) ) {
			continue;
		}
		$l[] = '## ' . bigm_llms_texte( $cat->name );
		$l[] = '';
		foreach ( $par_cat[ $cat->term_id ] as $p ) {
			$notes = array();
			$desc  = bigm_llms_texte( (string) $p['description'], 180 );
			if ( '' !== $desc ) {
				$notes[] = $desc;
			}
			if ( '' !== $p['badge'] ) {
				$notes[] = bigm_llms_texte( $p['badge'] );
			}
			$l[] = $lien( $p['titre'], $p['url'], implode( ' · ', $notes ) );
		}
		$l[] = '';
	}

	if ( $restos ) {
		$l[]   = '## Restaurants';
		$l[]   = '';
		$liste = bigm_llms_page( 'restaurants' );
		$l[]   = $lien( 'Tous nos restaurants', $liste ? (string) get_permalink( $liste ) : home_url( '/restaurants/' ), 'carte interactive et recherche par ville' );
		foreach ( $restos as $r ) {
			$m       = static fn( string $cle ): string => trim( (string) get_post_meta( $r->ID, $cle, true ) );
			$adresse = trim( $m( 'tfmsl_address' ) . ', ' . trim( $m( 'tfmsl_zip' ) . ' ' . $m( 'tfmsl_city' ) ), ' ,' );
			$l[]     = $lien( 'Big M ' . get_the_title( $r ), (string) get_permalink( $r ), implode( ' · ', array_filter( array( bigm_llms_texte( $adresse ), $m( 'tfmsl_phone' ) ) ) ) );
		}
		$l[] = '';
	}

	$franchise   = bigm_llms_page( 'devenir-franchise' );
	$recrutement = bigm_llms_page( 'nous-rejoindre' );
	if ( $franchise || $recrutement ) {
		$l[] = '## Franchise et recrutement';
		$l[] = '';
		if ( $franchise ) {
			$chiffres = array_filter(
				array(
					'' !== bigm_reglage_texte( 'franchise_duree' ) ? 'contrat de ' . bigm_reglage_texte( 'franchise_duree' ) : '',
					'' !== bigm_reglage_texte( 'franchise_droit_entree' ) ? 'droit d’entrée ' . bigm_reglage_texte( 'franchise_droit_entree' ) : '',
					'' !== bigm_reglage_texte( 'franchise_formation' ) ? 'formation ' . bigm_reglage_texte( 'franchise_formation' ) : '',
					'' !== bigm_reglage_texte( 'franchise_redevance_enseigne' ) ? 'redevance d’enseigne ' . bigm_reglage_texte( 'franchise_redevance_enseigne' ) : '',
					'' !== bigm_reglage_texte( 'franchise_redevance_communication' ) ? 'redevance communication ' . bigm_reglage_texte( 'franchise_redevance_communication' ) : '',
				)
			);
			$l[] = $lien( get_the_title( $franchise ), (string) get_permalink( $franchise ), bigm_llms_texte( implode( ', ', $chiffres ) . ( $chiffres ? ' ; ' : '' ) . 'formulaire de candidature' ) );
		}
		if ( $recrutement ) {
			$l[] = $lien( get_the_title( $recrutement ), (string) get_permalink( $recrutement ), 'travailler chez Big M : candidature avec CV' );
		}
		$l[] = '';
	}

	$articles = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
		)
	);
	if ( $articles ) {
		$l[]   = '## Actualités';
		$l[]   = '';
		$blog  = (int) get_option( 'page_for_posts' );
		$l[]   = $lien( 'Actualités et presse', $blog ? (string) get_permalink( $blog ) : home_url( '/actualites/' ), 'news de la marque et articles de presse' );
		foreach ( $articles as $a ) {
			$l[] = $lien( get_the_title( $a ), (string) get_permalink( $a ), get_the_date( 'j F Y', $a ) );
		}
		$l[] = '';
	}

	$legales = array();
	foreach ( array( 'mentions-legales', 'politique-de-confidentialite', 'cookies', 'jeu-concours' ) as $slug ) {
		$page = bigm_llms_page( $slug );
		if ( $page ) {
			$legales[] = $lien( get_the_title( $page ), (string) get_permalink( $page ) );
		}
	}
	if ( $legales ) {
		$l[] = '## Optional';
		$l[] = '';
		array_push( $l, ...$legales );
		$l[] = '';
	}
	$l[] = BIGM_LLMS_MARQUE;
	return (string) apply_filters( 'bigm_llms_txt', implode( "\n", $l ) . "\n" );
}

/**
 * Le fichier en place est-il le nôtre ?
 */
function bigm_llms_est_le_notre(): bool {
	$f = bigm_llms_chemin();
	return is_readable( $f ) && str_contains( (string) file_get_contents( $f ), BIGM_LLMS_MARQUE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Écrit le fichier (si le réglage est coché) et garde l'état dans l'option bigm_llms_etat.
 *
 * @return array{ok: bool, date: string, taille: int, message: string}
 */
function bigm_llms_ecrire(): array {
	$etat = array(
		'ok'      => false,
		'date'    => current_time( 'mysql' ),
		'taille'  => 0,
		'message' => '',
	);
	if ( '1' !== (string) bigm_reglage( 'llms_actif' ) ) {
		$etat['message'] = 'désactivé dans Réglages Big M';
	} else {
		$texte  = bigm_llms_txt();
		$chemin = bigm_llms_chemin();
		$ecrit  = ( ! file_exists( $chemin ) || is_writable( $chemin ) ) && false !== file_put_contents( $chemin, $texte, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$etat['ok']      = $ecrit;
		$etat['taille']  = $ecrit ? strlen( $texte ) : 0;
		$etat['message'] = $ecrit ? '' : 'écriture impossible dans ' . $chemin;
	}
	update_option( 'bigm_llms_etat', $etat, false );
	return $etat;
}

/*
 * Réécriture à la fin d'une requête qui a modifié le contenu (une seule fois par requête).
 */
$bigm_llms_a_refaire = static function () {
	static $prevu = false;
	if ( $prevu || '1' !== (string) bigm_reglage( 'llms_actif' ) ) {
		return;
	}
	$prevu = true;
	add_action( 'shutdown', 'bigm_llms_ecrire' );
};
add_action(
	'save_post',
	static function ( $post_id, $post ) use ( $bigm_llms_a_refaire ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! in_array( $post->post_type, array( 'bigm_produit', 'tfmsl_store', 'page', 'post' ), true ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		$bigm_llms_a_refaire();
	},
	99,
	2
);
add_action(
	'deleted_post',
	static function ( $post_id, $post = null ) use ( $bigm_llms_a_refaire ) {
		if ( $post instanceof WP_Post && in_array( $post->post_type, array( 'bigm_produit', 'tfmsl_store', 'page', 'post' ), true ) ) {
			$bigm_llms_a_refaire();
		}
	},
	10,
	2
);
foreach ( array( 'created_bigm_categorie', 'edited_bigm_categorie', 'delete_bigm_categorie', 'update_option_bigm_reglages' ) as $bigm_llms_crochet ) {
	add_action( $bigm_llms_crochet, $bigm_llms_a_refaire );
}
unset( $bigm_llms_crochet, $bigm_llms_a_refaire );

/*
 * Vérification : chaque jour (tâche planifiée), et au passage d'un administrateur au plus une
 * fois par heure. Fichier absent, remplacé (extension Hostinger) ou d'une ancienne version.
 */
add_action(
	'bigm_llms_quotidien',
	static function () {
		if ( '1' === (string) bigm_reglage( 'llms_actif' ) ) {
			bigm_llms_ecrire();
		}
	}
);
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'bigm_llms_quotidien' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bigm_llms_quotidien' );
		}
	},
	30
);
register_deactivation_hook( BIGM_CORE_FILE, static fn() => wp_clear_scheduled_hook( 'bigm_llms_quotidien' ) );
add_action(
	'admin_init',
	static function () {
		if ( '1' !== (string) bigm_reglage( 'llms_actif' ) || ! current_user_can( 'manage_options' ) || get_transient( 'bigm_llms_verifie' ) ) {
			return;
		}
		set_transient( 'bigm_llms_verifie', 1, HOUR_IN_SECONDS );
		$etat = (array) get_option( 'bigm_llms_etat', array() );
		if ( ! bigm_llms_est_le_notre() || BIGM_CORE_VERSION !== get_option( 'bigm_llms_version' ) || empty( $etat['ok'] ) ) {
			if ( file_exists( bigm_llms_chemin() ) && ! bigm_llms_est_le_notre() ) {
				update_option( 'bigm_llms_remplace', current_time( 'mysql' ), false );
			}
			bigm_llms_ecrire();
			update_option( 'bigm_llms_version', BIGM_CORE_VERSION, false );
		}
	}
);

/*
 * Réglages Big M → Référencement : état du fichier.
 */
add_action(
	'bigm_reglages_section',
	static function ( string $section ) {
		if ( 'referencement' !== $section ) {
			return;
		}
		$etat = (array) get_option( 'bigm_llms_etat', array() );
		if ( '1' !== (string) bigm_reglage( 'llms_actif' ) ) {
			echo '<p><strong>llms.txt :</strong> désactivé.</p>';
			return;
		}
		if ( ! empty( $etat['ok'] ) ) {
			printf(
				'<p><strong>llms.txt :</strong> <a href="%1$s" target="_blank" rel="noopener">%1$s</a>, mis à jour le %2$s (%3$s Ko).</p>',
				esc_url( home_url( '/llms.txt' ) ),
				esc_html( mysql2date( 'j F Y à H:i', (string) $etat['date'] ) ),
				esc_html( number_format_i18n( (int) $etat['taille'] / 1024, 1 ) )
			);
		} elseif ( ! empty( $etat['message'] ) ) {
			echo '<p><strong>llms.txt :</strong> ' . esc_html( (string) $etat['message'] ) . '.</p>';
		}
		$remplace = (string) get_option( 'bigm_llms_remplace', '' );
		if ( '' !== $remplace ) {
			echo '<p class="description">Le ' . esc_html( mysql2date( 'j F Y à H:i', $remplace ) ) . ', un autre llms.txt avait pris sa place (sans doute l’extension Hostinger) : désactiver l’option LLMs.txt de l’extension Hostinger.</p>';
		}
	}
);

/*
 * POST /wp-json/bigm/v1/llms : réécrit le fichier tout de suite.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/llms',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'callback'            => static function () {
					$avant = file_exists( bigm_llms_chemin() ) && ! bigm_llms_est_le_notre();
					$etat  = bigm_llms_ecrire();
					update_option( 'bigm_llms_version', BIGM_CORE_VERSION, false );
					return rest_ensure_response( $etat + array( 'remplace_un_autre' => $avant ) );
				},
			)
		);
	}
);
