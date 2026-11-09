<?php
/**
 * Reprise des articles de l'ancien site (brief, § 10) : Outils → Reprise des articles.
 *
 * Lit l'API publique de l'ancien WordPress (/wp-json/wp/v2/posts), 5 articles par lot,
 * et les recrée ici :
 * - même adresse (slug), même date, titre, texte, extrait ;
 * - catégorie Presse (anciennes Presse, Print, Web, Radio, TV) ou News (le reste) ;
 *   Print, Web, Radio, TV et les anciens mots-clés deviennent des étiquettes ;
 * - image à la une et images du texte rapatriées dans la médiathèque (une seule fois chacune) ;
 * - texte nettoyé : ni script ni style ; vidéos YouTube et Vimeo remplacées par leur adresse,
 *   que WordPress intègre lui-même.
 *
 * Un article déjà repris (même identifiant d'origine ou même adresse) n'est pas recréé :
 * on peut relancer la reprise sans risque. Seules les images du site d'origine sont téléchargées.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

const BIGM_REPRISE_LOT = 5;

/**
 * Catégories d'origine qui deviennent « Presse ».
 *
 * @return string[]
 */
function bigm_reprise_categories_presse(): array {
	return array( 'presse', 'print', 'web', 'radio', 'tv' );
}

/**
 * Lit une page de l'API de l'ancien site.
 *
 * @param string $source Adresse de l'ancien site.
 * @param int    $page   Page (1…).
 * @return array{articles: array, total_pages: int}|WP_Error
 */
function bigm_reprise_lire( string $source, int $page ) {
	$url  = trailingslashit( $source ) . 'wp-json/wp/v2/posts?' . http_build_query(
		array(
			'per_page' => BIGM_REPRISE_LOT,
			'page'     => $page,
			'orderby'  => 'date',
			'order'    => 'asc',
			'status'   => 'publish',
			'_embed'   => 'wp:featuredmedia,wp:term',
		)
	);
	$rep = wp_remote_get(
		$url,
		array(
			'timeout'    => 30,
			'user-agent' => 'BigM-reprise/1.0 (' . home_url() . ')',
		)
	);
	if ( is_wp_error( $rep ) ) {
		return $rep;
	}
	$code = (int) wp_remote_retrieve_response_code( $rep );
	if ( 400 === $code ) {
		return array(
			'articles'    => array(),
			'total_pages' => 0,
		); // Au-delà de la dernière page.
	}
	if ( 200 !== $code ) {
		return new WP_Error( 'bigm_reprise', "L'ancien site répond {$code} à {$url}" );
	}
	$articles = json_decode( (string) wp_remote_retrieve_body( $rep ), true );
	return array(
		'articles'    => is_array( $articles ) ? $articles : array(),
		'total_pages' => (int) wp_remote_retrieve_header( $rep, 'x-wp-totalpages' ),
	);
}

/**
 * Image de l'ancien site rapatriée dans la médiathèque (une seule fois par adresse).
 *
 * @param string $url     Adresse de l'image.
 * @param string $source  Adresse de l'ancien site.
 * @param int    $post_id Article auquel la rattacher.
 * @param string $alt     Texte de remplacement.
 * @return int Média, 0 si l'image n'a pas pu être reprise.
 */
