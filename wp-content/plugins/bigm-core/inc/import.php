<?php
/**
 * Import de la carte : Produits → Importer la carte.
 *
 * Lit data/carte.json (fabriqué par ops/carte/carte.py à partir du tableau des allergènes et,
 * si on l'a, de la page Uber Eats d'un restaurant), par lots de 10 produits :
 *
 * - catégories : créées si leur adresse n'existe pas encore (nom, titre, ordre, parent, icône) ;
 *   celles qui existent ne sont pas modifiées ;
 * - produits : retrouvés par leur adresse. Un nouveau produit est créé complet (catégories,
 *   viandes, allergènes « contient » et « traces », description, photo, ordre, statut, badge).
 *   Un produit existant reçoit les viandes et les allergènes du fichier, qui font foi. Si son nom
 *   n'a pas été retouché depuis le dernier import (ou s'il est provisoire, « nom à compléter »),
 *   il reprend aussi le nom, les catégories, le statut, l'ordre et le badge du fichier ; sinon
 *   ils ne changent pas. La description est remplacée si elle est vide ou encore celle d'un
 *   import précédent (pas retouchée) ; la photo est ajoutée s'il n'y en a pas, ou remplacée si
 *   elle vient d'un import précédent (l'ancienne est supprimée si plus rien ne s'en sert). On
 *   peut donc relancer l'import sans perdre ce qui a été retouché dans l'admin ;
 * - photos : téléchargées une fois (l'adresse d'origine est gardée sur le média : c'est elle qui
 *   distingue une photo importée d'une photo choisie dans l'admin) ;
 * - en option, les produits de démonstration (extension bigm-demo) vont à la corbeille.
 *
 * POST /wp-json/bigm/v1/carte/importer {"produits": [adresses]} n'importe que ces produits (et les
 * catégories qui manquent) : pour ajouter un produit au fichier sans repasser toute la carte.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

const BIGM_IMPORT_LOT = 10;

/**
 * Contenu de data/carte.json, ou null s'il est absent ou illisible.
 *
 * @return array{version: string, sources: string[], categories: array, produits: array}|null
 */
function bigm_import_donnees(): ?array {
	$fichier = BIGM_CORE_DIR . '/data/carte.json';
	$donnees = is_readable( $fichier ) ? json_decode( (string) file_get_contents( $fichier ), true ) : null; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichier de l'extension.
	return is_array( $donnees ) && isset( $donnees['produits'] ) && is_array( $donnees['produits'] ) ? $donnees : null;
}

/**
 * Crée les catégories absentes, parents d'abord.
 *
 * @param array<int, array<string, mixed>> $categories Catégories du fichier.
 * @return int Catégories créées.
 */
function bigm_import_categories( array $categories ): int {
	$crees = 0;
	usort( $categories, static fn( array $a, array $b ): int => (int) isset( $a['parent'] ) <=> (int) isset( $b['parent'] ) );
	foreach ( $categories as $c ) {
		if ( empty( $c['slug'] ) || get_term_by( 'slug', (string) $c['slug'], 'bigm_categorie' ) ) {
			continue;
		}
		$parent = ! empty( $c['parent'] ) ? get_term_by( 'slug', (string) $c['parent'], 'bigm_categorie' ) : null;
		$t      = wp_insert_term(
			(string) $c['nom'],
			'bigm_categorie',
			array(
				'slug'   => (string) $c['slug'],
				'parent' => $parent ? (int) $parent->term_id : 0,
			)
		);
		if ( is_wp_error( $t ) ) {
			continue;
		}
		update_term_meta( (int) $t['term_id'], '_bigm_ordre', (int) ( $c['ordre'] ?? 0 ) );
		if ( ! empty( $c['titre'] ) ) {
			update_term_meta( (int) $t['term_id'], '_bigm_titre', sanitize_text_field( (string) $c['titre'] ) );
		}
		if ( ! empty( $c['trace'] ) ) {
			update_term_meta( (int) $t['term_id'], '_bigm_icone', bigm_icone_depuis_trace( (string) $c['trace'] ) );
		}
		++$crees;
	}
	return $crees;
}

/**
 * Photo d'un produit, téléchargée une seule fois.
 *
 * @param string $url     Adresse de l'image (https).
 * @param int    $post_id Produit.
 * @param string $nom     Nom du produit (fichier et texte de remplacement).
 * @return int Média, 0 en cas d'échec.
 */
