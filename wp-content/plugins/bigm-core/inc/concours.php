<?php
/**
 * Jeux concours : un jeu (partenaire, dates, lien pour jouer, visuels, lots, étapes, questions,
 * règlement, restaurants participants), affiché sur la page Jeu concours et sur la fiche de
 * chaque restaurant participant.
 *
 * - Admin : menu « Jeux concours » (type de contenu bigm_concours, aussi dans l'API REST).
 * - Page : widget Elementor « Big M — Jeu concours » ou [bigm_jeu_concours] : le jeu en cours
 *   (sinon le prochain, sinon le dernier terminé depuis moins de 30 jours), ou un texte d'attente.
 * - Fiche d'un restaurant participant : bande sous le bandeau (crochet tfmsl_after_store_hero de
 *   StoreLocatore, entre les promos et le food court), ou [bigm_jeu_concours_bande].
 * - Accueil (ou autre page) : la même bande en bannière, widget « Big M — Bannière jeu concours »
 *   ou [bigm_jeu_concours_banniere] ; rien hors des dates du jeu.
 * - Toutes les autres pages : bouton flottant « Jeu concours en cours » qu'on peut fermer
 *   (assets/css/concours-bouton.css, assets/js/concours-bouton.js, filtre bigm_concours_bouton_flottant).
 * - Pages en cache : les dates sont dans le HTML (data-debut, data-fin) et le JS décide de ce
 *   qui s'affiche (compte à rebours, jeu terminé, bande cachée hors des dates). Le cache est
 *   vidé à l'enregistrement d'un jeu et à ses dates de début et de fin.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Champs d'un jeu : clé => [type, valeur par défaut].
 *
 * @return array<string, array{0: string, 1: mixed}>
 */
function bigm_concours_champs(): array {
	return array(
		'partenaire'   => array( 'string', '' ),
		'accroche'     => array( 'string', '' ),
		'condition'    => array( 'string', '' ),
		'debut'        => array( 'string', '' ),
		'fin'          => array( 'string', '' ),
		'tirage'       => array( 'string', '' ),
		'lien'         => array( 'string', '' ),
		'bouton'       => array( 'string', 'Je joue' ),
		'etapes'       => array( 'string', '' ),
		'lots'         => array( 'string', '' ),
		'faq'          => array( 'string', '' ),
		'mentions'     => array( 'string', '' ),
		'story'        => array( 'integer', 0 ),
		'video_mp4'    => array( 'integer', 0 ),
		'video_webm'   => array( 'integer', 0 ),
		'video_poster' => array( 'integer', 0 ),
		'reglement'    => array( 'integer', 0 ),
		'couleur1'     => array( 'string', '#2a1650' ),
		'couleur2'     => array( 'string', '#c8400a' ),
		'tous'         => array( 'string', '0' ),
		'restaurants'  => array( 'array', array() ),
	);
}

/**
 * Valeur nettoyée d'un champ.
 *
 * @param string $cle    Champ.
 * @param mixed  $valeur Valeur reçue.
 * @return mixed
 */
function bigm_concours_nettoyer( string $cle, $valeur ) {
	switch ( $cle ) {
		case 'debut':
		case 'fin':
			$valeur = str_replace( 'T', ' ', trim( (string) $valeur ) );
			return preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $valeur ) ? $valeur : '';
		case 'tirage':
			$valeur = trim( (string) $valeur );
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valeur ) ? $valeur : '';
		case 'lien':
			return esc_url_raw( trim( (string) $valeur ) );
		case 'story':
		case 'video_poster':
			return bigm_nettoyer_media( $valeur, 'image/' );
		case 'video_mp4':
			return bigm_nettoyer_media( $valeur, 'video/mp4' );
		case 'video_webm':
			return bigm_nettoyer_media( $valeur, 'video/webm' );
		case 'reglement':
			return bigm_nettoyer_media( $valeur, 'application/pdf' );
		case 'couleur1':
		case 'couleur2':
			return (string) sanitize_hex_color( (string) $valeur );
		case 'tous':
			return ! empty( $valeur ) && '0' !== (string) $valeur ? '1' : '0';
		case 'restaurants':
			return array_values( array_unique( array_filter( array_map( 'absint', (array) $valeur ) ) ) );
		case 'partenaire':
		case 'bouton':
		case 'condition':
			return sanitize_text_field( (string) $valeur );
		default:
			return sanitize_textarea_field( (string) $valeur );
	}
}

add_action(
	'init',
	static function () {
		register_post_type(
			'bigm_concours',
			array(
				'labels'        => array(
					'name'               => 'Jeux concours',
					'singular_name'      => 'Jeu concours',
					'menu_name'          => 'Jeux concours',
					'all_items'          => 'Tous les jeux',
					'add_new'            => 'Ajouter',
					'add_new_item'       => 'Ajouter un jeu concours',
					'edit_item'          => 'Modifier le jeu concours',
					'new_item'           => 'Nouveau jeu concours',
					'search_items'       => 'Rechercher un jeu',
					'not_found'          => 'Aucun jeu concours',
					'not_found_in_trash' => 'Aucun jeu concours dans la corbeille',
				),
				'public'        => false,
				'show_ui'       => true,
				'show_in_rest'  => true,
				'menu_position' => 23,
				'menu_icon'     => 'dashicons-awards',
				'supports'      => array( 'title', 'revisions', 'custom-fields' ),
			)
		);
		foreach ( bigm_concours_champs() as $cle => list( $type, $defaut ) ) {
			$args = array(
				'type'              => $type,
				'single'            => true,
				'default'           => $defaut,
				'show_in_rest'      => 'array' === $type ? array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) ) : true,
				'sanitize_callback' => static fn( $v ) => bigm_concours_nettoyer( $cle, $v ),
				'auth_callback'     => static fn( $permis, $meta, $post_id ): bool => current_user_can( 'edit_post', (int) $post_id ),
			);
			register_post_meta( 'bigm_concours', '_bigm_jc_' . $cle, $args );
		}
	}
);

/**
 * Horodatage d'une date saisie (heure de Paris), ou 0.
 *
 * @param string $date « Y-m-d H:i » ou « Y-m-d ».
 * @param bool   $fin_de_journee Pour une date seule : 23:59:59 plutôt que 00:00.
 */
function bigm_concours_ts( string $date, bool $fin_de_journee = false ): int {
	if ( '' === $date ) {
		return 0;
	}
	$format = strlen( $date ) > 10 ? 'Y-m-d H:i' : 'Y-m-d';
	$d      = DateTimeImmutable::createFromFormat( '!' . $format, $date, wp_timezone() );
	if ( ! $d ) {
		return 0;
	}
	if ( 'Y-m-d H:i' === $format ) {
		$d = $d->setTime( (int) $d->format( 'H' ), (int) $d->format( 'i' ), $fin_de_journee ? 59 : 0 );
	} elseif ( $fin_de_journee ) {
		$d = $d->setTime( 23, 59, 59 );
	}
	return $d->getTimestamp();
}

