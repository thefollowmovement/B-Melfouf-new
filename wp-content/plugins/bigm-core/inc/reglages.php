<?php
/**
 * Page « Réglages Big M » : lien Commander, applis, réseaux sociaux, contact,
 * chiffres de la franchise. Une seule option : bigm_reglages.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Champs de la page, par section. Types : url, email, text, annees, euros, pourcent.
 *
 * @return array<string, array{titre: string, aide?: string, champs: array<string, array{label: string, type: string, defaut: string|int}>}>
 */
function bigm_reglages_champs(): array {
	return array(
		'commande'  => array(
			'titre'  => 'Commande',
			'aide'   => 'Lien des boutons « Commander » du site. Une fiche produit peut avoir son propre lien.',
			'champs' => array(
				'commander_url' => array(
					'label'  => 'Lien Commander',
					'type'   => 'url',
					'defaut' => '',
				),
			),
		),
		'appli'     => array(
			'titre'  => 'Appli Big M',
			'champs' => array(
				'appstore_url'   => array(
					'label'  => 'App Store',
					'type'   => 'url',
					'defaut' => 'https://apps.apple.com/fr/app/big-m/id6444707190',
				),
				'googleplay_url' => array(
					'label'  => 'Google Play',
					'type'   => 'url',
					'defaut' => 'https://play.google.com/store/apps/details?id=com.dishop.big&gl=FR',
				),
			),
		),
		'reseaux'   => array(
			'titre'  => 'Réseaux sociaux',
			'aide'   => 'Un champ vide n’affiche pas l’icône dans le pied de page.',
			'champs' => array(
				'instagram_url' => array(
					'label'  => 'Instagram',
					'type'   => 'url',
					'defaut' => 'https://www.instagram.com/bigm_officiel/',
				),
				'snapchat_url'  => array(
					'label'  => 'Snapchat',
					'type'   => 'url',
					'defaut' => 'https://www.snapchat.com/add/bigm.officiel',
				),
				'x_url'         => array(
					'label'  => 'X (Twitter)',
					'type'   => 'url',
					'defaut' => 'https://twitter.com/BigM_officiel',
				),
				'tiktok_url'    => array(
					'label'  => 'TikTok',
					'type'   => 'url',
					'defaut' => '',
				),
				'facebook_url'  => array(
					'label'  => 'Facebook',
					'type'   => 'url',
					'defaut' => '',
				),
				'youtube_url'   => array(
					'label'  => 'YouTube',
					'type'   => 'url',
					'defaut' => '',
				),
			),
		),
		'contact'   => array(
			'titre'  => 'Contact',
			'champs' => array(
				'contact_email' => array(
					'label'  => 'E-mail de contact',
					'type'   => 'email',
					'defaut' => 'contact@bigm-burger.fr',
				),
				'siege'         => array(
					'label'  => 'Siège',
					'type'   => 'text',
					'defaut' => 'Villeneuve-la-Garenne',
				),
			),
		),
		'franchise' => array(
			'titre'  => 'Franchise',
			'aide'   => 'Chiffres affichés sur la page « Devenir franchisé ».',
			'champs' => array(
				'franchise_duree'                   => array(
					'label'  => 'Durée du contrat',
					'type'   => 'annees',
					'defaut' => 5,
				),
				'franchise_droit_entree'            => array(
					'label'  => 'Droit d’entrée',
					'type'   => 'euros',
					'defaut' => 30000,
				),
				'franchise_formation'               => array(
					'label'  => 'Formation',
					'type'   => 'euros',
					'defaut' => 5000,
				),
				'franchise_redevance_enseigne'      => array(
					'label'  => 'Redevance d’enseigne',
					'type'   => 'pourcent',
					'defaut' => 5,
				),
				'franchise_redevance_communication' => array(
					'label'  => 'Redevance communication',
					'type'   => 'pourcent',
					'defaut' => 2,
				),
			),
		),
	);
}

/**
 * Tous les champs à plat : clé => définition.
 *
 * @return array<string, array{label: string, type: string, defaut: string|int}>
 */
function bigm_reglages_definitions(): array {
	return array_merge( ...array_values( array_column( bigm_reglages_champs(), 'champs' ) ) );
}

/**
 * Valeur d'un réglage (brute : URL, nombre, texte).
 *
 * @param string $cle Clé du réglage.
 * @return string|int|float
 */
function bigm_reglage( string $cle ) {
	$defs    = bigm_reglages_definitions();
	$valeurs = (array) get_option( 'bigm_reglages', array() );
	if ( array_key_exists( $cle, $valeurs ) ) {
		return $valeurs[ $cle ];
	}
	return $defs[ $cle ]['defaut'] ?? '';
}

/**
 * Valeur d'un réglage mise en forme pour l'affichage : « 30 000 € HT », « 5 % », « 5 ans ».
 *
 * @param string $cle Clé du réglage.
 */
