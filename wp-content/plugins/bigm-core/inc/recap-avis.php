<?php
/**
 * Récapitulatif des avis par e-mail, aux couleurs de Big M : par restaurant, la note Google et son
 * évolution, les nouveaux avis Google, les retours privés laissés sur la page d'avis du site
 * (collecte d'avis de StoreLocatore) et ses chiffres (visites, notes, clics vers Google).
 *
 * - Réglages Big M → Avis : adresse générale (reçoit tous les restaurants), envoi à chaque
 *   restaurant (adresse « Email du responsable » de sa fiche, onglet Intégrations), fréquence.
 * - Restaurants → Récapitulatif des avis : aperçu, envoi d'essai à soi-même, envoi immédiat.
 * - Envoi chaque jour à 9 h (heure de Paris) selon la fréquence : tous les jours, le lundi
 *   (semaine écoulée) ou le 1er du mois (mois écoulé). Un restaurant ne reçoit son e-mail que
 *   s'il s'est passé quelque chose ; l'adresse générale le reçoit toujours.
 * - Données lues, jamais demandées à Google : note et avis gardés par la planification de
 *   inc/google.php (tfmsl_place_summary_{id}, 5 avis au plus par restaurant, les plus utiles
 *   selon Google), historique tfmsl_rating_history, tables tfmsl_feedback et tfmsl_events.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Période d'un récapitulatif qui se termine à $fin (horodatage), selon la fréquence.
 *
 * @param string   $frequence « jour », « semaine » ou « mois ».
 * @param int|null $fin       Fin (exclue), par défaut aujourd'hui à 0 h, heure de Paris.
 * @return array{0: int, 1: int} Début et fin.
 */
function bigm_recap_periode( string $frequence, ?int $fin = null ): array {
	$fin   = $fin ?? ( new DateTimeImmutable( 'today', wp_timezone() ) )->getTimestamp();
	$fin_d = ( new DateTimeImmutable( '@' . $fin ) )->setTimezone( wp_timezone() );
	$debut = match ( $frequence ) {
		'jour'  => $fin_d->modify( '-1 day' ),
		'mois'  => $fin_d->modify( 'first day of last month' )->setTime( 0, 0 ),
		default => $fin_d->modify( '-7 days' ),
	};
	return array( $debut->getTimestamp(), $fin );
}

/**
 * Horodatage d'une date locale enregistrée par current_time( 'mysql' ).
 *
 * @param string $date « Y-m-d H:i:s », heure du site.
 */