/**
 * Lignes d'un texte « une par ligne ».
 *
 * @param string $texte Texte.
 * @return string[]
 */
function bigm_concours_lignes( string $texte ): array {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $texte ) ) ) );
}

/**
 * Toutes les données d'un jeu, ou null.
 *
 * @param int|WP_Post $post Jeu.
 * @return array<string, mixed>|null
 */
function bigm_concours( $post ): ?array {
	$post = get_post( $post );
	if ( ! $post || 'bigm_concours' !== $post->post_type ) {
		return null;
	}
	$c = array();
	foreach ( bigm_concours_champs() as $cle => list( $type, $defaut ) ) {
		$valeur    = get_post_meta( $post->ID, '_bigm_jc_' . $cle, true );
		$c[ $cle ] = ( '' === $valeur || null === $valeur || ( 'array' === $type && ! is_array( $valeur ) ) ) ? $defaut : $valeur;
	}
	$c['id']        = $post->ID;
	$c['titre']     = get_the_title( $post );
	$c['debut_ts']  = bigm_concours_ts( (string) $c['debut'] );
	$c['fin_ts']    = bigm_concours_ts( (string) $c['fin'], true );
	$c['tirage_ts'] = bigm_concours_ts( (string) $c['tirage'] );

	// Étapes : « Titre : texte », une par ligne.
	$c['etapes_liste'] = array();
	foreach ( bigm_concours_lignes( (string) $c['etapes'] ) as $ligne ) {
		$parts                = preg_split( '/\s+:\s+/u', $ligne, 2 );
		$texte               = $parts[1] ?? $parts[0];
		$c['etapes_liste'][] = array(
			'titre' => isset( $parts[1] ) ? $parts[0] : '',
			'texte' => mb_strtoupper( mb_substr( $texte, 0, 1 ) ) . mb_substr( $texte, 1 ),
		);
	}
	// Lots : « Quantité | Lot | Précision », « * » devant la ligne pour le lot principal.
	$c['lots_liste'] = array();
	foreach ( bigm_concours_lignes( (string) $c['lots'] ) as $ligne ) {
		$principal         = str_starts_with( $ligne, '*' );
		$parts             = array_map( 'trim', explode( '|', ltrim( $ligne, '* ' ) ) );
		$c['lots_liste'][] = array(
			'quantite'  => count( $parts ) > 1 ? $parts[0] : '',
			'nom'       => count( $parts ) > 1 ? $parts[1] : $parts[0],
			'precision' => $parts[2] ?? '',
			'principal' => $principal,
		);
	}
	// Questions : blocs séparés par une ligne vide, la première ligne est la question.
	$c['faq_liste'] = array();
	foreach ( preg_split( '/(\r\n|\r|\n)\s*(\r\n|\r|\n)/', trim( (string) $c['faq'] ) ) as $bloc ) {
		$lignes = bigm_concours_lignes( $bloc );
		if ( count( $lignes ) > 1 ) {
			$c['faq_liste'][] = array(
				'question' => array_shift( $lignes ),
				'reponse'  => implode( "\n", $lignes ),
			);
		}
	}
	$c['video'] = bigm_video(
		array(
			'mp4'    => $c['video_mp4'],
			'webm'   => $c['video_webm'],
			'poster' => $c['video_poster'] ? $c['video_poster'] : $c['story'],
		)
	);
	$c['reglement_url'] = $c['reglement'] ? (string) wp_get_attachment_url( (int) $c['reglement'] ) : '';
	$c['restaurants']   = bigm_concours_restaurants( $c );
	return (array) apply_filters( 'bigm_concours', $c, $post );
}

/**
 * Restaurants participants publiés (tous, ou ceux cochés), dans l'ordre alphabétique.
 *
 * @param array<string, mixed> $c Jeu (champs bruts « tous » et « restaurants »).
 * @return int[]
 */
function bigm_concours_restaurants( array $c ): array {
	if ( ! post_type_exists( 'tfmsl_store' ) ) {
		return array();
	}
	$coches = array_map( 'intval', (array) ( $c['restaurants'] ?? array() ) );
	if ( '1' !== (string) ( $c['tous'] ?? '0' ) && ! $coches ) {
		return array();
	}
	$args = array(
		'post_type'      => 'tfmsl_store',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'fields'         => 'ids',
	);
	if ( '1' !== (string) ( $c['tous'] ?? '0' ) ) {
		$args['post__in'] = $coches;
	}
	return array_map( 'intval', get_posts( $args ) );
}

/**
 * État d'un jeu à un instant donné.
 *
 * @param array<string, mixed> $c          Jeu.
 * @param int|null             $maintenant Horodatage (tests), sinon maintenant.
 * @return string « a_venir », « en_cours » ou « termine ».
 */
function bigm_concours_etat( array $c, ?int $maintenant = null ): string {
	$maintenant = $maintenant ?? time();
	if ( $c['debut_ts'] && $maintenant < $c['debut_ts'] ) {
		return 'a_venir';
	}
	if ( $c['fin_ts'] && $maintenant > $c['fin_ts'] ) {
		return 'termine';
	}
	return 'en_cours';
}

/**
 * Jeux à montrer : publiés, ni terminés depuis plus de $apres jours, ni commençant dans plus de
 * $avant jours ; en cours d'abord, puis à venir, puis terminés.
 *
 * @param int      $avant      Jours avant le début.
 * @param int      $apres      Jours après la fin.
 * @param int|null $maintenant Horodatage (tests).
 * @return array<int, array<string, mixed>>
 */
function bigm_concours_a_montrer( int $avant = 30, int $apres = 30, ?int $maintenant = null ): array {
	$maintenant = $maintenant ?? time();
	$jeux       = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'bigm_concours',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	) as $p ) {
		$c = bigm_concours( $p );
		if ( ! $c ) {
			continue;
		}
		if ( $c['debut_ts'] && $c['debut_ts'] - $avant * DAY_IN_SECONDS > $maintenant ) {
			continue;
		}
		if ( $c['fin_ts'] && $c['fin_ts'] + $apres * DAY_IN_SECONDS < $maintenant ) {
			continue;
		}
		$c['etat'] = bigm_concours_etat( $c, $maintenant );
		$jeux[]    = $c;
	}
	$ordre = array(
		'en_cours' => 0,
		'a_venir'  => 1,
		'termine'  => 2,
	);
	usort( $jeux, static fn( $a, $b ) => array( $ordre[ $a['etat'] ], $a['fin_ts'] ) <=> array( $ordre[ $b['etat'] ], $b['fin_ts'] ) );
	return $jeux;
}

/**
 * Jeux d'un restaurant : en cours, ou qui commencent ou finissent dans la journée (la page peut
 * rester en cache jusqu'au lendemain ; le JS cache la bande hors des dates).
 *
 * @param int      $store_id   Restaurant (tfmsl_store).
 * @param int|null $maintenant Horodatage (tests).
 * @return array<int, array<string, mixed>>
 */