function bigm_reprise_image( string $url, string $source, int $post_id, string $alt = '' ): int {
	$url  = esc_url_raw( html_entity_decode( $url ) );
	$hote = (string) wp_parse_url( $source, PHP_URL_HOST );
	if ( '' === $url || strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ) !== strtolower( $hote ) || ! str_contains( $url, '/wp-content/uploads/' ) ) {
		return 0;
	}
	$deja = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_ancienne_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( $deja ) {
		return (int) $deja[0];
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_sideload_image( $url, $post_id, null, 'id' );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( (int) $id, '_bigm_ancienne_url', $url );
	if ( '' !== $alt ) {
		update_post_meta( (int) $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
	}
	return (int) $id;
}

/**
 * Texte d'un article de l'ancien site, nettoyé, avec ses images rapatriées.
 *
 * @param string $html    Contenu (content.rendered).
 * @param string $source  Adresse de l'ancien site.
 * @param int    $post_id Article.
 * @param int    $images  Compteur d'images reprises (incrémenté).
 */
function bigm_reprise_texte( string $html, string $source, int $post_id, int &$images = 0 ): string {
	// Vidéos intégrées : leur adresse seule sur une ligne, que WordPress intègre lui-même.
	$html = (string) preg_replace_callback(
		'#<iframe[^>]+src="([^"]+)"[^>]*>\s*</iframe>#i',
		static function ( array $m ): string {
			$src = html_entity_decode( $m[1] );
			if ( preg_match( '#youtube(?:-nocookie)?\.com/embed/([\w-]{6,})#', $src, $v ) ) {
				return "\n\nhttps://www.youtube.com/watch?v={$v[1]}\n\n";
			}
			if ( preg_match( '#player\.vimeo\.com/video/(\d+)#', $src, $v ) ) {
				return "\n\nhttps://vimeo.com/{$v[1]}\n\n";
			}
			return '';
		},
		$html
	);
	$html = (string) preg_replace( '#<(script|style|noscript)\b[^>]*>.*?</\1>#is', '', $html );
	$html = wp_kses_post( $html );
	// Images de l'ancien site : rapatriées, sans srcset (qui pointait vers l'ancien site).
	$html = (string) preg_replace_callback(
		'#<img\b[^>]*>#i',
		static function ( array $m ) use ( $source, $post_id, &$images ): string {
			$tag = $m[0];
			if ( ! preg_match( '#\ssrc="([^"]+)"#i', $tag, $src ) ) {
				return '';
			}
			$alt = preg_match( '#\salt="([^"]*)"#i', $tag, $a ) ? html_entity_decode( $a[1] ) : '';
			$id  = bigm_reprise_image( $src[1], $source, $post_id, $alt );
			if ( ! $id ) {
				return str_contains( $src[1], (string) wp_parse_url( $source, PHP_URL_HOST ) ) ? '' : $tag;
			}
			++$images;
			return wp_get_attachment_image( $id, 'large', false, array( 'alt' => $alt ) );
		},
		$html
	);
	// Liens vers les fichiers de l'ancien site (images agrandies) : vers la copie reprise.
	$html = (string) preg_replace_callback(
		'#href="(' . preg_quote( untrailingslashit( $source ), '#' ) . '/wp-content/uploads/[^"]+)"#i',
		static function ( array $m ) use ( $source, $post_id ): string {
			$id = bigm_reprise_image( $m[1], $source, $post_id );
			return 'href="' . esc_url( $id ? (string) wp_get_attachment_url( $id ) : $m[1] ) . '"';
		},
		$html
	);
	return trim( (string) preg_replace( '#<p[^>]*>(\s|&nbsp;|<br\s*/?>)*</p>#i', '', $html ) );
}

/**
 * Reprend un article de l'ancien site.
 *
 * @param array<string, mixed> $a      Article de l'API (avec _embedded).
 * @param string               $source Adresse de l'ancien site.
 * @return array{etat: string, images: int, id: int} etat : cree, deja, erreur.
 */
function bigm_reprise_article( array $a, string $source ): array {
	$ancien = (int) ( $a['id'] ?? 0 );
	$slug   = sanitize_title( (string) ( $a['slug'] ?? '' ) );
	$deja   = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bigm_ancien_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $ancien, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! $deja && '' !== $slug ) {
		$par_slug = bigm_post_par_adresse( $slug, 'post' );
		$deja     = $par_slug ? array( $par_slug->ID ) : array();
	}
	if ( $deja ) {
		return array(
			'etat'   => 'deja',
			'images' => 0,
			'id'     => (int) $deja[0],
		);
	}

	$termes = array_merge( array(), ...array_values( (array) ( $a['_embedded']['wp:term'] ?? array() ) ) );
	$cats   = array();
	$tags   = array();
	foreach ( $termes as $t ) {
		if ( 'category' === ( $t['taxonomy'] ?? '' ) ) {
			$cats[] = (string) $t['slug'];
			if ( in_array( $t['slug'], array( 'print', 'web', 'radio', 'tv' ), true ) ) {
				$tags[] = html_entity_decode( (string) $t['name'] );
			}
		} elseif ( 'post_tag' === ( $t['taxonomy'] ?? '' ) ) {
			$tags[] = html_entity_decode( (string) $t['name'] );
		}
	}
	$presse    = (bool) array_intersect( $cats, bigm_reprise_categories_presse() );
	$categorie = get_term_by( 'slug', $presse ? 'presse' : 'news', 'category' );
	if ( ! $categorie ) {
		$cree      = wp_insert_term( $presse ? 'Presse' : 'News', 'category', array( 'slug' => $presse ? 'presse' : 'news' ) );
		$categorie = is_wp_error( $cree ) ? null : get_term( (int) $cree['term_id'], 'category' );
	}

	$id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => wp_strip_all_tags( html_entity_decode( (string) ( $a['title']['rendered'] ?? '' ), ENT_QUOTES ) ),
			'post_name'     => $slug,
			'post_date'     => str_replace( 'T', ' ', (string) ( $a['date'] ?? '' ) ),
			'post_date_gmt' => str_replace( 'T', ' ', (string) ( $a['date_gmt'] ?? '' ) ),
			'post_excerpt'  => trim( wp_strip_all_tags( html_entity_decode( (string) ( $a['excerpt']['rendered'] ?? '' ), ENT_QUOTES ) ) ),
			'post_content'  => '',
			'post_category' => $categorie ? array( (int) $categorie->term_id ) : array(),
			'tags_input'    => array_values( array_unique( $tags ) ),
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return array(
			'etat'   => 'erreur',
			'images' => 0,
			'id'     => 0,
		);
	}
	update_post_meta( $id, '_bigm_ancien_id', $ancien );
	update_post_meta( $id, '_bigm_ancien_url', esc_url_raw( (string) ( $a['link'] ?? '' ) ) );

	$images = 0;
	$texte  = bigm_reprise_texte( (string) ( $a['content']['rendered'] ?? '' ), $source, $id, $images );
	wp_update_post(
		array(
			'ID'           => $id,
			'post_content' => $texte,
		)
	);
	$une = $a['_embedded']['wp:featuredmedia'][0] ?? array();
	if ( ! empty( $une['source_url'] ) ) {
		// Sans texte alternatif, la vignette (un lien) prend le titre de l'article.
		$alt   = trim( (string) ( $une['alt_text'] ?? '' ) );
		$media = bigm_reprise_image( (string) $une['source_url'], $source, $id, '' !== $alt ? $alt : (string) get_post_field( 'post_title', $id, 'raw' ) );
		if ( $media ) {
			set_post_thumbnail( $id, $media );
			++$images;
		}
	}
	return array(
		'etat'   => 'cree',
		'images' => $images,
		'id'     => (int) $id,
	);
}