function bigm_recap_ts( string $date ): int {
	try {
		return ( new DateTimeImmutable( $date, wp_timezone() ) )->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

/**
 * Le récapitulatif part-il aujourd'hui ?
 *
 * @param string $frequence  Fréquence.
 * @param int    $maintenant Horodatage.
 */
function bigm_recap_jour_d_envoi( string $frequence, int $maintenant ): bool {
	$d = ( new DateTimeImmutable( '@' . $maintenant ) )->setTimezone( wp_timezone() );
	return match ( $frequence ) {
		'jour'    => true,
		'semaine' => '1' === $d->format( 'N' ),
		'mois'    => '1' === $d->format( 'j' ),
		default   => false,
	};
}

/**
 * Table de StoreLocatore, ou '' si elle n'existe pas.
 *
 * @param string $nom « tfmsl_feedback » ou « tfmsl_events ».
 */
function bigm_recap_table( string $nom ): string {
	global $wpdb;
	$table = $wpdb->prefix . $nom;
	return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ? $table : ''; // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/**
 * Données du récapitulatif, par restaurant.
 *
 * @param int        $debut  Début (inclus).
 * @param int        $fin    Fin (exclue).
 * @param int[]|null $restos Restaurants (tous les publiés par défaut).
 * @return array<int, array<string, mixed>>
 */
function bigm_recap_donnees( int $debut, int $fin, ?array $restos = null ): array {
	global $wpdb;
	$restos = $restos ?? get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	$restos = array_map( 'intval', $restos );
	if ( ! $restos ) {
		return array();
	}
	$de        = wp_date( 'Y-m-d H:i:s', $debut );
	$a         = wp_date( 'Y-m-d H:i:s', $fin );
	$liste_ids = implode( ',', $restos );

	// Retours privés de la page d'avis.
	$retours = array();
	$t       = bigm_recap_table( 'tfmsl_feedback' );
	if ( $t ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- identifiants entiers, table du plugin.
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT post_id, rating, message, name, contact, created_at FROM $t WHERE post_id IN ($liste_ids) AND created_at >= %s AND created_at < %s ORDER BY created_at DESC", $de, $a ), ARRAY_A ) as $l ) {
			$retours[ (int) $l['post_id'] ][] = $l;
		}
	}
	// Chiffres de la page d'avis.
	$stats = array();
	$t     = bigm_recap_table( 'tfmsl_events' );
	if ( $t ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- identifiants entiers, table du plugin.
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT post_id, event_type, COUNT(*) AS n FROM $t WHERE post_id IN ($liste_ids) AND event_type IN ('rb_view','rb_rate_high','rb_rate_low','rb_google_click') AND created_at >= %s AND created_at < %s GROUP BY post_id, event_type", $de, $a ), ARRAY_A ) as $l ) {
			$stats[ (int) $l['post_id'] ][ $l['event_type'] ] = (int) $l['n'];
		}
	}

	$donnees = array();
	foreach ( $restos as $id ) {
		$resume = get_transient( 'tfmsl_place_summary_' . $id );
		$resume = is_array( $resume ) ? $resume : array();
		$note   = isset( $resume['rating'] ) ? (float) $resume['rating'] : null;
		$nombre = isset( $resume['count'] ) ? (int) $resume['count'] : null;
		// Note au début de la période : dernière entrée de l'historique avant le début.
		$avant   = null;
		$histo   = get_post_meta( $id, 'tfmsl_rating_history', true );
		$jour_de = wp_date( 'Y-m-d', $debut );
		foreach ( is_array( $histo ) ? $histo : array() as $jour => $h ) {
			if ( (string) $jour < $jour_de && is_array( $h ) ) {
				$avant = $h;
			}
		}
		$avis = array();
		foreach ( (array) ( $resume['reviews'] ?? array() ) as $r ) {
			$quand = strtotime( (string) ( $r['publish_time'] ?? '' ) );
			if ( $quand && $quand >= $debut && $quand < $fin ) {
				$avis[] = array(
					'auteur' => (string) ( $r['author_name'] ?? '' ),
					'note'   => (int) ( $r['rating'] ?? 0 ),
					'texte'  => (string) ( $r['text'] ?? '' ),
					'date'   => $quand,
				);
			}
		}
		$s         = $stats[ $id ] ?? array();
		$donnees[] = array(
			'id'          => $id,
			'nom'         => html_entity_decode( get_the_title( $id ), ENT_QUOTES ),
			'url'         => (string) get_permalink( $id ),
			'email'       => sanitize_email( (string) get_post_meta( $id, 'tfmsl_manager_email', true ) ),
			'note'        => $note,
			'nombre'      => $nombre,
			'note_avant'  => $avant ? (float) $avant['rating'] : null,
			'nombre_avant' => $avant ? (int) $avant['count'] : null,
			'maj'         => (string) ( $resume['fetched_at'] ?? '' ),
			'lien_google' => (string) ( $resume['maps_uri'] ?? '' ),
			'avis'        => $avis,
			'retours'     => $retours[ $id ] ?? array(),
			'vues'        => (int) ( $s['rb_view'] ?? 0 ),
			'notes'       => (int) ( $s['rb_rate_high'] ?? 0 ) + (int) ( $s['rb_rate_low'] ?? 0 ),
			'notes_basses' => (int) ( $s['rb_rate_low'] ?? 0 ),
			'clics'       => (int) ( $s['rb_google_click'] ?? 0 ),
		);
	}
	return $donnees;
}

/**
 * Le restaurant a-t-il de l'activité dans la période ?
 *
 * @param array<string, mixed> $r Données d'un restaurant.
 */
function bigm_recap_actif( array $r ): bool {
	$evolution = null !== $r['nombre'] && null !== $r['nombre_avant'] && $r['nombre'] !== $r['nombre_avant'];
	return $r['avis'] || $r['retours'] || $r['vues'] > 0 || $evolution;
}

/* ---------------------------------------------------------------------------------------------
 * Mise en page (e-mail : tableaux et styles en ligne, couleurs de la charte).
 * ------------------------------------------------------------------------------------------ */

/**
 * Couleurs et polices de l'e-mail.
 *
 * @return array<string, string>
 */
function bigm_recap_charte(): array {
	return (array) apply_filters(
		'bigm_recap_charte',
		array(
			'fond'    => '#faf8f5',
			'carte'   => '#ffffff',
			'tuile'   => '#f2ede7',
			'encre'   => '#2b1406',
			'doux'    => '#6b4a3a',
			'orange'  => '#fa5c19',
			'bouton'  => '#c8400a',
			'jaune'   => '#ffc21a',
			'vert'    => '#1e7a3a',
			'rouge'   => '#b42318',
			'titre'   => "'Fredoka','Arial Rounded MT Bold','Helvetica Rounded',Arial,sans-serif",
			'texte'   => "'Figtree',Arial,Helvetica,sans-serif",
		)
	);
}

/**
 * Étoiles (pleines en jaune, vides en beige).
 *
 * @param float $note Note sur 5.
 */
function bigm_recap_etoiles( float $note ): string {
	$c     = bigm_recap_charte();
	$plein = (int) round( $note );
	return '<span style="color:' . $c['jaune'] . ';font-size:16px;letter-spacing:1px" aria-label="' . esc_attr( number_format_i18n( $note, 1 ) . ' sur 5' ) . '">' . str_repeat( '★', max( 0, min( 5, $plein ) ) ) . '<span style="color:#e2d6c8">' . str_repeat( '★', 5 - max( 0, min( 5, $plein ) ) ) . '</span></span>';
}

/**
 * Évolution de la note (« +0,1 » en vert, « −0,2 » en rouge, rien sinon).
 *
 * @param array<string, mixed> $r Données d'un restaurant.
 */
function bigm_recap_evolution( array $r ): string {
	if ( null === $r['note'] || null === $r['note_avant'] ) {
		return '';
	}
	$d = round( $r['note'] - $r['note_avant'], 1 );
	if ( abs( $d ) < 0.05 ) {
		return '';
	}
	$c = bigm_recap_charte();
	return ' <span style="color:' . ( $d > 0 ? $c['vert'] : $c['rouge'] ) . ';font-weight:600;font-size:13px">' . esc_html( ( $d > 0 ? '+' : '−' ) . number_format_i18n( abs( $d ), 1 ) ) . '</span>';
}

/**
 * Bouton (lien en forme de bouton, lisible sans images).
 *
 * @param string $texte Texte.
 * @param string $lien  Adresse.
 * @param bool   $plein Fond orange (sinon contour).
 */
function bigm_recap_bouton( string $texte, string $lien, bool $plein = true ): string {
	$c = bigm_recap_charte();
	return '<a href="' . esc_url( $lien ) . '" style="display:inline-block;padding:12px 20px;margin:4px 8px 4px 0;border-radius:12px;font-family:' . esc_attr( $c['titre'] ) . ';font-weight:600;font-size:15px;text-decoration:none;'
		. ( $plein ? 'background:' . $c['bouton'] . ';color:#ffffff' : 'background:#ffffff;color:' . $c['encre'] . ';border:2px solid ' . $c['encre'] ) . '">' . esc_html( $texte ) . '</a>';
}

/**
 * Détail d'un restaurant : chiffres, nouveaux avis Google, retours privés.
 *
 * @param array<string, mixed> $r   Données d'un restaurant.
 * @param bool                 $nom Avec son nom en titre (e-mail général).
 */
function bigm_recap_html_restaurant( array $r, bool $nom = true ): string {
	$c     = bigm_recap_charte();
	$tuile = static function ( string $valeur, string $label ) use ( $c ): string {
		return '<td width="25%" style="padding:4px" valign="top"><div style="background:' . $c['tuile'] . ';border-radius:16px;padding:12px 8px;text-align:center"><div style="font-family:' . $c['titre'] . ';font-weight:600;font-size:24px;line-height:1.1;color:' . $c['encre'] . '">' . $valeur . '</div><div style="margin-top:4px;font-size:12px;line-height:1.3;color:' . $c['doux'] . '">' . esc_html( $label ) . '</div></div></td>';
	};
	$h = '<tr><td style="padding:' . ( $nom ? '24px' : '8px' ) . ' 28px 0">' . ( $nom ? '<h2 style="margin:0 0 4px;font-family:' . $c['titre'] . ';font-weight:600;font-size:22px;color:' . $c['encre'] . '"><a href="' . esc_url( $r['url'] ) . '" style="color:' . $c['encre'] . ';text-decoration:none">' . esc_html( $r['nom'] ) . '</a></h2>' : '' )
		. ( null !== $r['note'] ? '<p style="margin:0;font-size:15px;color:' . $c['doux'] . '">' . bigm_recap_etoiles( (float) $r['note'] ) . ' <strong style="color:' . $c['encre'] . '">' . esc_html( number_format_i18n( (float) $r['note'], 1 ) ) . '</strong>' . bigm_recap_evolution( $r ) . ' · ' . esc_html( sprintf( '%s avis Google', number_format_i18n( (int) $r['nombre'] ) ) ) . '</p>' : '<p style="margin:0;font-size:14px;color:' . $c['doux'] . '">Note Google pas encore relevée.</p>' )
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px"><tr>'
		. $tuile( (string) count( $r['avis'] ), 'nouveaux avis Google' )
		. $tuile( (string) count( $r['retours'] ), 'retours privés' )
		. $tuile( (string) $r['notes'], 'notes sur la page d’avis' )
		. $tuile( (string) $r['clics'], 'clics vers Google' )
		. '</tr></table></td></tr>';

	if ( $r['avis'] ) {
		$h .= '<tr><td style="padding:16px 28px 0"><p style="margin:0 0 8px;font-family:' . $c['titre'] . ';font-weight:600;font-size:16px;color:' . $c['encre'] . '">Nouveaux avis Google</p>';
		foreach ( $r['avis'] as $a ) {
			$h .= '<div style="margin:0 0 10px;padding:14px 16px;border-radius:16px;background:' . $c['carte'] . ';border:1px solid #eadfd3">'
				. '<p style="margin:0;font-size:14px;color:' . $c['doux'] . '">' . bigm_recap_etoiles( (float) $a['note'] ) . ' <strong style="color:' . $c['encre'] . '">' . esc_html( '' !== $a['auteur'] ? $a['auteur'] : 'Client' ) . '</strong> · ' . esc_html( wp_date( 'j F', $a['date'] ) ) . '</p>'
				. ( '' !== trim( $a['texte'] ) ? '<p style="margin:6px 0 0;font-size:15px;line-height:1.5;color:' . $c['encre'] . '">' . nl2br( esc_html( wp_html_excerpt( $a['texte'], 600, '…' ) ) ) . '</p>' : '' )
				. '</div>';
		}
		$h .= ( '' !== $r['lien_google'] ? '<p style="margin:4px 0 0">' . bigm_recap_bouton( 'Répondre sur Google', $r['lien_google'], false ) . '</p>' : '' ) . '</td></tr>';
	}
	if ( $r['retours'] ) {
		$h .= '<tr><td style="padding:16px 28px 0"><p style="margin:0 0 8px;font-family:' . $c['titre'] . ';font-weight:600;font-size:16px;color:' . $c['encre'] . '">Retours privés laissés sur le site</p>';
		foreach ( $r['retours'] as $f ) {
			$contact = trim( (string) $f['contact'] );
			$lien    = is_email( $contact ) ? '<a href="mailto:' . esc_attr( $contact ) . '" style="color:' . $c['bouton'] . '">' . esc_html( $contact ) . '</a>' : esc_html( $contact );
			$h      .= '<div style="margin:0 0 10px;padding:14px 16px;border-radius:16px;background:' . $c['tuile'] . '">'
				. '<p style="margin:0;font-size:14px;color:' . $c['doux'] . '">' . bigm_recap_etoiles( (float) $f['rating'] ) . ' <strong style="color:' . $c['encre'] . '">' . esc_html( '' !== trim( (string) $f['name'] ) ? (string) $f['name'] : 'Anonyme' ) . '</strong>' . ( '' !== $contact ? ' · ' . $lien : '' ) . ' · ' . esc_html( wp_date( 'j F, H\hi', bigm_recap_ts( (string) $f['created_at'] ) ) ) . '</p>'
				. ( '' !== trim( (string) $f['message'] ) ? '<p style="margin:6px 0 0;font-size:15px;line-height:1.5;color:' . $c['encre'] . '">' . nl2br( esc_html( wp_html_excerpt( (string) $f['message'], 1200, '…' ) ) ) . '</p>' : '' )
				. '</div>';
		}
		$h .= '</td></tr>';
	}
	return $h;
}

/**
 * E-mail complet : tous les restaurants (adresse générale) ou un seul.
 *
 * @param array<int, array<string, mixed>> $donnees Données (bigm_recap_donnees()).
 * @param int                              $debut   Début.
 * @param int                              $fin     Fin (exclue).
 * @param bool                             $general Tous les restaurants (sinon le premier seulement).
 */
function bigm_recap_html( array $donnees, int $debut, int $fin, bool $general = true ): string {
	$c        = bigm_recap_charte();
	$logo     = function_exists( 'bigm_child_logo' ) ? bigm_child_logo()['url'] : '';
	$periode  = 'Du ' . wp_date( 'j F', $debut ) . ' au ' . wp_date( 'j F Y', $fin - 1 );
	$actifs   = array_values( array_filter( $donnees, 'bigm_recap_actif' ) );
	$avis     = array_sum( array_map( static fn( $r ) => count( $r['avis'] ), $donnees ) );
	$retours  = array_sum( array_map( static fn( $r ) => count( $r['retours'] ), $donnees ) );
	$notes    = array_filter( $donnees, static fn( $r ) => null !== $r['note'] && $r['nombre'] );
	$poids    = array_sum( array_map( static fn( $r ) => (int) $r['nombre'], $notes ) );
	$moyenne  = $poids ? array_sum( array_map( static fn( $r ) => (float) $r['note'] * (int) $r['nombre'], $notes ) ) / $poids : null;
	$admin    = admin_url( 'admin.php?page=tfmsl_reviews' );
	$titre    = $general || ! $donnees ? 'Récapitulatif des avis' : 'Les avis de ' . $donnees[0]['nom'];

	$h = '<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="light"><title>' . esc_html( $titre ) . '</title></head>'
		. '<body style="margin:0;padding:0;background:' . $c['fond'] . ';font-family:' . $c['texte'] . ';color:' . $c['encre'] . '">'
		. '<div style="display:none;max-height:0;overflow:hidden">' . esc_html( $periode . ' : ' . $avis . ' nouveaux avis Google, ' . $retours . ' retours privés.' ) . '</div>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $c['fond'] . '"><tr><td align="center" style="padding:24px 12px">'
		. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:' . $c['carte'] . ';border-radius:24px;overflow:hidden">'
		// En-tête brun, logo, filet orange.
		. '<tr><td align="center" style="background:' . $c['encre'] . ';padding:22px 24px">' . ( '' !== $logo ? '<img src="' . esc_url( $logo ) . '" alt="Big M" height="64" style="display:block;height:64px;width:auto;border:0">' : '<span style="font-family:' . $c['titre'] . ';font-weight:600;font-size:28px;color:#ffffff">Big M</span>' ) . '</td></tr>'
		. '<tr><td style="background:' . $c['orange'] . ';height:6px;line-height:6px;font-size:0">&nbsp;</td></tr>'
		. '<tr><td style="padding:28px 28px 8px"><p style="margin:0;font-family:' . $c['titre'] . ';font-weight:600;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:' . $c['bouton'] . '">' . esc_html( $periode ) . '</p>'
		. '<h1 style="margin:6px 0 0;font-family:' . $c['titre'] . ';font-weight:600;font-size:28px;line-height:1.15;color:' . $c['encre'] . '">' . esc_html( $titre ) . '</h1></td></tr>';

	if ( $general ) {
		$kpi = static function ( string $valeur, string $label ) use ( $c ): string {
			return '<td width="25%" style="padding:4px" valign="top"><div style="background:' . $c['tuile'] . ';border-radius:16px;padding:14px 8px;text-align:center"><div style="font-family:' . $c['titre'] . ';font-weight:600;font-size:26px;color:' . $c['encre'] . '">' . $valeur . '</div><div style="margin-top:4px;font-size:12px;line-height:1.3;color:' . $c['doux'] . '">' . esc_html( $label ) . '</div></div></td>';
		};
		$h .= '<tr><td style="padding:12px 24px 0"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
			. $kpi( null !== $moyenne ? esc_html( number_format_i18n( $moyenne, 2 ) ) . '<span style="color:' . $c['jaune'] . '">★</span>' : '—', 'note Google du réseau' )
			. $kpi( (string) $avis, 'nouveaux avis Google' )
			. $kpi( (string) $retours, 'retours privés' )
			. $kpi( (string) count( $actifs ), 'restaurants avec du nouveau' )
			. '</tr></table></td></tr>';
		// Tableau de tous les restaurants.
		$lignes = '';
		foreach ( $donnees as $i => $r ) {
			$lignes .= '<tr style="background:' . ( $i % 2 ? $c['carte'] : $c['fond'] ) . '">'
				. '<td style="padding:8px 10px;font-size:14px"><a href="' . esc_url( $r['url'] ) . '" style="color:' . $c['encre'] . ';text-decoration:none;font-weight:600">' . esc_html( $r['nom'] ) . '</a></td>'
				. '<td style="padding:8px 6px;font-size:14px;white-space:nowrap">' . ( null !== $r['note'] ? esc_html( number_format_i18n( (float) $r['note'], 1 ) ) . bigm_recap_evolution( $r ) : '—' ) . '</td>'
				. '<td align="center" style="padding:8px 6px;font-size:14px">' . ( $r['avis'] ? '<strong>' . count( $r['avis'] ) . '</strong>' : '<span style="color:#b9a89a">0</span>' ) . '</td>'
				. '<td align="center" style="padding:8px 6px;font-size:14px">' . ( $r['retours'] ? '<strong style="color:' . $c['bouton'] . '">' . count( $r['retours'] ) . '</strong>' : '<span style="color:#b9a89a">0</span>' ) . '</td></tr>';
		}
		$h .= '<tr><td style="padding:20px 24px 0"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:16px;overflow:hidden;border:1px solid #eadfd3">'
			. '<tr style="background:' . $c['encre'] . '"><th align="left" style="padding:8px 10px;font-size:12px;font-weight:600;color:#ffffff">Restaurant</th><th align="left" style="padding:8px 6px;font-size:12px;font-weight:600;color:#ffffff">Note</th><th style="padding:8px 6px;font-size:12px;font-weight:600;color:#ffffff">Avis</th><th style="padding:8px 6px;font-size:12px;font-weight:600;color:#ffffff">Retours</th></tr>'
			. $lignes . '</table></td></tr>';
		$detail = array_values( array_filter( $donnees, static fn( $r ) => $r['avis'] || $r['retours'] ) );
		if ( $detail ) {
			$h .= '<tr><td style="padding:28px 28px 0"><h2 style="margin:0;font-family:' . $c['titre'] . ';font-weight:600;font-size:22px;color:' . $c['encre'] . '">Le détail</h2></td></tr>';
		}
		foreach ( $detail as $r ) {
			$h .= bigm_recap_html_restaurant( $r );
		}
	} elseif ( $donnees ) {
		$h .= bigm_recap_html_restaurant( $donnees[0], false );
	}

	$maj = array_filter( array_column( $donnees, 'maj' ) );
	$bouton = $general || ! $donnees ? bigm_recap_bouton( 'Voir tous les avis dans l’admin', $admin ) : bigm_recap_bouton( 'Voir la fiche du restaurant', $donnees[0]['url'] );
	$pourquoi = $general || ! $donnees ? 'Réglages : Réglages Big M → Avis.' : 'Vous recevez cet e-mail en tant que responsable du restaurant ' . $donnees[0]['nom'] . '.';
	$h  .= '<tr><td style="padding:24px 28px 8px">' . $bouton . '</td></tr>'
		. '<tr><td style="padding:16px 28px 28px;font-size:12px;line-height:1.5;color:' . $c['doux'] . '">'
		. 'Avis Google : jusqu’à 5 par restaurant, les plus utiles selon Google' . ( $maj ? ', relevés au plus tard le ' . esc_html( wp_date( 'j F', bigm_recap_ts( (string) max( $maj ) ) ) ) : '' ) . '. Retours privés et notes : page d’avis du site (QR code).'
		. '<br>' . esc_html( $pourquoi . ' ' . (string) bigm_reglage( 'pied_mention' ) ) . '</td></tr>'
		. '</table></td></tr></table></body></html>';
	return $h;
}

/**
 * Version texte de l'e-mail (lecteurs sans HTML, filtres anti-spam).
 *
 * @param string $html E-mail HTML.
 */
function bigm_recap_texte( string $html ): string {
	$t = preg_replace( array( '#<(br|/p|/tr|/h1|/h2|/div)[^>]*>#i', '#<td[^>]*>#i' ), array( "\n", ' ' ), $html );
	$t = html_entity_decode( wp_strip_all_tags( (string) $t ), ENT_QUOTES );
	return trim( (string) preg_replace( "/\n\s*\n+/", "\n\n", (string) preg_replace( '/[ \t]+/', ' ', $t ) ) );
}

/**
 * Envoie un e-mail HTML (expéditeur « Big M », version texte jointe).
 *
 * @param string $a     Destinataire.
 * @param string $sujet Sujet.
 * @param string $html  Contenu.
 */
function bigm_recap_mail( string $a, string $sujet, string $html ): bool {
	$nom   = static fn(): string => 'Big M';
	$texte = static function ( $phpmailer ) use ( $html ) {
		$phpmailer->AltBody = bigm_recap_texte( $html ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	};
	add_filter( 'wp_mail_from_name', $nom );
	add_action( 'phpmailer_init', $texte );
	$ok = wp_mail( $a, $sujet, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	remove_filter( 'wp_mail_from_name', $nom );
	remove_action( 'phpmailer_init', $texte );
	return (bool) $ok;
}

/**
 * Envoie les récapitulatifs d'une période.
 *
 * @param int         $debut   Début.
 * @param int         $fin     Fin (exclue).
 * @param string|null $essai   Adresse d'essai : tout lui est envoyé (général et un restaurant), rien aux autres.
 * @return array{general: string, restaurants: string[], erreurs: string[]}
 */
function bigm_recap_envoyer( int $debut, int $fin, ?string $essai = null ): array {
	$donnees = bigm_recap_donnees( $debut, $fin );
	$periode = wp_date( 'j M', $debut ) . ' – ' . wp_date( 'j M Y', $fin - 1 );
	$bilan   = array(
		'general'     => '',
		'restaurants' => array(),
		'erreurs'     => array(),
	);
	$general = $essai ? $essai : sanitize_email( (string) bigm_reglage( 'recap_email' ) );
	if ( $general ) {
		$ok = bigm_recap_mail( $general, ( $essai ? '[Essai] ' : '' ) . 'Avis Big M · ' . $periode, bigm_recap_html( $donnees, $debut, $fin, true ) );
		if ( $ok ) {
			$bilan['general'] = $general;
		} else {
			$bilan['erreurs'][] = $general;
		}
	}
	if ( '1' === (string) bigm_reglage( 'recap_restaurants' ) || $essai ) {
		foreach ( $donnees as $r ) {
			$a = $essai ? $essai : $r['email'];
			if ( ! $a || ! bigm_recap_actif( $r ) ) {
				continue;
			}
			$ok = bigm_recap_mail( $a, ( $essai ? '[Essai] ' : '' ) . 'Vos avis · ' . $r['nom'] . ' · ' . $periode, bigm_recap_html( array( $r ), $debut, $fin, false ) );
			if ( $ok ) {
				$bilan['restaurants'][] = $r['nom'] . ' (' . $a . ')';
			} else {
				$bilan['erreurs'][] = $a;
			}
			if ( $essai ) {
				break; // Un seul exemple de restaurant pour l'essai.
			}
		}
	}
	return $bilan;
}

/* ---------------------------------------------------------------------------------------------
 * Planification : chaque jour à 9 h, envoi si c'est le jour de la fréquence.
 * ------------------------------------------------------------------------------------------ */

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'bigm_recap_avis' ) ) {
			$neuf = new DateTimeImmutable( 'today 09:00', wp_timezone() );
			$neuf = $neuf->getTimestamp() < time() ? $neuf->modify( '+1 day' ) : $neuf;
			wp_schedule_event( $neuf->getTimestamp(), 'daily', 'bigm_recap_avis' );
		}
	}
);