function bigm_concours_du_restaurant( int $store_id, ?int $maintenant = null ): array {
	return array_values( array_filter( bigm_concours_a_montrer( 1, 1, $maintenant ), static fn( $c ) => in_array( $store_id, $c['restaurants'], true ) ) );
}

/**
 * Adresse de la page Jeu concours.
 */
function bigm_concours_page_url(): string {
	$page = bigm_post_par_adresse( 'jeu-concours', 'page' );
	return (string) apply_filters( 'bigm_concours_page_url', $page ? get_permalink( $page ) : home_url( '/jeu-concours/' ) );
}

/**
 * Date lisible (« 22 novembre 2026 »).
 *
 * @param int $ts Horodatage.
 */
function bigm_concours_date( int $ts ): string {
	return $ts ? wp_date( 'j F Y', $ts ) : '';
}

/**
 * Attributs communs (couleurs, dates) d'un bloc de jeu.
 *
 * @param array<string, mixed> $c Jeu.
 */
function bigm_concours_attributs( array $c ): string {
	$style = sprintf( '--jc1:%s;--jc2:%s', $c['couleur1'] ? $c['couleur1'] : '#2a1650', $c['couleur2'] ? $c['couleur2'] : '#c8400a' );
	return ' style="' . esc_attr( $style ) . '"'
		. ( $c['debut_ts'] ? ' data-debut="' . esc_attr( gmdate( 'c', $c['debut_ts'] ) ) . '"' : '' )
		. ( $c['fin_ts'] ? ' data-fin="' . esc_attr( gmdate( 'c', $c['fin_ts'] ) ) . '"' : '' );
}

/**
 * Balise vidéo (lecture par le JS quand elle est visible), ou l'image story.
 *
 * @param array<string, mixed> $c      Jeu.
 * @param string               $classe Classe.
 * @param string               $taille Taille de l'image d'aperçu.
 */
function bigm_concours_media( array $c, string $classe, string $taille = 'large' ): string {
	if ( $c['video'] ) {
		$v = $c['video'];
		return '<video class="' . esc_attr( $classe ) . '" data-jc-video muted loop playsinline preload="none"'
			. ( $v['mp4'] ? ' data-src="' . esc_url( $v['mp4'] ) . '"' : '' )
			. ( $v['webm'] ? ' data-src-webm="' . esc_url( $v['webm'] ) . '"' : '' )
			. ( $v['poster'] ? ' poster="' . esc_url( $v['poster'] ) . '"' : '' )
			. ' aria-label="' . esc_attr( 'Vidéo du ' . $c['titre'] ) . '"></video>';
	}
	if ( $c['story'] ) {
		return (string) wp_get_attachment_image(
			(int) $c['story'],
			$taille,
			false,
			array(
				'class' => $classe,
				'alt'   => 'Affiche du ' . $c['titre'],
			)
		);
	}
	return '';
}

/**
 * Boutons de la vidéo : pause (la vidéo boucle plus de 5 secondes), et son sur la page du jeu.
 *
 * @param bool $son Avec le bouton du son.
 */
function bigm_concours_boutons_video( bool $son ): string {
	return '<div class="jc-tel__boutons">'
		. '<button type="button" class="jc-tel__bouton" data-jc-pause aria-pressed="false" aria-label="Mettre la vidéo en pause"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="jc-i-pause" d="M8 5v14M16 5v14"/><path class="jc-i-lecture" d="M8 5l11 7-11 7z"/></svg></button>'
		. ( $son ? '<button type="button" class="jc-tel__bouton" data-jc-son aria-pressed="false" aria-label="Activer le son"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h4l5-4v14l-5-4H4z"/><path class="jc-i-son" d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12"/><path class="jc-i-muet" d="M17 9.5l5 5M22 9.5l-5 5"/></svg></button>' : '' )
		. '</div>';
}

/**
 * Icônes des étapes (traits, 24 × 24).
 *
 * @param int $i Rang de l'étape.
 */
function bigm_concours_icone( int $i ): string {
	$traces = array(
		'<path d="M5 7h14l-1.2 12.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
		'<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M8 12h8"/>',
		'<rect x="3" y="7" width="18" height="11" rx="5"/><path d="M8 11v3M6.5 12.5h3"/><circle cx="15.5" cy="11.5" r=".6" fill="currentColor"/><circle cx="17.5" cy="13.5" r=".6" fill="currentColor"/>',
		'<path d="M8 4h8v5a4 4 0 0 1-8 0z"/><path d="M8 6H5a3 3 0 0 0 3 4M16 6h3a3 3 0 0 1-3 4M12 13v4M9 20h6"/>',
	);
	return '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $traces[ $i % count( $traces ) ] . '</svg>';
}

/**
 * Compte à rebours (rempli par le JS ; sans JS, la date de fin en clair).
 *
 * @param array<string, mixed> $c       Jeu.
 * @param bool                 $compact Bande des fiches (jours et heures seulement).
 */
function bigm_concours_compte( array $c, bool $compact = false ): string {
	if ( ! $c['fin_ts'] ) {
		return '';
	}
	$unites = $compact ? array(
		'j' => 'jours',
		'h' => 'heures',
	) : array(
		'j' => 'jours',
		'h' => 'heures',
		'm' => 'minutes',
		's' => 'secondes',
	);
	$cases  = '';
	foreach ( $unites as $cle => $nom ) {
		$cases .= '<span class="jc-compte__case"><b data-jc-' . $cle . '>–</b><small>' . esc_html( $nom ) . '</small></span>';
	}
	return '<div class="jc-compte' . ( $compact ? ' jc-compte--compact' : '' ) . '" data-jc-compte>'
		. '<p class="jc-compte__label" data-jc-label data-avant="Commence dans" data-pendant="Fin du jeu dans" data-apres="' . esc_attr( 'Jeu terminé le ' . bigm_concours_date( $c['fin_ts'] ) ) . '">'
		. esc_html( 'Jusqu’au ' . bigm_concours_date( $c['fin_ts'] ) )
		. '</p><div class="jc-compte__cases" aria-hidden="true">' . $cases . '</div><p class="sr-only" data-jc-sr></p></div>';
}

/**
 * Section complète d'un jeu (page Jeu concours).
 *
 * @param array<string, mixed> $c Jeu (avec « etat »).
 */