function bigm_import_image( string $url, int $post_id, string $nom ): int {
	$url = esc_url_raw( $url );
	if ( ! str_starts_with( $url, 'https://' ) ) {
		return 0;
	}
	$deja = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_source_image', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( $deja ) {
		return (int) $deja[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = download_url( $url, 30 );
	if ( is_wp_error( $tmp ) ) {
		return 0;
	}
	// Les adresses d'images des plateformes n'ont pas toujours d'extension : on la déduit du contenu.
	$types = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);
	$mime  = (string) wp_get_image_mime( $tmp );
	if ( ! isset( $types[ $mime ] ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	$id = media_handle_sideload(
		array(
			'name'     => sanitize_title( $nom ) . '.' . $types[ $mime ],
			'tmp_name' => $tmp,
		),
		$post_id,
		$nom
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( (int) $id, '_bigm_source_image', $url );
	update_post_meta( (int) $id, '_wp_attachment_image_alt', $nom );
	return (int) $id;
}

/**
 * Supprime une photo d'un import précédent, remplacée, si plus aucun contenu ne l'a en image à la une.
 *
 * @param int $media Média.
 */
function bigm_import_retirer_ancienne_photo( int $media ): void {
	$utilisee = get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $media, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! $utilisee ) {
		wp_delete_attachment( $media, true );
	}
}

/**
 * Crée ou met à jour un produit.
 *
 * @param array<string, mixed> $p Produit du fichier.
 * @return array{etat: string, image: bool, id: int} etat : cree, maj, erreur.
 */
function bigm_import_produit( array $p ): array {
	$titre   = sanitize_text_field( (string) ( $p['titre'] ?? '' ) );
	$slug    = sanitize_title( (string) ( $p['slug'] ?? $titre ) );
	$existe  = '' !== $slug ? get_page_by_path( $slug, OBJECT, 'bigm_produit' ) : null;
	$slugs_a = array_keys( bigm_allergenes() );
	$contient = bigm_nettoyer_slugs( (array) ( $p['contient'] ?? array() ), $slugs_a );
	$traces   = array_values( array_diff( bigm_nettoyer_slugs( (array) ( $p['traces'] ?? array() ), $slugs_a ), $contient ) );
	$viandes  = bigm_nettoyer_slugs( (array) ( $p['viandes'] ?? array() ), array_keys( bigm_viandes() ) );
	$desc     = wp_kses_post( (string) ( $p['description'] ?? '' ) );

	$termes = array();
	foreach ( (array) ( $p['categories'] ?? array() ) as $c ) {
		$t = get_term_by( 'slug', (string) $c, 'bigm_categorie' );
		if ( $t ) {
			$termes[] = (int) $t->term_id;
		}
	}
	$badge = sanitize_text_field( (string) ( $p['badge'] ?? '' ) );

	if ( $existe ) {
		$id      = (int) $existe->ID;
		$modifs  = array( 'ID' => $id );
		$nom_imp = (string) get_post_meta( $id, '_bigm_import_titre', true );
		// Nom jamais retouché dans l'admin (nom provisoire, ou celui du dernier import) : le
		// produit suit le fichier (nom, catégories, statut, ordre, badge). Sinon il est gardé.
		$intact = str_contains( $existe->post_title, '(nom à compléter)' )
			|| in_array( $existe->post_title, array( $titre, $nom_imp, (string) get_post_meta( $id, '_bigm_import', true ) ), true );
		if ( $intact && '' !== $titre ) {
			$modifs['post_title']  = $titre;
			$modifs['post_status'] = 'draft' === ( $p['statut'] ?? '' ) ? 'draft' : 'publish';
			$modifs['menu_order']  = (int) ( $p['ordre'] ?? 0 );
			wp_set_object_terms( $id, $termes, 'bigm_categorie' );
			update_post_meta( $id, '_bigm_badge', $badge );
		}
		// Description vide, ou encore celle d'un import précédent (pas retouchée) : celle du fichier.
		$texte     = static fn( string $html ): string => trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
		$actuelle  = $texte( (string) $existe->post_content );
		$anciennes = array_map( $texte, array_merge( (array) ( $p['anciennes_descriptions'] ?? array() ), array( (string) get_post_meta( $id, '_bigm_import_description', true ) ) ) );
		if ( '' !== $desc && $actuelle !== $texte( $desc ) && ( '' === $actuelle || in_array( $actuelle, $anciennes, true ) ) ) {
			$modifs['post_content'] = $desc;
		}
		if ( count( $modifs ) > 1 ) {
			wp_update_post( $modifs );
		}
		$etat = 'maj';
	} else {
		if ( '' === $titre ) {
			return array(
				'etat'  => 'erreur',
				'image' => false,
				'id'    => 0,
			);
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'bigm_produit',
				'post_title'   => $titre,
				'post_name'    => $slug,
				'post_status'  => 'draft' === ( $p['statut'] ?? '' ) ? 'draft' : 'publish',
				'post_content' => $desc,
				'menu_order'   => (int) ( $p['ordre'] ?? 0 ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return array(
				'etat'  => 'erreur',
				'image' => false,
				'id'    => 0,
			);
		}
		wp_set_object_terms( (int) $id, $termes, 'bigm_categorie' );
		update_post_meta( (int) $id, '_bigm_badge', $badge );
		$etat = 'cree';
	}
	update_post_meta( $id, '_bigm_viande', $viandes );
	update_post_meta( $id, '_bigm_allergenes_contient', $contient );
	update_post_meta( $id, '_bigm_allergenes_traces', $traces );
	update_post_meta( $id, '_bigm_import', sanitize_text_field( (string) ( $p['source'] ?? $titre ) ) );
	update_post_meta( $id, '_bigm_a_verifier', sanitize_text_field( (string) ( $p['a_verifier'] ?? '' ) ) );
	update_post_meta( $id, '_bigm_import_titre', (string) get_the_title( $id ) );
	update_post_meta( $id, '_bigm_import_description', $desc );

	// Photo : ajoutée s'il n'y en a pas ; remplacée si la photo actuelle vient d'un import précédent
	// (autre adresse d'origine). Une photo choisie dans l'admin n'est jamais remplacée.
	$image   = false;
	$url     = esc_url_raw( (string) ( $p['image'] ?? '' ) );
	$actuel  = (int) get_post_thumbnail_id( $id );
	$origine = $actuel ? (string) get_post_meta( $actuel, '_bigm_source_image', true ) : '';
	if ( '' !== $url && ( ! $actuel || ( '' !== $origine && $origine !== $url ) ) ) {
		$media = bigm_import_image( $url, $id, $titre );
		if ( $media && $media !== $actuel ) {
			set_post_thumbnail( $id, $media );
			$image = true;
			if ( $actuel && '' !== $origine ) {
				bigm_import_retirer_ancienne_photo( $actuel );
			}
		}
	}
	return array(
		'etat'  => $etat,
		'image' => $image,
		'id'    => $id,
	);
}

/**
 * Traite un lot et met à jour le compte rendu (option bigm_import_carte).
 *
 * @param int  $lot  Lot (1…).
 * @param bool $demo Mettre les produits de démonstration à la corbeille (au premier lot).
 * @return array<string, mixed> Compte rendu.
 */
function bigm_import_lot( int $lot, bool $demo = false ): array {
	$donnees = bigm_import_donnees();
	if ( ! $donnees ) {
		return array(
			'fini'    => true,
			'message' => 'Fichier data/carte.json absent ou illisible.',
		);
	}
	$produits = array_values( $donnees['produits'] );
	$cr       = 1 === $lot ? array(
		'version'    => (string) ( $donnees['version'] ?? '' ),
		'debut'      => current_time( 'mysql' ),
		'categories' => bigm_import_categories( (array) ( $donnees['categories'] ?? array() ) ),
		'demo'       => 0,
		'cree'       => 0,
		'maj'        => 0,
		'erreur'     => 0,
		'images'     => 0,
		'lots'       => (int) ceil( count( $produits ) / BIGM_IMPORT_LOT ),
		'lot'        => 0,
		'fini'       => false,
		'message'    => '',
	) : (array) get_option( 'bigm_import_carte', array() );

	if ( 1 === $lot && $demo ) {
		$demos = get_posts(
			array(
				'post_type'      => 'bigm_produit',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_bigm_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		foreach ( $demos as $id ) {
			if ( wp_trash_post( (int) $id ) ) {
				++$cr['demo'];
			}
		}
	}

	foreach ( array_slice( $produits, ( $lot - 1 ) * BIGM_IMPORT_LOT, BIGM_IMPORT_LOT ) as $p ) {
		$r                = bigm_import_produit( (array) $p );
		$cr[ $r['etat'] ] = (int) ( $cr[ $r['etat'] ] ?? 0 ) + 1;
		$cr['images']     = (int) $cr['images'] + (int) $r['image'];
	}
	$cr['lot']  = $lot;
	$cr['fini'] = $lot >= (int) $cr['lots'];
	update_option( 'bigm_import_carte', $cr, false );
	if ( $cr['fini'] && function_exists( 'bigm_vider_cache' ) ) {
		bigm_vider_cache();
	}
	return $cr;
}

/*
 * Page Produits → Importer la carte, et lots enchaînés (une page par lot, rechargée seule).
 */
add_action(
	'admin_menu',
	static function () {
		add_submenu_page( 'edit.php?post_type=bigm_produit', 'Importer la carte', 'Importer la carte', 'manage_options', 'bigm-import-carte', 'bigm_import_page' );
	}
);

/**
 * Page de l'import.
 */
function bigm_import_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$donnees = bigm_import_donnees();
	$lot     = isset( $_GET['lot'] ) ? absint( $_GET['lot'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- vérifié ci-dessous avant tout traitement.
	$demo    = ! empty( $_GET['demo'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cr      = (array) get_option( 'bigm_import_carte', array() );
	echo '<div class="wrap"><h1>Importer la carte</h1>';
	if ( $lot && check_admin_referer( 'bigm_import_carte' ) ) {
		set_time_limit( 300 );
		$cr = bigm_import_lot( $lot, $demo );
		if ( empty( $cr['fini'] ) ) {
			$suite = wp_nonce_url(
				add_query_arg(
					array(
						'post_type' => 'bigm_produit',
						'page'      => 'bigm-import-carte',
						'lot'       => $lot + 1,
					),
					admin_url( 'edit.php' )
				),
				'bigm_import_carte'
			);
			printf( '<p>Lot %d sur %d traité… la page se recharge seule. <a href="%s">Continuer</a></p>', (int) $lot, (int) $cr['lots'], esc_url( $suite ) );
			echo '<meta http-equiv="refresh" content="1;url=' . esc_url( $suite ) . '">';
		}
	}
	if ( $cr ) {
		echo '<div class="notice notice-' . ( empty( $cr['message'] ) ? 'info' : 'error' ) . '"><p>';
		if ( ! empty( $cr['message'] ) ) {
			echo esc_html( (string) $cr['message'] );
		} else {
			printf(
				'Import du %1$s (fichier du %2$s) : %3$d produits créés, %4$d mis à jour, %5$d en erreur, %6$d photos, %7$d catégories créées, %8$d produits de démonstration mis à la corbeille.%9$s',
				esc_html( mysql2date( 'j F Y H:i', (string) $cr['debut'] ) ),
				esc_html( (string) $cr['version'] ),
				(int) $cr['cree'],
				(int) $cr['maj'],
				(int) $cr['erreur'],
				(int) $cr['images'],
				(int) $cr['categories'],
				(int) $cr['demo'],
				! empty( $cr['fini'] ) ? ' <strong>Terminé.</strong>' : ''
			);
		}
		echo '</p></div>';
	}

	if ( ! $donnees ) {
		echo '<p>Le fichier <code>data/carte.json</code> de l’extension est absent ou illisible.</p></div>';
		return;
	}
	$publies = count( array_filter( $donnees['produits'], static fn( $p ): bool => 'draft' !== ( $p['statut'] ?? '' ) ) );
	?>
	<p>
		Fichier du <?php echo esc_html( (string) $donnees['version'] ); ?> :
		<strong><?php echo (int) count( $donnees['produits'] ); ?> produits</strong>
		(<?php echo (int) $publies; ?> publiés, <?php echo (int) ( count( $donnees['produits'] ) - $publies ); ?> en brouillon),
		<?php echo (int) count( (array) ( $donnees['categories'] ?? array() ) ); ?> catégories.
		Sources : <?php echo esc_html( implode( ' ; ', (array) ( $donnees['sources'] ?? array() ) ) ); ?>.
	</p>
	<p>Un produit qui existe déjà (même adresse) reçoit les viandes et les allergènes du fichier. S’il a été renommé dans l’admin, il garde son nom, ses catégories et son statut ; sinon il suit le fichier. Sa description et sa photo suivent le fichier tant qu’elles n’ont pas été changées dans l’admin. On peut relancer l’import sans risque.</p>
	<form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
		<input type="hidden" name="post_type" value="bigm_produit">
		<input type="hidden" name="page" value="bigm-import-carte">
		<input type="hidden" name="lot" value="1">
		<?php wp_nonce_field( 'bigm_import_carte', '_wpnonce', false ); ?>
		<p><label><input type="checkbox" name="demo" value="1"> Mettre les produits de démonstration à la corbeille</label><br>
			<span class="description">Avant de cocher : remplacer les produits de démonstration là où ils ont été choisis un par un (carrousel « Nos classiques » de l’accueil, « Ça va bien avec »), sinon ces blocs restent vides. On peut relancer l’import juste pour ça : il ne recrée rien.</span></p>
		<p><button class="button button-primary">Importer la carte</button></p>
	</form>
	<?php
	$a_verifier = get_posts(
		array(
			'post_type'      => 'bigm_produit',
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_bigm_a_verifier',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);
	if ( $a_verifier ) {
		echo '<h2>À vérifier</h2><p class="description">Points notés à l’import (viande inconnue, nom manquant…). La note disparaît au prochain import si le fichier n’en porte plus.</p>';
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>Produit</th><th>Statut</th><th>À vérifier</th></tr></thead><tbody>';
		foreach ( $a_verifier as $post ) {
			echo '<tr><td><a href="' . esc_url( (string) get_edit_post_link( $post->ID ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a></td><td>' . esc_html( 'draft' === $post->post_status ? 'Brouillon' : 'Publié' ) . '</td><td>' . esc_html( (string) get_post_meta( $post->ID, '_bigm_a_verifier', true ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

/**
 * Importe seulement quelques produits du fichier (et les catégories qui manquent), sans relancer
 * toute la carte : les autres produits ne sont pas touchés.
 *
 * @param string[] $slugs Adresses des produits dans le fichier.
 * @return array{version: string, categories: int, produits: array<string, array{etat: string, id: int, lien: string}>, absents: string[]}|WP_Error
 */
function bigm_import_produits( array $slugs ) {
	$donnees = bigm_import_donnees();
	if ( ! $donnees ) {
		return new WP_Error( 'bigm_import_fichier', 'Fichier data/carte.json absent ou illisible.' );
	}
	$fichier = array();
	foreach ( (array) $donnees['produits'] as $p ) {
		$fichier[ sanitize_title( (string) ( $p['slug'] ?? $p['titre'] ?? '' ) ) ] = (array) $p;
	}
	$resultat = array(
		'version'    => (string) ( $donnees['version'] ?? '' ),
		'categories' => bigm_import_categories( (array) ( $donnees['categories'] ?? array() ) ),
		'produits'   => array(),
		'absents'    => array(),
	);
	foreach ( array_unique( array_map( 'sanitize_title', $slugs ) ) as $slug ) {
		if ( '' === $slug || ! isset( $fichier[ $slug ] ) ) {
			$resultat['absents'][] = $slug;
			continue;
		}
		$r                             = bigm_import_produit( $fichier[ $slug ] );
		$resultat['produits'][ $slug ] = array(
			'etat' => $r['etat'],
			'id'   => (int) $r['id'],
			'lien' => $r['id'] ? (string) get_permalink( (int) $r['id'] ) : '',
		);
	}
	if ( function_exists( 'bigm_vider_cache' ) ) {
		bigm_vider_cache();
	}
	return $resultat;
}

/*
 * POST /wp-json/bigm/v1/carte/importer {"produits": ["big-box", "m-box"]} : importe ces produits
 * du fichier (et les catégories qui manquent), comme l'import complet mais sans les autres.
 */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/carte/importer',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'args'                => array(
					'produits' => array(
						'type'     => 'array',
						'items'    => array( 'type' => 'string' ),
						'required' => true,
					),
				),
				'callback'            => static function ( WP_REST_Request $r ) {
					$resultat = bigm_import_produits( (array) $r['produits'] );
					if ( is_wp_error( $resultat ) ) {
						$resultat->add_data( array( 'status' => 500 ) );
					}
					return rest_ensure_response( $resultat );
				},
			)
		);
	}
);
