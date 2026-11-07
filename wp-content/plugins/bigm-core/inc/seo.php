<?php
/**
 * Référencement sans extension (brief, § 10) : titre et description de chaque page, partage
 * (Open Graph, Twitter), adresse canonique des listes, données structurées, plans du site natifs.
 *
 * - Bloc « Référencement » sur les pages, articles et produits : titre affiché dans Google,
 *   description, image de partage, « ne pas indexer ». Mêmes champs (sans image) sur les
 *   catégories de la carte et d'articles. Sans saisie, des valeurs sont déduites du contenu.
 * - Image de partage par défaut : Réglages Big M → Référencement.
 * - JSON-LD : Organization et WebSite sur l'accueil ; MenuItem (100 % halal, valeurs
 *   nutritionnelles) et fil d'Ariane sur les produits ; fil d'Ariane sur les catégories.
 *   Les restaurants ont celles du plugin StoreLocatore (Restaurant, voir inc/restaurants.php).
 * - Plans du site de WordPress (/wp-sitemap.xml) : sans les comptes, sans les pages exclues.
 *
 * Tout se coupe si une extension de référencement est active (Yoast, Rank Math, SEOPress,
 * The SEO Framework, All in One SEO, Slim SEO) : elle prend le relais.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le module est-il actif (aucune extension de référencement) ? Filtre « bigm_seo_actif ».
 */
function bigm_seo_actif(): bool {
	$extension = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' )
		|| defined( 'THE_SEO_FRAMEWORK_VERSION' ) || defined( 'AIOSEO_VERSION' ) || class_exists( 'SlimSEO\\Plugin' );
	return (bool) apply_filters( 'bigm_seo_actif', ! $extension );
}

/**
 * Types de contenu qui ont le bloc Référencement.
 *
 * @return string[]
 */
function bigm_seo_types(): array {
	return array( 'page', 'post', 'bigm_produit' );
}

/**
 * Taxonomies qui ont les champs de référencement.
 *
 * @return string[]
 */
function bigm_seo_taxonomies(): array {
	return array( 'bigm_categorie', 'category' );
}

/**
 * Texte court tiré d'un contenu : sans balises ni codes, coupé au mot, 155 caractères au plus.
 *
 * @param string $html Contenu.
 */
function bigm_seo_extrait( string $html ): string {
	$texte = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
	$texte = trim( str_replace( array( '[', ']' ), '', $texte ) );
	if ( mb_strlen( $texte ) <= 155 ) {
		return $texte;
	}
	$coupe = mb_substr( $texte, 0, 154 );
	$coupe = preg_replace( '/\s+\S*$/u', '', $coupe );
	return rtrim( $coupe, ' ,;:.' ) . '…';
}

/**
 * Ce que la page affichée dit d'elle-même aux moteurs et aux réseaux, ou null quand ce n'est
 * pas à ce module de le dire (restaurants du plugin, recherche, 404). Calculé une fois par page.
 *
 * @param bool $recalculer Ignorer le calcul déjà fait (tests).
 * @return array{titre: string, description: string, image: int, url: string, type: string, noindex: bool}|null
 */
function bigm_seo_page( bool $recalculer = false ): ?array {
	static $cache = array();
	$cle = get_queried_object_id() . '|' . (int) get_query_var( 'paged' ) . '|' . (int) is_front_page();
	if ( $recalculer || ! array_key_exists( $cle, $cache ) ) {
		$cache[ $cle ] = bigm_seo_calcul();
	}
	return $cache[ $cle ];
}

/**
 * Calcul de bigm_seo_page() (sans cache).
 *
 * @return array<string, mixed>|null
 */