function bigm_rendu_concours( array $c ): string {
	$id      = 'jc-' . (int) $c['id'];
	$etat    = $c['etat'] ?? bigm_concours_etat( $c );
	$jouer   = '' !== $c['lien'] ? '<a class="btn jc-btn" href="' . esc_url( $c['lien'] ) . '" target="_blank" rel="noopener" data-jc-jouer>' . esc_html( $c['bouton'] ? $c['bouton'] : 'Je joue' ) . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg><span class="sr-only"> (nouvel onglet)</span></a>' : '';
	$restos  = $c['restaurants'];
	$surtitre = 'Jeu concours' . ( '' !== $c['partenaire'] ? ' avec ' . $c['partenaire'] : '' );

	$html = '<section class="jc jc--' . esc_attr( $etat ) . '" id="' . esc_attr( $id ) . '" data-jc' . bigm_concours_attributs( $c ) . ' aria-labelledby="' . esc_attr( $id ) . '-titre">';

	// Bandeau : texte, compte à rebours, boutons ; vidéo dans un téléphone.
	$html .= '<div class="container jc-hero-cadre"><div class="jc-hero"><div class="jc-hero__soleil" aria-hidden="true"></div><div class="jc-hero__grille">'
		. '<div class="jc-hero__texte">'
		. '<p class="jc-pastille"><span class="jc-pastille__point" aria-hidden="true"></span><span><span data-jc-etat data-avant="Bientôt" data-pendant="En cours" data-apres="Terminé">' . esc_html( array( 'a_venir' => 'Bientôt', 'en_cours' => 'En cours', 'termine' => 'Terminé' )[ $etat ] ) . '</span> · ' . esc_html( $surtitre ) . '</span></p>'
		. '<h1 class="jc-hero__titre" id="' . esc_attr( $id ) . '-titre">' . esc_html( $c['titre'] ) . '</h1>'
		. ( '' !== $c['accroche'] ? '<p class="jc-hero__accroche">' . esc_html( $c['accroche'] ) . '</p>' : '' )
		. bigm_concours_compte( $c )
		. '<div class="jc-hero__actions">' . $jouer
		. ( $restos ? '<a class="btn btn--outline jc-btn-contour" href="#' . esc_attr( $id ) . '-restos">' . esc_html( 1 === count( $restos ) ? 'Le restaurant participant' : count( $restos ) . ' restaurants participants' ) . '</a>' : '' )
		. '</div>'
		. ( '' !== $c['condition'] ? '<p class="jc-hero__condition">' . esc_html( $c['condition'] ) . '</p>' : '' )
		. '<p class="jc-hero__fini" data-jc-fini' . ( 'termine' === $etat ? '' : ' hidden' ) . '>' . esc_html(
			'Le jeu est terminé. ' . ( $c['tirage_ts'] ? 'Tirage au sort le ' . bigm_concours_date( $c['tirage_ts'] ) . ' : les gagnants sont contactés par e-mail.' : 'Les gagnants sont contactés par e-mail.' )
		) . '</p>'
		. '</div>';
	$media = bigm_concours_media( $c, 'jc-tel__media' );
	if ( '' !== $media ) {
		$html .= '<div class="jc-hero__tel"><div class="jc-tel">' . $media
			. ( $c['video'] ? bigm_concours_boutons_video( true ) : '' )
			. '</div></div>';
	}
	$html .= '</div></div></div>';

	// Comment jouer.
	if ( $c['etapes_liste'] ) {
		$html .= '<div class="container section jc-section"><div class="section-head"><div><h2>Comment jouer</h2></div></div><ol class="jc-etapes">';
		foreach ( $c['etapes_liste'] as $i => $e ) {
			$html .= '<li class="jc-etape" data-reveal style="--i:' . (int) $i . '"><span class="jc-etape__icone">' . bigm_concours_icone( $i ) . '</span><span class="jc-etape__num" aria-hidden="true">' . (int) ( $i + 1 ) . '</span>'
				. ( '' !== $e['titre'] ? '<h3 class="jc-etape__titre">' . esc_html( $e['titre'] ) . '</h3>' : '' )
				. '<p>' . esc_html( $e['texte'] ) . '</p></li>';
		}
		$html .= '</ol></div>';
	}

	// Lots.
	if ( $c['lots_liste'] ) {
		$html .= '<div class="jc-lots-fond"><div class="container section jc-section"><div class="section-head"><div><h2>À gagner</h2></div></div><ul class="jc-lots">';
		foreach ( $c['lots_liste'] as $i => $l ) {
			$qte   = $l['quantite'];
			$html .= '<li class="jc-lot' . ( $l['principal'] ? ' jc-lot--principal' : '' ) . '" data-reveal style="--i:' . (int) $i . '">'
				. ( $l['principal'] ? '<span class="jc-lot__badge">Lot principal</span>' : '' )
				. ( '' !== $qte ? '<span class="jc-lot__qte"' . ( ctype_digit( $qte ) ? ' data-jc-compteur="' . (int) $qte . '"' : '' ) . '>' . esc_html( $qte ) . '</span>' : '' )
				. '<span class="jc-lot__nom">' . esc_html( $l['nom'] ) . '</span>'
				. ( '' !== $l['precision'] ? '<span class="jc-lot__precision">' . esc_html( $l['precision'] ) . '</span>' : '' )
				. '</li>';
		}
		$html .= '</ul></div></div>';
	}

	// Restaurants participants.
	if ( $restos ) {
		$html .= '<div class="container section jc-section" id="' . esc_attr( $id ) . '-restos"><div class="section-head"><div><h2>Restaurants participants</h2><p>'
			. esc_html( 1 === count( $restos ) ? 'Un restaurant Big M participe au jeu.' : sprintf( '%d restaurants Big M participent au jeu.', count( $restos ) ) ) . '</p></div>'
			. '<div class="jc-recherche" role="search"><label class="sr-only" for="' . esc_attr( $id ) . '-q">Rechercher une ville ou un restaurant</label>'
			. '<input type="search" id="' . esc_attr( $id ) . '-q" placeholder="Ville, restaurant…" autocomplete="off" data-jc-q aria-controls="' . esc_attr( $id ) . '-liste"></div></div>'
			. '<ul class="jc-restos" id="' . esc_attr( $id ) . '-liste">';
		foreach ( $restos as $r ) {
			$nom   = html_entity_decode( get_the_title( $r ), ENT_QUOTES );
			$ville = (string) get_post_meta( $r, 'tfmsl_city', true );
			$html .= '<li data-jc-resto data-q="' . esc_attr( $nom . ' ' . $ville . ' ' . get_post_meta( $r, 'tfmsl_zip', true ) ) . '"><a href="' . esc_url( get_permalink( $r ) ) . '"><span>' . esc_html( $nom ) . '</span>'
				. ( '' !== $ville && false === stripos( $nom, $ville ) ? '<small>' . esc_html( $ville ) . '</small>' : '' ) . '</a></li>';
		}
		$html .= '</ul><p class="jc-restos__vide" role="status" data-jc-vide hidden>Aucun restaurant participant ne correspond. <a href="' . esc_url( function_exists( 'bigm_child_lien_restaurants' ) ? bigm_child_lien_restaurants() : home_url( '/restaurants/' ) ) . '">Voir tous les restaurants</a></p></div>';
	}

	// Questions.
	if ( $c['faq_liste'] ) {
		$html .= '<div class="container section jc-section jc-section--etroite"><div class="section-head"><div><h2>Questions fréquentes</h2></div></div><div class="jc-faq">';
		foreach ( $c['faq_liste'] as $q ) {
			$html .= '<details class="jc-faq__item"><summary>' . esc_html( $q['question'] ) . '</summary><div class="jc-faq__reponse">' . wpautop( esc_html( $q['reponse'] ) ) . '</div></details>';
		}
		$html .= '</div></div>';
	}

	// Règlement et mentions.
	if ( '' !== $c['mentions'] || '' !== $c['reglement_url'] ) {
		$html .= '<div class="container jc-legal">'
			. ( '' !== $c['reglement_url'] ? '<p><a class="btn btn--outline" href="' . esc_url( $c['reglement_url'] ) . '" download><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>Télécharger le règlement (PDF)</a></p>' : '' )
			. ( '' !== $c['mentions'] ? '<div class="jc-legal__texte">' . wpautop( esc_html( $c['mentions'] ) ) . '</div>' : '' )
			. '</div>';
	}

	return $html . '</section>';
}