/**
 * Traite un lot (une page de l'API) et met à jour le compte rendu.
 *
 * @param string $source Adresse de l'ancien site.
 * @param int    $page   Page.
 * @return array<string, mixed> Compte rendu cumulé (option bigm_reprise).
 */
function bigm_reprise_lot( string $source, int $page ): array {
	$cr = 1 === $page ? array(
		'source'  => $source,
		'debut'   => current_time( 'mysql' ),
		'cree'    => 0,
		'deja'    => 0,
		'erreur'  => 0,
		'images'  => 0,
		'pages'   => 0,
		'page'    => 0,
		'fini'    => false,
		'message' => '',
	) : (array) get_option( 'bigm_reprise', array() );
	$lu = bigm_reprise_lire( $source, $page );
	if ( is_wp_error( $lu ) ) {
		$cr['message'] = $lu->get_error_message();
		$cr['fini']    = true;
		update_option( 'bigm_reprise', $cr, false );
		return $cr;
	}
	foreach ( $lu['articles'] as $a ) {
		$r                = bigm_reprise_article( (array) $a, $source );
		$cr[ $r['etat'] ] = (int) ( $cr[ $r['etat'] ] ?? 0 ) + 1;
		$cr['images']     = (int) $cr['images'] + $r['images'];
	}
	$cr['pages'] = max( (int) ( $cr['pages'] ?? 0 ), $lu['total_pages'] );
	$cr['page']  = $page;
	$cr['fini']  = ! $lu['articles'] || $page >= $lu['total_pages'];
	update_option( 'bigm_reprise', $cr, false );
	return $cr;
}