function bigm_reglage_texte( string $cle ): string {
	$type   = bigm_reglages_definitions()[ $cle ]['type'] ?? 'text';
	$valeur = bigm_reglage( $cle );
	if ( '' === $valeur ) {
		return '';
	}
	// Toujours à la française, quelle que soit la langue du site : 30 000 et 5,5.
	$nombre = static fn( $v ): string => rtrim( rtrim( number_format( (float) $v, 2, ',', "\u{202F}" ), '0' ), ',' );
	switch ( $type ) {
		case 'euros':
			return $nombre( $valeur ) . "\u{202F}€\u{00A0}HT";
		case 'pourcent':
			return $nombre( $valeur ) . "\u{202F}%";
		case 'annees':
			return $nombre( $valeur ) . "\u{00A0}" . ( (float) $valeur >= 2 ? 'ans' : 'an' );
		default:
			return (string) $valeur;
	}
}

/**
 * Nettoie les réglages envoyés par le formulaire.
 *
 * @param mixed $input Valeurs reçues.
 * @return array<string, string|int|float>
 */
function bigm_reglages_nettoyer( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( bigm_reglages_definitions() as $cle => $def ) {
		$v = trim( (string) ( $input[ $cle ] ?? '' ) );
		switch ( $def['type'] ) {
			case 'url':
				$out[ $cle ] = esc_url_raw( $v );
				break;
			case 'email':
				$out[ $cle ] = sanitize_email( $v );
				break;
			case 'euros':
			case 'pourcent':
			case 'annees':
				$v           = str_replace( array( ' ', "\u{00A0}", "\u{202F}", ',' ), array( '', '', '', '.' ), $v );
				$out[ $cle ] = '' === $v ? '' : max( 0, round( (float) $v, 2 ) );
				break;
			default:
				$out[ $cle ] = sanitize_text_field( $v );
		}
	}
	return $out;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'bigm_reglages',
			'bigm_reglages',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'bigm_reglages_nettoyer',
				'default'           => array(),
			)
		);
	}
);

add_action(
	'admin_menu',
	static function () {
		add_menu_page( 'Réglages Big M', 'Réglages Big M', 'manage_options', 'bigm-reglages', 'bigm_reglages_page', 'dashicons-store', 23 );
	}
);

/**
 * Page des réglages.
 */
function bigm_reglages_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$unites = array(
		'euros'    => '€ HT',
		'pourcent' => '%',
		'annees'   => 'ans',
	);
	?>
	<div class="wrap bigm-reglages">
		<h1>Réglages Big M</h1>
		<?php settings_errors(); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'bigm_reglages' ); ?>
			<?php foreach ( bigm_reglages_champs() as $section ) : ?>
				<h2><?php echo esc_html( $section['titre'] ); ?></h2>
				<?php if ( ! empty( $section['aide'] ) ) : ?>
					<p class="description"><?php echo esc_html( $section['aide'] ); ?></p>
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<?php
					foreach ( $section['champs'] as $cle => $def ) :
						$valeur  = bigm_reglage( $cle );
						$numeric = isset( $unites[ $def['type'] ] );
						$type    = $numeric ? 'number' : ( 'text' === $def['type'] ? 'text' : $def['type'] );
						?>
						<tr>
							<th scope="row"><label for="bigm_<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $def['label'] ); ?></label></th>
							<td>
								<input type="<?php echo esc_attr( $type ); ?>" id="bigm_<?php echo esc_attr( $cle ); ?>" name="bigm_reglages[<?php echo esc_attr( $cle ); ?>]" value="<?php echo esc_attr( (string) $valeur ); ?>" class="<?php echo $numeric ? 'small-text' : 'regular-text'; ?>"<?php echo $numeric ? ' min="0" step="any"' : ''; ?>>
								<?php if ( $numeric ) : ?>
									<?php echo esc_html( $unites[ $def['type'] ] ); ?>
								<?php endif; ?>
								<p class="description"><code>[bigm_reglage cle="<?php echo esc_html( $cle ); ?>"]</code><?php echo $numeric && '' !== $valeur ? ' → ' . esc_html( bigm_reglage_texte( $cle ) ) : ''; ?></p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
		<p class="description">Les codes entre crochets s’insèrent dans un widget Texte d’Elementor pour afficher la valeur à jour.</p>
	</div>
	<?php
}

/*
 * [bigm_reglage cle="franchise_droit_entree"] → 30 000 € HT
 */
add_shortcode(
	'bigm_reglage',
	static function ( $atts ): string {
		$atts = shortcode_atts( array( 'cle' => '' ), $atts, 'bigm_reglage' );
		$cle  = sanitize_key( $atts['cle'] );
		if ( ! array_key_exists( $cle, bigm_reglages_definitions() ) ) {
			return '';
		}
		$type = bigm_reglages_definitions()[ $cle ]['type'];
		$text = bigm_reglage_texte( $cle );
		if ( 'url' === $type ) {
			return esc_url( $text );
		}
		return 'email' === $type ? esc_html( antispambot( $text ) ) : esc_html( $text );
	}
);