/**
 * Bande « Jeu concours » : fiche d'un restaurant participant, ou bannière (accueil).
 *
 * @param array<string, mixed> $c        Jeu.
 * @param int                  $store_id Restaurant, ou 0 pour la bannière.
 */
function bigm_rendu_concours_bande( array $c, int $store_id = 0 ): string {
	$titre    = $c['titre'];
	$media    = bigm_concours_media( $c, 'jc-bande__media', 'medium_large' );
	$surtitre = $store_id ? 'Jeu concours dans ce restaurant' : ( 'Jeu concours' . ( '' !== $c['partenaire'] ? ' avec ' . $c['partenaire'] : '' ) );
	return '<section class="jc-bande' . ( $store_id ? '' : ' jc-bande--banniere' ) . '" data-jc data-jc-bande' . bigm_concours_attributs( $c ) . ( 'en_cours' !== ( $c['etat'] ?? '' ) ? ' hidden' : '' ) . ' aria-labelledby="jc-bande-' . (int) $c['id'] . '-' . $store_id . '">'
		. '<div class="jc-bande__carte">'
		. ( '' !== $media ? '<div class="jc-bande__visuel">' . $media . ( $c['video'] ? bigm_concours_boutons_video( false ) : '' ) . '</div>' : '' )
		. '<div class="jc-bande__corps">'
		. '<p class="jc-pastille"><span class="jc-pastille__point" aria-hidden="true"></span><span>' . esc_html( $surtitre ) . '</span></p>'
		. '<h2 class="jc-bande__titre" id="jc-bande-' . (int) $c['id'] . '-' . $store_id . '">' . esc_html( $titre ) . '</h2>'
		. ( '' !== $c['accroche'] ? '<p class="jc-bande__texte">' . esc_html( $c['accroche'] ) . '</p>' : '' )
		. ( '' !== $c['condition'] ? '<p class="jc-bande__condition">' . esc_html( $c['condition'] ) . '</p>' : '' )
		. bigm_concours_compte( $c, true )
		. '<div class="jc-bande__actions">'
		. ( '' !== $c['lien'] ? '<a class="btn jc-btn" href="' . esc_url( $c['lien'] ) . '" target="_blank" rel="noopener">' . esc_html( $c['bouton'] ? $c['bouton'] : 'Je joue' ) . '<span class="sr-only"> (nouvel onglet)</span></a>' : '' )
		. '<a class="btn btn--outline jc-btn-contour" href="' . esc_url( bigm_concours_page_url() . '#jc-' . (int) $c['id'] ) . '">Lots et règlement</a>'
		. '</div></div></div></section>';
}

/**
 * Jeu en cours (ou qui commence ou finit dans la journée : pages en cache, le JS tranche), ou null.
 *
 * @param int $id Jeu choisi (0 : automatique).
 * @return array<string, mixed>|null
 */
function bigm_concours_du_moment( int $id = 0 ): ?array {
	foreach ( bigm_concours_a_montrer( 1, 1 ) as $c ) {
		if ( ! $id || (int) $c['id'] === $id ) {
			return $c;
		}
	}
	return null;
}

/**
 * Bannière du jeu en cours (accueil, ou ailleurs avec le widget ou le code court), ou rien.
 *
 * @param int $id Jeu choisi (0 : automatique).
 */
function bigm_rendu_concours_banniere( int $id = 0 ): string {
	$c = bigm_concours_du_moment( $id );
	return $c ? bigm_rendu_concours_bande( $c, 0 ) : '';
}

/**
 * La page affiche-t-elle le jeu (widget « Big M — Jeu concours » ou [bigm_jeu_concours]) ?
 *
 * @param int $post_id Page.
 */
function bigm_concours_page_du_jeu( int $post_id ): bool {
	$textes = (string) get_post_field( 'post_content', $post_id ) . (string) get_post_meta( $post_id, '_elementor_data', true );
	return str_contains( $textes, '"widgetType":"bigm-jeu-concours"' ) || str_contains( $textes, '[bigm_jeu_concours]' ) || str_contains( $textes, '[bigm_jeu_concours ' );
}

/**
 * Le bouton flottant doit-il être imprimé sur cette page ? Pas sur la page du jeu, ni sur la fiche
 * d'un restaurant qui a déjà la bande, ni dans l'aperçu d'Elementor. Filtre
 * bigm_concours_bouton_flottant.
 *
 * @return array<string, mixed>|null Jeu, ou null.
 */
function bigm_concours_bouton_jeu(): ?array {
	if ( is_admin() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}
	$c = bigm_concours_du_moment();
	if ( $c && is_singular() ) {
		$id = (int) get_queried_object_id();
		if ( bigm_concours_page_du_jeu( $id ) || ( is_singular( 'tfmsl_store' ) && in_array( $id, $c['restaurants'], true ) ) ) {
			$c = null;
		}
	}
	return apply_filters( 'bigm_concours_bouton_flottant', $c );
}

/**
 * Bouton flottant « Jeu concours en cours », qu'on peut fermer (choix gardé dans le navigateur
 * jusqu'à la fin du jeu). Caché dans le HTML : le JS le montre pendant le jeu, sans recouvrir le
 * bandeau des cookies.
 *
 * @param array<string, mixed> $c Jeu.
 */
function bigm_rendu_concours_bouton( array $c ): string {
	return '<aside class="jc-flottant" data-jc-flottant data-id="' . (int) $c['id'] . '"' . bigm_concours_attributs( $c ) . ' aria-label="' . esc_attr( 'Jeu concours en cours' ) . '" hidden>'
		. '<a class="jc-flottant__lien" href="' . esc_url( bigm_concours_page_url() ) . '">'
		. '<span class="jc-flottant__icone" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11h16v9H4zM3 7h18v4H3zM12 7v13M12 7c-1.5-3-5-3.2-5-1s3 1 5 1zm0 0c1.5-3 5-3.2 5-1s-3 1-5 1z"/></svg></span>'
		. '<span class="jc-flottant__texte"><strong>Jeu concours en cours</strong><small data-jc-reste>' . esc_html( $c['titre'] ) . '</small></span>'
		. '</a>'
		. '<button type="button" class="jc-flottant__fermer" data-jc-fermer aria-label="Fermer : ne plus afficher ce bouton"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>'
		. '</aside>';
}

