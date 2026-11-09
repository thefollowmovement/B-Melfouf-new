<?php
/**
 * Réglages → Cookies : textes du bandeau, page de la politique, durée du choix, finalités
 * proposées, et « Redemander le choix à tous les visiteurs » (nouvelle version du texte).
 *
 * @package BigM\Cookies
 */

defined( 'ABSPATH' ) || exit;

/**
 * Réglages par défaut.
 *
 * @return array{titre: string, texte: string, politique: int, duree: int, version: string, mesure: string, contenus: string}
 */
function bigm_cookies_defauts(): array {
	// Page « cookies » (get_page_by_path() pourrait renvoyer un média de même adresse).
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'name'           => 'cookies',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return array(
		'titre'     => 'Cookies : à vous de choisir',
		'texte'     => 'Avec votre accord, nous mesurons la fréquentation du site (Google Analytics) pour l’améliorer, et nous affichons des contenus d’autres sites, comme des vidéos. Les cookies nécessaires au fonctionnement du site sont toujours actifs. Votre choix est gardé 6 mois, et vous pouvez le changer à tout moment avec le lien « Paramétrer les cookies » en bas de page.',
		'politique' => $pages ? (int) $pages[0] : (int) get_option( 'wp_page_for_privacy_policy' ),
		'duree'     => 182,
		'version'   => '1',
		'mesure'    => '1',
		'contenus'  => '1',
	);
}

/**
 * Réglages enregistrés, complétés par les valeurs par défaut.
 *
 * @return array{titre: string, texte: string, politique: int, duree: int, version: string, mesure: string, contenus: string}
 */
function bigm_cookies_reglages(): array {
	$r = array_merge( bigm_cookies_defauts(), array_filter( (array) get_option( 'bigm_cookies', array() ), static fn( $v ): bool => '' !== $v && null !== $v ) );
	return (array) apply_filters( 'bigm_cookies_reglages', $r );
}

/**
 * Finalités du bandeau, dans l'ordre. « necessaires » est toujours active et ne se refuse pas.
 *
 * @return array<string, array{nom: string, texte: string, cookies: array<int, array{0: string, 1: string, 2: string}>, toujours: bool}>
 */
function bigm_cookies_categories(): array {
	$r     = bigm_cookies_reglages();
	$jours = (int) $r['duree'];
	$cats  = array(
		'necessaires' => array(
			'nom'      => 'Nécessaires au site',
			'texte'    => 'Ils font fonctionner le site et ne peuvent pas être refusés : mémoriser votre choix sur les cookies, le restaurant que vous avez choisi pour commander (gardé dans votre navigateur), la connexion des administrateurs.',
			'cookies'  => array(
				array( 'bigm_cookies', 'Big M', 'Votre choix sur les cookies, ' . $jours . ' jours' ),
				array( 'tfmsl_promo_store', 'Big M', 'Restaurant choisi pour commander (stockage du navigateur), jusqu’à ce que vous en changiez' ),
				array( 'wordpress_*, wp-settings-*', 'Big M', 'Connexion à l’administration du site, administrateurs seulement' ),
			),
			'toujours' => true,
		),
		'mesure'      => array(
			'nom'      => 'Mesure d’audience',
			'texte'    => 'Google Analytics nous indique les pages vues, la durée des visites et le type d’appareil, pour améliorer le site. Fournisseur : Google Ireland Ltd ; des données peuvent être transférées aux États-Unis (Google LLC, adhérent au Data Privacy Framework).',
			'cookies'  => array(
				array( '_ga, _ga_*', 'Google Analytics', 'Distinguer les visiteurs et les visites, 13 mois au plus (non prolongés à chaque visite)' ),
			),
			'toujours' => false,
		),
		'contenus'    => array(
			'nom'      => 'Contenus d’autres sites',
			'texte'    => 'Vidéos et publications intégrées aux pages (YouTube, Vimeo, Instagram, TikTok, X, Facebook…). Ces sites peuvent déposer leurs propres cookies et suivre votre navigation.',
			'cookies'  => array(
				array( 'Cookies de YouTube, Vimeo, Instagram…', 'Le site concerné', 'Selon le site, jusqu’à 2 ans' ),
			),
			'toujours' => false,
		),
	);
	foreach ( array( 'mesure', 'contenus' ) as $cle ) {
		if ( '1' !== (string) $r[ $cle ] ) {
			unset( $cats[ $cle ] );
		}
	}
	return (array) apply_filters( 'bigm_cookies_categories', $cats );
}