function bigm_seo_calcul(): ?array {
	if ( is_singular( array( 'tfmsl_store' ) ) || is_search() || is_404() || is_feed() ) {
		return null;
	}
	$page = array(
		'titre'       => '',
		'description' => '',
		'image'       => 0,
		'url'         => '',
		'type'        => 'website',
		'noindex'     => false,
	);
	$objet_id = 0;
	if ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
		$objet_id = (int) get_option( 'page_on_front' );
	} elseif ( is_home() ) {
		$objet_id = (int) get_option( 'page_for_posts' );
	} elseif ( is_singular() ) {
		$objet_id = (int) get_queried_object_id();
	}

	if ( $objet_id && in_array( get_post_type( $objet_id ), bigm_seo_types(), true ) ) {
		$post                = get_post( $objet_id );
		$page['titre']       = trim( (string) get_post_meta( $objet_id, '_bigm_seo_titre', true ) );
		$page['description'] = trim( (string) get_post_meta( $objet_id, '_bigm_seo_description', true ) );
		$page['image']       = (int) get_post_meta( $objet_id, '_bigm_seo_image', true );
		$page['noindex']     = '1' === get_post_meta( $objet_id, '_bigm_seo_noindex', true );
		if ( '' === $page['description'] ) {
			$page['description'] = bigm_seo_extrait( has_excerpt( $post ) ? get_the_excerpt( $post ) : (string) $post->post_content );
		}
		if ( '' === $page['description'] && is_front_page() ) {
			$page['description'] = (string) get_bloginfo( 'description' );
		}
		$page['image'] = $page['image'] ? $page['image'] : (int) get_post_thumbnail_id( $post );
		$page['url']   = is_home() ? (string) get_permalink( $objet_id ) : (string) get_permalink( $post );
		$page['type']  = 'post' === $post->post_type ? 'article' : 'website';
		if ( is_front_page() ) {
			$page['url'] = home_url( '/' );
		}
	} elseif ( is_post_type_archive( 'bigm_produit' ) ) {
		$page['description'] = bigm_seo_extrait( (string) bigm_reglage( 'carte_texte' ) );
		$page['url']         = (string) get_post_type_archive_link( 'bigm_produit' );
	} elseif ( is_tax( bigm_seo_taxonomies() ) || is_category() ) {
		$term                = get_queried_object();
		$page['titre']       = trim( (string) get_term_meta( $term->term_id, '_bigm_seo_titre', true ) );
		$page['description'] = trim( (string) get_term_meta( $term->term_id, '_bigm_seo_description', true ) );
		$page['description'] = '' !== $page['description'] ? $page['description'] : bigm_seo_extrait( (string) $term->description );
		$lien                = get_term_link( $term );
		$page['url']         = is_string( $lien ) ? $lien : '';
		if ( 'bigm_categorie' === $term->taxonomy && function_exists( 'bigm_categorie' ) ) {
			$c             = bigm_categorie( $term );
			$page['image'] = $c ? bigm_image_categorie( $c ) : 0;
		}
	} else {
		return null;
	}
	if ( is_paged() && $page['url'] ) {
		$page['url'] = (string) get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
	}
	$page['image'] = $page['image'] ? $page['image'] : (int) bigm_reglage( 'partage_image' );
	$page['image'] = $page['image'] ? $page['image'] : (int) get_theme_mod( 'custom_logo' );
	return $page;
}

/*
 * Titre de l'onglet et de Google : celui saisi dans le bloc Référencement, tel quel.
 */
add_filter(
	'pre_get_document_title',
	static function ( string $titre ): string {
		if ( ! bigm_seo_actif() ) {
			return $titre;
		}
		$page = bigm_seo_page();
		return $page && '' !== $page['titre'] ? $page['titre'] : $titre;
	},
	20
);

/*
 * Pages exclues : noindex (WordPress ajoute « follow »).
 */
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		if ( bigm_seo_actif() ) {
			$page = bigm_seo_page();
			if ( $page && $page['noindex'] ) {
				$robots['noindex'] = true;
			}
		}
		return $robots;
	}
);

/*
 * Description, partage et adresse canonique, en haut du <head>.
 */