add_action(
	'wp_footer',
	static function () {
		$c = bigm_concours_bouton_jeu();
		if ( $c ) {
			echo bigm_rendu_concours_bouton( $c ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_rendu_concours_bouton().
		}
	},
	5
);

/*
 * Bandeau des cookies (bigm-cookies) : le bouton fermé est gardé dans le navigateur, avec les
 * autres stockages nécessaires au site.
 */
add_filter(
	'bigm_cookies_categories',
	static function ( array $cats ): array {
		if ( isset( $cats['necessaires']['cookies'] ) ) {
			$cats['necessaires']['cookies'][] = array( 'bigm_jeu_ferme_*', 'Big M', 'Bouton « Jeu concours en cours » fermé (stockage du navigateur), jusqu’à ce que vous vidiez les données du site' );
		}
		return $cats;
	}
);

/**
 * Bloc de la page Jeu concours : le jeu à montrer, ou un texte d'attente.
 *
 * @param int    $id    Jeu choisi (0 : automatique).
 * @param string $titre Titre sans jeu.
 * @param string $texte Texte sans jeu.
 */
function bigm_rendu_page_concours( int $id = 0, string $titre = '', string $texte = '' ): string {
	$jeux = $id ? array_filter( array( bigm_concours( $id ) ) ) : bigm_concours_a_montrer();
	if ( $jeux ) {
		$c         = reset( $jeux );
		$c['etat'] = $c['etat'] ?? bigm_concours_etat( $c );
		return bigm_rendu_concours( $c );
	}
	$titre = '' !== $titre ? $titre : 'Jeu concours';
	$texte = '' !== $texte ? $texte : 'Pas de jeu concours en ce moment. Les prochains seront annoncés ici et sur nos réseaux sociaux.';
	return '<section class="jc-vide container section"><h1>' . esc_html( $titre ) . '</h1><div class="prose">' . wpautop( esc_html( $texte ) ) . '</div></section>';
}

/*
 * Styles et script, chargés seulement là où il y a un jeu.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		$v = static function ( string $rel ): string {
			$f = BIGM_CORE_DIR . '/assets/' . $rel;
			return is_file( $f ) ? (string) filemtime( $f ) : BIGM_CORE_VERSION;
		};
		wp_register_style( 'bigm-concours', BIGM_CORE_URL . 'assets/css/concours.css', array(), $v( 'css/concours.css' ) );
		wp_register_script(
			'bigm-concours',
			BIGM_CORE_URL . 'assets/js/concours.js',
			array(),
			$v( 'js/concours.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		if ( is_singular( 'tfmsl_store' ) && bigm_concours_du_restaurant( (int) get_queried_object_id() ) ) {
			wp_enqueue_style( 'bigm-concours' );
			wp_enqueue_script( 'bigm-concours' );
		}
		if ( bigm_concours_bouton_jeu() ) {
			wp_enqueue_style( 'bigm-concours-bouton', BIGM_CORE_URL . 'assets/css/concours-bouton.css', array(), $v( 'css/concours-bouton.css' ) );
			wp_enqueue_script(
				'bigm-concours-bouton',
				BIGM_CORE_URL . 'assets/js/concours-bouton.js',
				array(),
				$v( 'js/concours-bouton.js' ),
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	},
	25
);

/*
 * Fiche d'un restaurant (gabarit de StoreLocatore) : la bande sous le bandeau, après les promos
 * (priorité 10) et avant le food court (20).
 */
add_action(
	'tfmsl_after_store_hero',
	static function ( $post_id ) {
		foreach ( bigm_concours_du_restaurant( (int) $post_id ) as $c ) {
			echo bigm_rendu_concours_bande( $c, (int) $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- échappé dans bigm_rendu_concours_bande().
		}
	},
	15
);

add_shortcode(
	'bigm_jeu_concours',
	static function ( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'titre' => '',
				'texte' => '',
			),
			$atts
		);
		wp_enqueue_style( 'bigm-concours' );
		wp_enqueue_script( 'bigm-concours' );
		return bigm_rendu_page_concours( (int) $atts['id'], (string) $atts['titre'], (string) $atts['texte'] );
	}
);

add_shortcode(
	'bigm_jeu_concours_banniere',
	static function ( $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts );
		$html = bigm_rendu_concours_banniere( (int) $atts['id'] );
		if ( '' !== $html ) {
			wp_enqueue_style( 'bigm-concours' );
			wp_enqueue_script( 'bigm-concours' );
		}
		return $html;
	}
);

add_shortcode(
	'bigm_jeu_concours_bande',
	static function (): string {
		$html = '';
		foreach ( bigm_concours_du_restaurant( (int) get_the_ID() ) as $c ) {
			$html .= bigm_rendu_concours_bande( $c, (int) get_the_ID() );
		}
		if ( '' !== $html ) {
			wp_enqueue_style( 'bigm-concours' );
			wp_enqueue_script( 'bigm-concours' );
		}
		return $html;
	}
);

/*
 * Référencement de la page qui affiche le jeu (widget ou code court) : tant qu'un jeu est en
 * cours ou à venir, sa description (titre, date de fin, accroche) et son affiche, à la place de
 * la description saisie pour la page sans jeu.
 */
add_filter(
	'bigm_seo_donnees_page',
	static function ( array $page, int $objet_id ): array {
		if ( ! $objet_id || 'page' !== get_post_type( $objet_id ) ) {
			return $page;
		}
		if ( ! bigm_concours_page_du_jeu( $objet_id ) ) {
			return $page;
		}
		$jeux = array_values( array_filter( bigm_concours_a_montrer(), static fn( $c ) => 'termine' !== $c['etat'] ) );
		if ( ! $jeux ) {
			return $page;
		}
		$c                   = $jeux[0];
		$page['description'] = bigm_seo_extrait( trim( $c['titre'] . ( $c['fin_ts'] ? ' jusqu’au ' . bigm_concours_date( $c['fin_ts'] ) : '' ) . ' : ' . $c['accroche'], ' :' ) );
		if ( ! $page['image'] && $c['story'] ) {
			$page['image'] = (int) $c['story'];
		}
		return $page;
	},
	10,
	2
);

/* ---------------------------------------------------------------------------------------------
 * Cache : vidé à l'enregistrement d'un jeu et à ses dates de début et de fin.
 * ------------------------------------------------------------------------------------------ */

/**
 * Programme les vidages du cache aux dates d'un jeu.
 *
 * @param int $id Jeu.
 */