add_action(
	'bigm_recap_avis',
	static function () {
		$frequence = (string) bigm_reglage( 'recap_frequence' );
		$aujourd   = ( new DateTimeImmutable( 'today', wp_timezone() ) )->format( 'Y-m-d' );
		if ( ! bigm_recap_jour_d_envoi( $frequence, time() ) || get_option( 'bigm_recap_dernier_jour' ) === $aujourd ) {
			return;
		}
		update_option( 'bigm_recap_dernier_jour', $aujourd, false );
		list( $debut, $fin ) = bigm_recap_periode( $frequence );
		$bilan = bigm_recap_envoyer( $debut, $fin );
		update_option( 'bigm_recap_dernier', array( 'date' => current_time( 'mysql' ) ) + $bilan, false );
	}
);

/* ---------------------------------------------------------------------------------------------
 * Admin : Restaurants → Récapitulatif des avis (aperçu, essai, envoi), et API REST.
 * ------------------------------------------------------------------------------------------ */

add_action(
	'admin_menu',
	static function () {
		if ( post_type_exists( 'tfmsl_store' ) ) {
			add_submenu_page( 'edit.php?post_type=tfmsl_store', 'Récapitulatif des avis', 'Récapitulatif des avis', 'manage_options', 'bigm-recap-avis', 'bigm_recap_page' );
		}
	},
	21
);