add_action(
	'wp_head',
	static function () {
		if ( ! bigm_seo_actif() ) {
			return;
		}
		$page = bigm_seo_page();
		if ( ! $page ) {
			return;
		}
		$titre = '' !== $page['titre'] ? $page['titre'] : wp_get_document_title();
		$sorties = array();
		if ( '' !== $page['description'] ) {
			$sorties[] = '<meta name="description" content="' . esc_attr( $page['description'] ) . '">';
		}
		// WordPress écrit la balise canonique des contenus seuls ; celles des listes sont ici.
		if ( ! is_singular() && $page['url'] ) {
			$sorties[] = '<link rel="canonical" href="' . esc_url( $page['url'] ) . '">';
		}
		$og = array(
			'og:locale'    => 'fr_FR',
			'og:type'      => $page['type'],
			'og:site_name' => get_bloginfo( 'name' ),
			'og:title'     => $titre,
			'og:url'       => $page['url'],
		);
		if ( '' !== $page['description'] ) {
			$og['og:description'] = $page['description'];
		}
		$image = $page['image'] ? wp_get_attachment_image_src( $page['image'], 'large' ) : false;
		if ( $image ) {
			$og['og:image']        = $image[0];
			$og['og:image:width']  = (string) $image[1];
			$og['og:image:height'] = (string) $image[2];
			$alt                   = (string) get_post_meta( $page['image'], '_wp_attachment_image_alt', true );
			if ( '' !== $alt ) {
				$og['og:image:alt'] = $alt;
			}
		}
		if ( 'article' === $page['type'] && is_singular() ) {
			$og['article:published_time'] = (string) get_the_date( DATE_W3C );
			$og['article:modified_time']  = (string) get_the_modified_date( DATE_W3C );
		}
		foreach ( $og as $propriete => $valeur ) {
			if ( '' !== (string) $valeur ) {
				$sorties[] = '<meta property="' . esc_attr( $propriete ) . '" content="' . esc_attr( (string) $valeur ) . '">';
			}
		}
		$sorties[] = '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">';
		echo "\n" . implode( "\n", $sorties ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé à la construction.
	},
	1
);

/* ------------------------------------------------------------------ Données structurées */

/**
 * Objets JSON-LD de la page affichée.
 *
 * @return array<int, array<string, mixed>>
 */
function bigm_seo_donnees_structurees(): array {
	$objets  = array();
	$accueil = array(
		'@type' => 'ListItem',
		'name'  => 'Accueil',
		'item'  => home_url( '/' ),
	);
	$carte   = array(
		'@type' => 'ListItem',
		'name'  => (string) bigm_reglage( 'carte_titre' ),
		'item'  => (string) get_post_type_archive_link( 'bigm_produit' ),
	);
	$ariane  = static function ( array $elements ): array {
		foreach ( $elements as $i => &$e ) {
			$e['position'] = $i + 1;
		}
		return array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $elements,
		);
	};

	if ( is_front_page() ) {
		$reseaux = array();
		foreach ( array( 'instagram_url', 'facebook_url', 'x_url', 'tiktok_url', 'snapchat_url', 'youtube_url' ) as $cle ) {
			$url = (string) bigm_reglage( $cle );
			if ( '' !== $url ) {
				$reseaux[] = $url;
			}
		}
		$logo = (int) get_theme_mod( 'custom_logo' );
		$org  = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Organization',
			'@id'      => home_url( '/#organisation' ),
			'name'     => get_bloginfo( 'name' ),
			'url'      => home_url( '/' ),
		);
		if ( $logo && wp_get_attachment_url( $logo ) ) {
			$org['logo'] = wp_get_attachment_url( $logo );
		}
		if ( $reseaux ) {
			$org['sameAs'] = $reseaux;
		}
		$objets[] = $org;
		$objets[] = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'WebSite',
			'@id'        => home_url( '/#site' ),
			'name'       => get_bloginfo( 'name' ),
			'url'        => home_url( '/' ),
			'inLanguage' => 'fr-FR',
			'publisher'  => array( '@id' => home_url( '/#organisation' ) ),
		);
	} elseif ( is_singular( 'bigm_produit' ) && function_exists( 'bigm_produit' ) ) {
		$p = bigm_produit( get_queried_object_id() );
		if ( $p ) {
			$plat = array(
				'@context'        => 'https://schema.org',
				'@type'           => 'MenuItem',
				'name'            => $p['titre'],
				'url'             => $p['url'],
				'suitableForDiet' => 'https://schema.org/HalalDiet',
			);
			$texte = bigm_seo_extrait( (string) $p['description'] );
			if ( '' !== $texte ) {
				$plat['description'] = $texte;
			}
			if ( $p['image_id'] && wp_get_attachment_url( $p['image_id'] ) ) {
				$plat['image'] = wp_get_attachment_url( $p['image_id'] );
			}
			// Valeurs pour une portion, telles que saisies (« 12,5 » → 12.5).
			$nutrition = array();
			$unites    = array(
				'kcal'      => array( 'calories', 'kcal' ),
				'lipides'   => array( 'fatContent', 'g' ),
				'satures'   => array( 'saturatedFatContent', 'g' ),
				'glucides'  => array( 'carbohydrateContent', 'g' ),
				'sucres'    => array( 'sugarContent', 'g' ),
				'proteines' => array( 'proteinContent', 'g' ),
				'sel'       => array( 'sodiumContent', 'mg' ),
			);
			foreach ( $unites as $cle => $u ) {
				$v = str_replace( ',', '.', (string) ( $p['nutrition']['lignes'][ $cle ]['portion'] ?? '' ) );
				if ( '' === $v || ! is_numeric( $v ) ) {
					continue;
				}
				// schema.org ne connaît que le sodium : sel = sodium × 2,5 (règlement UE 1169/2011).
				$nutrition[ $u[0] ] = 'sel' === $cle ? round( (float) $v / 2.5 * 1000 ) . ' mg' : $v . ' ' . $u[1];
			}
			if ( $nutrition ) {
				$plat['nutrition'] = array( '@type' => 'NutritionInformation' ) + $nutrition;
				if ( '' !== $p['nutrition']['portion_g'] ) {
					$plat['nutrition']['servingSize'] = str_replace( ',', '.', $p['nutrition']['portion_g'] ) . ' g';
				}
			}
			$objets[] = $plat;
			$elements = array( $accueil, $carte );
			$cat      = function_exists( 'bigm_categorie_principale' ) ? bigm_categorie_principale( $p['id'] ) : null;
			if ( $cat ) {
				$racine     = bigm_categorie_racine( $cat );
				$elements[] = array(
					'@type' => 'ListItem',
					'name'  => $racine->name,
					'item'  => (string) get_term_link( $racine ),
				);
			}
			$elements[] = array(
				'@type' => 'ListItem',
				'name'  => $p['titre'],
			);
			$objets[]   = $ariane( $elements );
		}
	} elseif ( is_tax( 'bigm_categorie' ) ) {
		$term     = get_queried_object();
		$elements = array( $accueil, $carte );
		foreach ( array_reverse( get_ancestors( $term->term_id, 'bigm_categorie', 'taxonomy' ) ) as $parent_id ) {
			$parent     = get_term( $parent_id, 'bigm_categorie' );
			$elements[] = array(
				'@type' => 'ListItem',
				'name'  => $parent->name,
				'item'  => (string) get_term_link( $parent ),
			);
		}
		$elements[] = array(
			'@type' => 'ListItem',
			'name'  => $term->name,
		);
		$objets[]   = $ariane( $elements );
	}
	return $objets;
}