function bigm_concours_programmer( int $id ): void {
	foreach ( (array) _get_cron_array() as $quand => $taches ) {
		foreach ( $taches['bigm_concours_changement'] ?? array() as $tache ) {
			if ( (int) ( $tache['args'][0] ?? 0 ) === $id ) {
				wp_unschedule_event( $quand, 'bigm_concours_changement', $tache['args'] );
			}
		}
	}
	$c = 'publish' === get_post_status( $id ) ? bigm_concours( $id ) : null;
	if ( ! $c ) {
		return;
	}
	foreach ( array( $c['debut_ts'], $c['fin_ts'] ) as $ts ) {
		if ( $ts && $ts > time() ) {
			wp_schedule_single_event( $ts + 60, 'bigm_concours_changement', array( $id, $ts ) );
		}
	}
}
add_action( 'bigm_concours_changement', 'bigm_vider_cache' );

add_action(
	'save_post_bigm_concours',
	static function ( int $post_id ) {
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		// Après l'enregistrement des champs (priorité 10) et de l'API REST (meta écrites après).
		add_action(
			'shutdown',
			static function () use ( $post_id ) {
				bigm_concours_programmer( $post_id );
				bigm_vider_cache();
			}
		);
	},
	20
);

/* ---------------------------------------------------------------------------------------------
 * Admin : champs du jeu.
 * ------------------------------------------------------------------------------------------ */

add_action(
	'add_meta_boxes_bigm_concours',
	static function () {
		add_meta_box( 'bigm-concours-jeu', 'Le jeu', 'bigm_concours_boite_jeu', 'bigm_concours', 'normal', 'high' );
		add_meta_box( 'bigm-concours-contenu', 'Étapes, lots, questions, mentions', 'bigm_concours_boite_contenu', 'bigm_concours', 'normal' );
		add_meta_box( 'bigm-concours-visuels', 'Visuels et règlement', 'bigm_concours_boite_visuels', 'bigm_concours', 'side' );
		add_meta_box( 'bigm-concours-restos', 'Restaurants participants', 'bigm_concours_boite_restos', 'bigm_concours', 'normal' );
	}
);

/**
 * Valeur enregistrée d'un champ, ou sa valeur par défaut.
 *
 * @param WP_Post $post Jeu.
 * @param string  $cle  Champ.
 * @return mixed
 */
function bigm_concours_valeur( WP_Post $post, string $cle ) {
	$champs = bigm_concours_champs();
	$valeur = get_post_meta( $post->ID, '_bigm_jc_' . $cle, true );
	return ( '' === $valeur || null === $valeur ) ? $champs[ $cle ][1] : $valeur;
}

/**
 * Boîte « Le jeu ».
 *
 * @param WP_Post $post Jeu.
 */