/**
 * Page « Récapitulatif des avis ».
 */
function bigm_recap_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$message = '';
	if ( isset( $_POST['bigm_recap_action'] ) && check_admin_referer( 'bigm_recap' ) ) {
		$frequence           = (string) bigm_reglage( 'recap_frequence' );
		list( $debut, $fin ) = bigm_recap_periode( in_array( $frequence, array( 'jour', 'semaine', 'mois' ), true ) ? $frequence : 'semaine', time() );
		$essai               = 'essai' === $_POST['bigm_recap_action'] ? (string) wp_get_current_user()->user_email : null;
		$bilan               = bigm_recap_envoyer( $debut, $fin, $essai );
		$message             = $bilan['erreurs'] ? 'Échec de l’envoi vers : ' . implode( ', ', $bilan['erreurs'] ) . '. Vérifier l’envoi des e-mails du site (extension SMTP).' : 'Envoyé' . ( $bilan['general'] ? ' à ' . $bilan['general'] : '' ) . ( $bilan['restaurants'] ? ' et à ' . count( $bilan['restaurants'] ) . ' restaurant(s)' : '' ) . '.';
	}
	$frequence           = (string) bigm_reglage( 'recap_frequence' );
	list( $debut, $fin ) = bigm_recap_periode( in_array( $frequence, array( 'jour', 'semaine', 'mois' ), true ) ? $frequence : 'semaine', time() );
	$resto               = isset( $_GET['restaurant'] ) ? absint( $_GET['restaurant'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$donnees             = bigm_recap_donnees( $debut, $fin, $resto ? array( $resto ) : null );
	$html                = bigm_recap_html( $donnees, $debut, $fin, ! $resto );
	$dernier             = (array) get_option( 'bigm_recap_dernier', array() );
	$restos              = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$sans_email          = count( array_filter( $restos, static fn( $p ) => ! is_email( (string) get_post_meta( $p->ID, 'tfmsl_manager_email', true ) ) ) );
	?>
	<div class="wrap">
		<h1>Récapitulatif des avis</h1>
		<?php if ( $message ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>
		<p style="max-width:75ch">Envoi <?php echo esc_html( array( 'jour' => 'chaque jour à 9 h', 'semaine' => 'chaque lundi à 9 h (semaine écoulée)', 'mois' => 'le 1er de chaque mois à 9 h (mois écoulé)' )[ $frequence ] ?? 'désactivé' ); ?>.
			Adresse générale : <strong><?php echo esc_html( (string) bigm_reglage( 'recap_email' ) ? (string) bigm_reglage( 'recap_email' ) : 'aucune' ); ?></strong>.
			Restaurants : <?php echo '1' === (string) bigm_reglage( 'recap_restaurants' ) ? esc_html( sprintf( 'chacun reçoit le sien s’il y a du nouveau (« Email du responsable » de sa fiche, onglet Intégrations ; %d fiche(s) sans adresse)', $sans_email ) ) : 'pas d’envoi'; ?>.
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=bigm-reglages#avis' ) ); ?>">Modifier</a>
			<?php if ( ! empty( $dernier['date'] ) ) : ?>
				<br>Dernier envoi : <?php echo esc_html( wp_date( 'j F Y à H\hi', bigm_recap_ts( (string) $dernier['date'] ) ) ); ?><?php echo ! empty( $dernier['erreurs'] ) ? esc_html( ' (échecs : ' . implode( ', ', (array) $dernier['erreurs'] ) . ')' ) : ''; ?>.
			<?php endif; ?>
		</p>
		<form method="get" style="margin:12px 0">
			<input type="hidden" name="post_type" value="tfmsl_store"><input type="hidden" name="page" value="bigm-recap-avis">
			<label for="bigm-recap-resto">Aperçu :</label>
			<select id="bigm-recap-resto" name="restaurant" onchange="this.form.submit()">
				<option value="0">E-mail général (tous les restaurants)</option>
				<?php foreach ( $restos as $p ) : ?>
					<option value="<?php echo (int) $p->ID; ?>"<?php selected( $resto, $p->ID ); ?>><?php echo esc_html( get_the_title( $p ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</form>
		<form method="post" style="margin:0 0 16px">
			<?php wp_nonce_field( 'bigm_recap' ); ?>
			<button class="button button-secondary" name="bigm_recap_action" value="essai">M’envoyer un essai (<?php echo esc_html( (string) wp_get_current_user()->user_email ); ?>)</button>
			<button class="button" name="bigm_recap_action" value="envoyer" onclick="return confirm('Envoyer le récapitulatif maintenant à l’adresse générale et aux restaurants ?')">Envoyer maintenant</button>
		</form>
		<iframe title="Aperçu de l’e-mail" srcdoc="<?php echo esc_attr( $html ); ?>" style="width:100%;max-width:680px;height:1100px;border:1px solid #dcdcde;border-radius:8px;background:#fff"></iframe>
	</div>
	<?php
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'bigm/v1',
			'/avis/recap',
			array(
				'methods'             => 'POST',
				'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				'args'                => array(
					'action'     => array(
						'type'    => 'string',
						'enum'    => array( 'apercu', 'essai', 'envoyer' ),
						'default' => 'apercu',
					),
					'restaurant' => array(
						'type'    => 'integer',
						'default' => 0,
					),
					'jours'      => array(
						'type'    => 'integer',
						'default' => 7,
					),
				),
				'callback'            => static function ( WP_REST_Request $q ) {
					$fin   = time();
					$debut = $fin - max( 1, (int) $q['jours'] ) * DAY_IN_SECONDS;
					if ( 'apercu' === $q['action'] ) {
						$d = bigm_recap_donnees( $debut, $fin, $q['restaurant'] ? array( (int) $q['restaurant'] ) : null );
						return array(
							'html'    => bigm_recap_html( $d, $debut, $fin, ! $q['restaurant'] ),
							'actifs'  => count( array_filter( $d, 'bigm_recap_actif' ) ),
							'avis'    => array_sum( array_map( static fn( $r ) => count( $r['avis'] ), $d ) ),
							'retours' => array_sum( array_map( static fn( $r ) => count( $r['retours'] ), $d ) ),
						);
					}
					return bigm_recap_envoyer( $debut, $fin, 'essai' === $q['action'] ? (string) wp_get_current_user()->user_email : null );
				},
			)
		);
	}
);