/**
 * Nettoie les réglages envoyés par le formulaire.
 *
 * @param mixed $in Valeurs envoyées.
 * @return array<string, mixed>
 */
function bigm_cookies_nettoyer( $in ): array {
	$in     = is_array( $in ) ? $in : array();
	$avant  = bigm_cookies_reglages();
	$out    = array(
		'titre'     => sanitize_text_field( (string) ( $in['titre'] ?? '' ) ),
		'texte'     => sanitize_textarea_field( (string) ( $in['texte'] ?? '' ) ),
		'politique' => absint( $in['politique'] ?? 0 ),
		'duree'     => max( 1, min( 395, absint( $in['duree'] ?? 182 ) ) ),
		'version'   => (string) $avant['version'],
		'mesure'    => ! empty( $in['mesure'] ) ? '1' : '0',
		'contenus'  => ! empty( $in['contenus'] ) ? '1' : '0',
	);
	if ( ! empty( $in['redemander'] ) ) {
		$out['version'] = (string) time();
	}
	return $out;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'bigm_cookies',
			'bigm_cookies',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'bigm_cookies_nettoyer',
			)
		);
	}
);

add_action(
	'admin_menu',
	static function () {
		add_options_page( 'Cookies', 'Cookies', 'manage_options', 'bigm-cookies', 'bigm_cookies_page' );
	}
);

/**
 * Page Réglages → Cookies.
 */
function bigm_cookies_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$r = bigm_cookies_reglages();
	?>
	<div class="wrap">
		<h1>Cookies</h1>
		<p>Le bandeau s’affiche à la première visite. Rien n’est déposé ni chargé avant l’accord du visiteur (Google Analytics de Site Kit et contenus d’autres sites compris). « Tout refuser » est aussi visible que « Tout accepter », comme le demande la CNIL. Lien pour rouvrir le bandeau : <code>#gestion-cookies</code> (ajouté seul au pied de page) ; sur la page de la politique, les codes courts <code>[bigm_cookies_gestion]</code> (bouton) et <code>[bigm_cookies_liste]</code> (tableau des cookies).</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'bigm_cookies' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="bigm_cookies_titre">Titre du bandeau</label></th>
					<td><input type="text" class="regular-text" id="bigm_cookies_titre" name="bigm_cookies[titre]" value="<?php echo esc_attr( $r['titre'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="bigm_cookies_texte">Texte</label></th>
					<td><textarea class="large-text" rows="5" id="bigm_cookies_texte" name="bigm_cookies[texte]"><?php echo esc_textarea( $r['texte'] ); ?></textarea>
						<p class="description">Dire à quoi servent les cookies, que le refus est possible, et comment changer d’avis.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="bigm_cookies_politique">Page « En savoir plus »</label></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'              => 'bigm_cookies[politique]',
								'id'                => 'bigm_cookies_politique',
								'selected'          => (int) $r['politique'],
								'show_option_none'  => '— Aucune —',
								'option_none_value' => '0',
							)
						);
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="bigm_cookies_duree">Durée du choix</label></th>
					<td><input type="number" min="1" max="395" class="small-text" id="bigm_cookies_duree" name="bigm_cookies[duree]" value="<?php echo (int) $r['duree']; ?>"> jours
						<p class="description">La CNIL recommande de redemander le choix au bout de 6 mois (182 jours) ; 13 mois au plus.</p></td>
				</tr>
				<tr>
					<th scope="row">Finalités proposées</th>
					<td>
						<label><input type="checkbox" name="bigm_cookies[mesure]" value="1"<?php checked( '1', (string) $r['mesure'] ); ?>> Mesure d’audience (Google Analytics)</label><br>
						<label><input type="checkbox" name="bigm_cookies[contenus]" value="1"<?php checked( '1', (string) $r['contenus'] ); ?>> Contenus d’autres sites (vidéos, publications)</label>
						<p class="description">Une finalité décochée n’est plus proposée, et ce qui en dépend reste bloqué.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Nouveau choix</th>
					<td><label><input type="checkbox" name="bigm_cookies[redemander]" value="1"> Redemander leur choix à tous les visiteurs</label>
						<p class="description">À cocher quand un nouveau service est ajouté ou que le texte change beaucoup.</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