function bigm_concours_boite_jeu( WP_Post $post ): void {
	$v = static fn( string $cle ): string => (string) bigm_concours_valeur( $post, $cle );
	wp_nonce_field( 'bigm_concours_' . $post->ID, 'bigm_concours_nonce' );
	$champs = array(
		array( 'partenaire', 'Partenaire', 'text', 'Ex. « Oasis Ice Tea » : affiché « Big M × Oasis Ice Tea ».' ),
		array( 'accroche', 'Accroche', 'textarea', 'Une ou deux phrases sous le titre : ce qu’on peut gagner.' ),
		array( 'condition', 'Condition de participation', 'text', 'Ex. « Avec un menu Big M et un Oasis Ice Tea… ». Affichée sous les boutons.' ),
		array( 'debut', 'Début', 'datetime-local', 'Heure de Paris.' ),
		array( 'fin', 'Fin', 'datetime-local', 'Heure de Paris. Le compte à rebours s’arrête à cette minute (59 s comprises).' ),
		array( 'tirage', 'Tirage au sort', 'date', 'Facultatif : annoncé quand le jeu est terminé.' ),
		array( 'lien', 'Lien pour jouer', 'url', 'Site du jeu (ouvert dans un nouvel onglet). Vide : pas de bouton.' ),
		array( 'bouton', 'Texte du bouton', 'text', '' ),
	);
	echo '<table class="form-table" role="presentation">';
	foreach ( $champs as list( $cle, $label, $type, $aide ) ) {
		$valeur = $v( $cle );
		if ( 'datetime-local' === $type ) {
			$valeur = str_replace( ' ', 'T', $valeur );
		}
		echo '<tr><th scope="row"><label for="bigm-jc-' . esc_attr( $cle ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'textarea' === $type ) {
			echo '<textarea id="bigm-jc-' . esc_attr( $cle ) . '" name="bigm_jc[' . esc_attr( $cle ) . ']" rows="2" class="large-text">' . esc_textarea( $valeur ) . '</textarea>';
		} else {
			echo '<input type="' . esc_attr( $type ) . '" id="bigm-jc-' . esc_attr( $cle ) . '" name="bigm_jc[' . esc_attr( $cle ) . ']" value="' . esc_attr( $valeur ) . '" class="' . ( in_array( $type, array( 'text', 'url' ), true ) ? 'large-text' : '' ) . '">';
		}
		if ( '' !== $aide ) {
			echo '<p class="description">' . esc_html( $aide ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '<tr><th scope="row">Couleurs du bandeau</th><td>';
	foreach ( array( 'couleur1' => 'Haut', 'couleur2' => 'Bas' ) as $cle => $label ) {
		echo '<label style="margin-right:16px">' . esc_html( $label ) . ' <input type="color" name="bigm_jc[' . esc_attr( $cle ) . ']" value="' . esc_attr( $v( $cle ) ) . '"></label>';
	}
	echo '<p class="description">Dégradé derrière le titre, en texte blanc : garder des couleurs foncées.</p></td></tr></table>';
}

/**
 * Boîte « Étapes, lots, questions, mentions ».
 *
 * @param WP_Post $post Jeu.
 */
function bigm_concours_boite_contenu( WP_Post $post ): void {
	$champs = array(
		'etapes'   => array( 'Comment jouer', 5, 'Une étape par ligne : « Titre : texte ». Ex. « Commandez : un menu Big M avec… ».' ),
		'lots'     => array( 'À gagner', 5, 'Un lot par ligne : « Quantité | Lot | Précision ». Une étoile devant la ligne (« *1 | Casque Apple | Tirage au sort ») le met en avant.' ),
		'faq'      => array( 'Questions fréquentes', 10, 'Une question par bloc : la question sur la première ligne, la réponse dessous, une ligne vide entre deux questions.' ),
		'mentions' => array( 'Mentions légales', 5, 'Organisateur, dates, conditions, marques citées… Affichées en bas, avec le lien vers le règlement.' ),
	);
	echo '<table class="form-table" role="presentation">';
	foreach ( $champs as $cle => list( $label, $lignes, $aide ) ) {
		echo '<tr><th scope="row"><label for="bigm-jc-' . esc_attr( $cle ) . '">' . esc_html( $label ) . '</label></th><td>'
			. '<textarea id="bigm-jc-' . esc_attr( $cle ) . '" name="bigm_jc[' . esc_attr( $cle ) . ']" rows="' . (int) $lignes . '" class="large-text">' . esc_textarea( (string) bigm_concours_valeur( $post, $cle ) ) . '</textarea>'
			. '<p class="description">' . esc_html( $aide ) . '</p></td></tr>';
	}
	echo '</table>';
}

/**
 * Boîte « Visuels et règlement ».
 *
 * @param WP_Post $post Jeu.
 */
function bigm_concours_boite_visuels( WP_Post $post ): void {
	$n = static fn( string $cle ): int => (int) bigm_concours_valeur( $post, $cle );
	bigm_champ_media( 'bigm_jc[story]', $n( 'story' ), 'image', 'Affiche story (9:16)', 'Montrée à la place de la vidéo s’il n’y en a pas, et comme image d’aperçu.' );
	bigm_champ_media( 'bigm_jc[video_mp4]', $n( 'video_mp4' ), 'video/mp4', 'Vidéo MP4 (9:16, 3 Mo max.)' );
	bigm_champ_media( 'bigm_jc[video_webm]', $n( 'video_webm' ), 'video/webm', 'Vidéo WebM (facultatif)' );
	bigm_champ_media( 'bigm_jc[video_poster]', $n( 'video_poster' ), 'image', 'Image d’aperçu de la vidéo', 'Vide : l’affiche story.' );
	bigm_champ_media( 'bigm_jc[reglement]', $n( 'reglement' ), 'application/pdf', 'Règlement (PDF)' );
}

/**
 * Boîte « Restaurants participants ».
 *
 * @param WP_Post $post Jeu.
 */
function bigm_concours_boite_restos( WP_Post $post ): void {
	if ( ! post_type_exists( 'tfmsl_store' ) ) {
		echo '<p>StoreLocatore n’est pas actif.</p>';
		return;
	}
	$tous   = '1' === (string) bigm_concours_valeur( $post, 'tous' );
	$coches = array_map( 'intval', (array) bigm_concours_valeur( $post, 'restaurants' ) );
	$restos = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	echo '<p><label><input type="checkbox" name="bigm_jc[tous]" value="1" data-jc-tous' . checked( $tous, true, false ) . '> <strong>Tous les restaurants</strong> (y compris ceux ajoutés plus tard)</label></p>';
	echo '<div class="bigm-jc-restos"' . ( $tous ? ' hidden' : '' ) . ' data-jc-liste>';
	echo '<p><label class="screen-reader-text" for="bigm-jc-filtre">Filtrer les restaurants</label><input type="search" id="bigm-jc-filtre" placeholder="Filtrer…" data-jc-filtre> <button type="button" class="button" data-jc-cocher="1">Tout cocher</button> <button type="button" class="button" data-jc-cocher="0">Tout décocher</button> <span data-jc-nombre></span></p>';
	echo '<input type="hidden" name="bigm_jc[restaurants][]" value="0">';
	echo '<ul style="columns:3 220px;margin:0">';
	foreach ( $restos as $r ) {
		echo '<li style="break-inside:avoid"><label><input type="checkbox" name="bigm_jc[restaurants][]" value="' . (int) $r->ID . '"' . checked( in_array( $r->ID, $coches, true ), true, false ) . '> ' . esc_html( get_the_title( $r ) ) . ( 'publish' !== $r->post_status ? ' <em>(non publié)</em>' : '' ) . '</label></li>';
	}
	echo '</ul></div>';
	?>
	<script>
	(() => {
		const boite = document.getElementById('bigm-concours-restos');
		if (!boite) return;
		const liste = boite.querySelector('[data-jc-liste]');
		const nombre = boite.querySelector('[data-jc-nombre]');
		const cases = [...boite.querySelectorAll('input[name="bigm_jc[restaurants][]"][type=checkbox]')];
		const compter = () => { nombre.textContent = cases.filter((c) => c.checked).length + ' coché(s)'; };
		boite.querySelector('[data-jc-tous]').addEventListener('change', (e) => { liste.hidden = e.target.checked; });
		boite.querySelector('[data-jc-filtre]').addEventListener('input', (e) => {
			const q = e.target.value.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
			cases.forEach((c) => { c.closest('li').hidden = q && !c.parentNode.textContent.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').includes(q); });
		});
		boite.querySelectorAll('[data-jc-cocher]').forEach((b) => b.addEventListener('click', () => {
			cases.filter((c) => !c.closest('li').hidden).forEach((c) => { c.checked = b.dataset.jcCocher === '1'; });
			compter();
		}));
		cases.forEach((c) => c.addEventListener('change', compter));
		compter();
	})();
	</script>
	<?php
}

add_action(
	'save_post_bigm_concours',
	static function ( int $post_id ) {
		if ( ! isset( $_POST['bigm_concours_nonce'], $_POST['bigm_jc'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bigm_concours_nonce'] ) ), 'bigm_concours_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$d = (array) wp_unslash( $_POST['bigm_jc'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nettoyé par bigm_concours_nettoyer() (sanitize_callback des meta).
		foreach ( array_keys( bigm_concours_champs() ) as $cle ) {
			$valeur = $d[ $cle ] ?? ( 'tous' === $cle ? '0' : ( 'restaurants' === $cle ? array() : '' ) );
			update_post_meta( $post_id, '_bigm_jc_' . $cle, bigm_concours_nettoyer( $cle, $valeur ) );
		}
	},
	10
);

/*
 * Liste des jeux : dates, état, restaurants.
 */
add_filter(
	'manage_bigm_concours_posts_columns',
	static fn( array $colonnes ): array => array_slice( $colonnes, 0, 2, true ) + array(
		'bigm_jc_dates'  => 'Dates',
		'bigm_jc_etat'   => 'État',
		'bigm_jc_restos' => 'Restaurants',
	) + array_slice( $colonnes, 2, null, true )
);
add_action(
	'manage_bigm_concours_posts_custom_column',
	static function ( string $colonne, int $post_id ) {
		$c = bigm_concours( $post_id );
		if ( ! $c ) {
			return;
		}
		if ( 'bigm_jc_dates' === $colonne ) {
			echo esc_html( trim( bigm_concours_date( $c['debut_ts'] ) . ' → ' . bigm_concours_date( $c['fin_ts'] ), ' →' ) );
		} elseif ( 'bigm_jc_etat' === $colonne ) {
			echo esc_html(
				array(
					'a_venir'  => 'À venir',
					'en_cours' => 'En cours',
					'termine'  => 'Terminé',
				)[ bigm_concours_etat( $c ) ]
			);
		} elseif ( 'bigm_jc_restos' === $colonne ) {
			echo '1' === (string) $c['tous'] ? 'Tous' : esc_html( (string) count( $c['restaurants'] ) );
		}
	},
	10,
	2
);