add_action(
	'wp_head',
	static function () {
		if ( ! bigm_seo_actif() ) {
			return;
		}
		foreach ( bigm_seo_donnees_structurees() as $objet ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $objet, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
		}
	},
	2
);

/* ------------------------------------------------------------------ Plans du site */

add_filter(
	'wp_sitemaps_add_provider',
	// Pas de plan des comptes : il donne les identifiants des auteurs.
	static fn( $provider, string $nom ) => 'users' === $nom ? false : $provider,
	10,
	2
);

add_filter(
	'wp_sitemaps_posts_query_args',
	static function ( array $args ): array {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'OR',
			array(
				'key'     => '_bigm_seo_noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_bigm_seo_noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);
		return $args;
	}
);

add_filter(
	'wp_sitemaps_taxonomies',
	static function ( array $taxonomies ): array {
		unset( $taxonomies['post_format'] );
		return $taxonomies;
	}
);

/* ------------------------------------------------------------------ Admin */

add_action(
	'add_meta_boxes',
	static function ( string $type ) {
		if ( bigm_seo_actif() && in_array( $type, bigm_seo_types(), true ) ) {
			add_meta_box( 'bigm_seo', 'Référencement', 'bigm_seo_box', $type, 'normal', 'low' );
		}
	}
);

/**
 * Bloc Référencement d'une page, d'un article ou d'un produit.
 *
 * @param WP_Post $post Contenu.
 */
function bigm_seo_box( WP_Post $post ): void {
	wp_nonce_field( 'bigm_seo', 'bigm_seo_nonce' );
	$titre = (string) get_post_meta( $post->ID, '_bigm_seo_titre', true );
	$desc  = (string) get_post_meta( $post->ID, '_bigm_seo_description', true );
	$auto  = bigm_seo_extrait( has_excerpt( $post ) ? $post->post_excerpt : (string) $post->post_content );
	?>
	<p><label for="bigm_seo_titre"><strong>Titre dans Google et l’onglet</strong></label><br>
		<input type="text" id="bigm_seo_titre" name="bigm_seo[titre]" value="<?php echo esc_attr( $titre ); ?>" class="large-text" maxlength="70" placeholder="<?php echo esc_attr( get_the_title( $post ) . ' · ' . get_bloginfo( 'name' ) ); ?>">
		<span class="description">60 caractères environ. Vide : titre de la page, suivi de « · <?php echo esc_html( get_bloginfo( 'name' ) ); ?> ».</span></p>
	<p><label for="bigm_seo_description"><strong>Description</strong></label><br>
		<textarea id="bigm_seo_description" name="bigm_seo[description]" rows="3" class="large-text" maxlength="200" placeholder="<?php echo esc_attr( $auto ); ?>"><?php echo esc_textarea( $desc ); ?></textarea>
		<span class="description">150 caractères environ, affichés sous le titre dans Google et dans les partages. Vide : début du texte.</span></p>
	<?php
	bigm_champ_media( 'bigm_seo[image]', (int) get_post_meta( $post->ID, '_bigm_seo_image', true ), 'image', 'Image de partage', 'Facebook, WhatsApp, LinkedIn… 1200 × 630 conseillé. Vide : image mise en avant, sinon celle des Réglages Big M.' );
	?>
	<p><label><input type="checkbox" name="bigm_seo[noindex]" value="1" <?php checked( '1', get_post_meta( $post->ID, '_bigm_seo_noindex', true ) ); ?>> Ne pas montrer cette page dans Google (et la retirer du plan du site)</label></p>
	<?php
}

add_action(
	'save_post',
	static function ( int $post_id, WP_Post $post ) {
		if ( ! isset( $_POST['bigm_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bigm_seo_nonce'] ), 'bigm_seo' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! in_array( $post->post_type, bigm_seo_types(), true ) ) {
			return;
		}
		$in = isset( $_POST['bigm_seo'] ) && is_array( $_POST['bigm_seo'] ) ? wp_unslash( $_POST['bigm_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ.
		$valeurs = array(
			'_bigm_seo_titre'       => sanitize_text_field( (string) ( $in['titre'] ?? '' ) ),
			'_bigm_seo_description' => sanitize_textarea_field( (string) ( $in['description'] ?? '' ) ),
			'_bigm_seo_image'       => bigm_nettoyer_media( $in['image'] ?? 0, 'image/' ),
			'_bigm_seo_noindex'     => empty( $in['noindex'] ) ? '' : '1',
		);
		foreach ( $valeurs as $cle => $v ) {
			if ( '' === $v || 0 === $v ) {
				delete_post_meta( $post_id, $cle );
			} else {
				update_post_meta( $post_id, $cle, $v );
			}
		}
	},
	10,
	2
);

/**
 * Champs de référencement d'une catégorie (formulaire de modification).
 *
 * @param WP_Term $term Catégorie.
 */
function bigm_seo_champs_terme( WP_Term $term ): void {
	wp_nonce_field( 'bigm_seo_terme', 'bigm_seo_terme_nonce' );
	?>
	<tr class="form-field"><th scope="row"><label for="bigm_seo_titre">Titre dans Google</label></th>
		<td><input type="text" id="bigm_seo_titre" name="bigm_seo[titre]" value="<?php echo esc_attr( (string) get_term_meta( $term->term_id, '_bigm_seo_titre', true ) ); ?>" maxlength="70">
		<p class="description">60 caractères environ. Vide : titre de la page, suivi du nom du site.</p></td></tr>
	<tr class="form-field"><th scope="row"><label for="bigm_seo_description">Description pour Google</label></th>
		<td><textarea id="bigm_seo_description" name="bigm_seo[description]" rows="3" maxlength="200"><?php echo esc_textarea( (string) get_term_meta( $term->term_id, '_bigm_seo_description', true ) ); ?></textarea>
		<p class="description">150 caractères environ. Vide : description de la catégorie.</p></td></tr>
	<?php
}

foreach ( bigm_seo_taxonomies() as $bigm_taxonomie ) {
	add_action(
		"{$bigm_taxonomie}_edit_form_fields",
		static function ( WP_Term $term ) {
			if ( bigm_seo_actif() ) {
				bigm_seo_champs_terme( $term );
			}
		},
		20
	);
	add_action(
		"edited_{$bigm_taxonomie}",
		static function ( int $term_id ) {
			if ( ! isset( $_POST['bigm_seo_terme_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['bigm_seo_terme_nonce'] ), 'bigm_seo_terme' ) || ! current_user_can( 'manage_categories' ) ) {
				return;
			}
			$in = isset( $_POST['bigm_seo'] ) && is_array( $_POST['bigm_seo'] ) ? wp_unslash( $_POST['bigm_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé champ par champ.
			update_term_meta( $term_id, '_bigm_seo_titre', sanitize_text_field( (string) ( $in['titre'] ?? '' ) ) );
			update_term_meta( $term_id, '_bigm_seo_description', sanitize_textarea_field( (string) ( $in['description'] ?? '' ) ) );
		}
	);
}