/*
 * Page Outils → Reprise des articles, et lots enchaînés (une page par lot, rechargée seule).
 */
add_action(
	'admin_menu',
	static function () {
		add_management_page( 'Reprise des articles', 'Reprise des articles', 'manage_options', 'bigm-reprise', 'bigm_reprise_page' );
	}
);

/**
 * Page de la reprise.
 */
function bigm_reprise_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$cr     = (array) get_option( 'bigm_reprise', array() );
	$source = isset( $_GET['source'] ) ? esc_url_raw( wp_unslash( $_GET['source'] ) ) : (string) ( $cr['source'] ?? 'https://bigm-burger.fr' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- vérifié ci-dessous avant tout traitement.
	$page   = isset( $_GET['lot'] ) ? absint( $_GET['lot'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap"><h1>Reprise des articles de l’ancien site</h1>';
	if ( $page && check_admin_referer( 'bigm_reprise' ) ) {
		set_time_limit( 300 );
		$cr = bigm_reprise_lot( $source, $page );
		if ( ! $cr['fini'] ) {
			$suite = wp_nonce_url(
				add_query_arg(
					array(
						'page'   => 'bigm-reprise',
						'source' => rawurlencode( $source ),
						'lot'    => $page + 1,
					),
					admin_url( 'tools.php' )
				),
				'bigm_reprise'
			);
			printf( '<p>Lot %d sur %d traité… la page se recharge seule. <a href="%s">Continuer</a></p>', (int) $page, (int) $cr['pages'], esc_url( $suite ) );
			echo '<meta http-equiv="refresh" content="1;url=' . esc_url( $suite ) . '">';
		}
	}
	if ( $cr ) {
		echo '<div class="notice notice-' . ( empty( $cr['message'] ) ? 'info' : 'error' ) . '"><p>';
		printf(
			'Depuis %1$s (%2$s) : %3$d articles repris, %4$d déjà présents, %5$d en erreur, %6$d images.%7$s',
			esc_html( (string) $cr['source'] ),
			esc_html( mysql2date( 'j F Y H:i', (string) $cr['debut'] ) ),
			(int) $cr['cree'],
			(int) $cr['deja'],
			(int) $cr['erreur'],
			(int) $cr['images'],
			! empty( $cr['fini'] ) ? ' <strong>Terminé.</strong>' : ''
		);
		if ( ! empty( $cr['message'] ) ) {
			echo '<br>' . esc_html( (string) $cr['message'] );
		}
		echo '</p></div>';
	}
	?>
	<p>Recrée ici les articles de l’ancien site WordPress, avec leur adresse, leur date et leurs images : catégorie <strong>Presse</strong> pour les anciennes rubriques Presse, Print, Web, Radio et TV, <strong>News</strong> pour les autres. Les articles déjà repris ne sont pas recréés.</p>
	<form method="get" action="<?php echo esc_url( admin_url( 'tools.php' ) ); ?>">
		<input type="hidden" name="page" value="bigm-reprise">
		<input type="hidden" name="lot" value="1">
		<?php wp_nonce_field( 'bigm_reprise', '_wpnonce', false ); ?>
		<p><label for="bigm_source">Adresse de l’ancien site</label><br>
			<input type="url" id="bigm_source" name="source" value="<?php echo esc_attr( $source ); ?>" class="regular-text" required></p>
		<p><button class="button button-primary">Lancer la reprise</button></p>
		<p class="description">5 articles par lot ; compter quelques minutes pour une centaine d’articles. L’ancien site doit être en ligne et son API publique ouverte.</p>
	</form>
	</div>
	<?php
}
