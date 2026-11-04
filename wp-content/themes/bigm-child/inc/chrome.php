<?php
/**
 * En-tête, tiroir, barre d'onglets mobile et pied de page.
 * Balisage repris de prototype/build.py (fonctions header(), tabbar() et FOOTER).
 *
 * L'en-tête et le pied de page remplacent ceux de Hello Elementor
 * (template-parts/). Le tiroir et la barre d'onglets sont ajoutés en fin de
 * page : ils restent en place même avec un en-tête du Theme Builder.
 *
 * @package BigM\Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * En-tête en capsule : menu à gauche, logo au centre, Restaurants et Commander à droite.
 */
function bigm_child_entete_html(): string {
	$logo = bigm_child_logo();
	ob_start();
	?>
<header class="header">
  <div class="header__inner">
    <div class="header__left">
      <button class="burger" type="button" data-open-drawer aria-expanded="false" aria-controls="drawer" aria-label="Ouvrir le menu"><?php echo bigm_child_icone( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
      <nav class="header__nav" aria-label="Navigation principale">
		<?php foreach ( bigm_child_liens( 'bigm-principal' ) as $lien ) : ?>
        <a<?php echo bigm_child_attributs_lien( $lien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $lien['titre'] ); ?></a>
		<?php endforeach; ?>
      </nav>
    </div>
    <a class="header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Big M, accueil"><img src="<?php echo esc_url( $logo['url'] ); ?>" alt="Big M" width="<?php echo (int) $logo['width']; ?>" height="<?php echo (int) $logo['height']; ?>"></a>
    <div class="header__right">
      <a class="hide-sm" href="<?php echo esc_url( bigm_child_lien_restaurants() ); ?>"<?php echo bigm_child_est_courant( bigm_child_lien_restaurants() ) ? ' aria-current="page"' : ''; ?>><?php echo bigm_child_icone( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Restaurants</a>
      <a class="btn" href="<?php echo esc_url( bigm_child_lien_commander() ); ?>"><?php echo bigm_child_icone( 'bag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Commander</a>
    </div>
  </div>
</header>
	<?php
	return (string) ob_get_clean();
}

/**
 * Menu latéral (tiroir), ouvert par le bouton menu de l'en-tête ou « Plus » de la barre d'onglets.
 */
function bigm_child_tiroir_html(): string {
	ob_start();
	?>
<div class="drawer" id="drawer" data-open="false" role="dialog" aria-modal="true" aria-label="Menu">
  <div class="drawer__scrim" data-close-drawer></div>
  <nav class="drawer__panel" aria-label="Menu">
    <button class="drawer__close" type="button" data-close-drawer aria-label="Fermer le menu"><?php echo bigm_child_icone( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
	<?php foreach ( bigm_child_liens( 'bigm-tiroir' ) as $lien ) : ?>
    <a<?php echo bigm_child_attributs_lien( $lien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $lien['titre'] ); ?></a>
	<?php endforeach; ?>
  </nav>
</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Barre d'onglets mobile : deux liens, le bouton central Commander, un lien, puis « Plus ».
 */
function bigm_child_onglets_html(): string {
	$liens    = array_slice( bigm_child_liens( 'bigm-onglets' ), 0, 3 );
	$icones   = array( 'accueil', 'carte', 'restos' );
	$logo     = bigm_child_logo();
	$item     = static function ( array $lien, int $i ) use ( $icones ): string {
		$icone = $lien['icone'] ? $lien['icone'] : $icones[ $i ];
		return '<a class="tabbar__item"' . bigm_child_attributs_lien( $lien ) . '>' . bigm_child_icone( $icone ) . esc_html( $lien['titre'] ) . '</a>';
	};
	$avant    = '';
	$apres    = '';
	foreach ( $liens as $i => $lien ) {
		if ( $i < 2 ) {
			$avant .= $item( $lien, $i );
		} else {
			$apres .= $item( $lien, $i );
		}
	}
	ob_start();
	?>
<nav class="tabbar" aria-label="Navigation rapide">
  <div class="tabbar__bar">
    <?php echo $avant; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construit et échappé ci-dessus. ?>
    <a class="tabbar__fab" href="<?php echo esc_url( bigm_child_lien_commander() ); ?>" aria-label="Commander"><img src="<?php echo esc_url( $logo['url'] ); ?>" alt="" width="<?php echo (int) $logo['width']; ?>" height="<?php echo (int) $logo['height']; ?>"></a>
    <?php echo $apres; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <button class="tabbar__item" type="button" data-open-drawer aria-controls="drawer" aria-expanded="false"><?php echo bigm_child_icone( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>Plus</button>
    <span class="tabbar__label" aria-hidden="true">Commander</span>
  </div>
</nav>
	<?php
	return (string) ob_get_clean();
}

/**
 * Pied de page brun : logo, réseaux sociaux, applis, trois colonnes de liens,
 * mention obligatoire mangerbouger.fr.
 */
function bigm_child_pied_html(): string {
	$logo    = bigm_child_logo();
	$nnbsp   = "\u{202F}";
	$reseaux = array(
		'instagram_url' => array( 'Instagram', 'instagram', 'https://www.instagram.com/bigm_officiel/' ),
		'snapchat_url'  => array( 'Snapchat', 'snapchat', 'https://www.snapchat.com/add/bigm.officiel' ),
		'x_url'         => array( 'X', 'x', 'https://twitter.com/BigM_officiel' ),
		'tiktok_url'    => array( 'TikTok', 'tiktok', '' ),
		'facebook_url'  => array( 'Facebook', 'facebook', '' ),
		'youtube_url'   => array( 'YouTube', 'youtube', '' ),
	);
	// Valeurs du prototype seulement si bigm-core est inactif : un champ vidé dans
	// Réglages Big M masque l'élément.
	$secours    = static fn( string $v ): string => function_exists( 'bigm_reglage' ) ? '' : $v;
	$appstore   = bigm_child_reglage( 'appstore_url', $secours( 'https://apps.apple.com/fr/app/big-m/id6444707190' ) );
	$googleplay = bigm_child_reglage( 'googleplay_url', $secours( 'https://play.google.com/store/apps/details?id=com.dishop.big&gl=FR' ) );
	$email      = bigm_child_reglage( 'contact_email', $secours( 'contact@bigm-burger.fr' ) );
	$siege      = bigm_child_reglage( 'siege', $secours( 'Villeneuve-la-Garenne' ) );
	$mention    = bigm_child_reglage( 'pied_mention', $secours( "Big M, c'est ça qu'on aime{$nnbsp}!" ) );
	$mention    = '' !== $mention ? $mention : 'Big M';
	$colonne    = static function ( string $emplacement ): string {
		$html = '';
		foreach ( bigm_child_liens( $emplacement ) as $lien ) {
			$html .= '<li><a' . bigm_child_attributs_lien( $lien ) . '>' . esc_html( $lien['titre'] ) . '</a></li>';
		}
		return $html;
	};
	ob_start();
	?>
<footer class="footer">
  <div class="container footer__top">
    <div class="footer__brand">
      <img src="<?php echo esc_url( $logo['url'] ); ?>" alt="Big M" width="<?php echo (int) $logo['width']; ?>" height="<?php echo (int) $logo['height']; ?>" loading="lazy">
      <div class="footer__social">
		<?php
		foreach ( $reseaux as $cle => $reseau ) :
			$url = bigm_child_reglage( $cle, $secours( $reseau[2] ) );
			if ( '' === $url ) {
				continue;
			}
			?>
        <a href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $reseau[0] ); ?>"><?php echo bigm_child_icone( $reseau[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
		<?php endforeach; ?>
      </div>
      <p class="footer__app">Big M dans votre poche</p>
      <div class="stores">
		<?php if ( $appstore ) : ?>
        <a class="store" href="<?php echo esc_url( $appstore ); ?>"><?php echo bigm_child_icone( 'apple' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small>Télécharger dans</small><b>l'App Store</b></span></a>
		<?php endif; ?>
		<?php if ( $googleplay ) : ?>
        <a class="store" href="<?php echo esc_url( $googleplay ); ?>"><?php echo bigm_child_icone( 'play' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><small>Disponible sur</small><b>Google Play</b></span></a>
		<?php endif; ?>
      </div>
    </div>
    <div>
      <h3>Produits</h3>
      <ul><?php echo $colonne( 'bigm-pied-produits' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
    </div>
    <div>
      <h3>L'entreprise</h3>
      <ul><?php echo $colonne( 'bigm-pied-entreprise' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
    </div>
    <div>
      <h3>Contact</h3>
      <ul>
		<?php if ( $email ) : ?>
        <li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li>
		<?php endif; ?>
		<?php if ( $siege ) : ?>
        <li>Siège<?php echo esc_html( $nnbsp ); ?>: <?php echo esc_html( $siege ); ?></li>
		<?php endif; ?>
        <?php echo $colonne( 'bigm-pied-legal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
      </ul>
    </div>
  </div>
  <div class="container footer__bottom">
    <span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $mention ); ?></span>
    <span>Pour votre santé, mangez au moins cinq fruits et légumes par jour. www.mangerbouger.fr</span>
  </div>
</footer>
	<?php
	return (string) ob_get_clean();
}

/*
 * Tiroir et barre d'onglets en fin de page, une seule fois.
 * Filtre bigm_child_onglets : false pour masquer la barre sur une page.
 */
add_action(
	'wp_footer',
	static function () {
		echo bigm_child_tiroir_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( apply_filters( 'bigm_child_onglets', true ) ) {
			echo bigm_child_onglets_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	},
	5
);

/*
 * Styles et script de l'en-tête, du pied de page et de la barre d'onglets : sur toutes les pages.
 * Le style du header et du footer de Hello Elementor n'est plus utile.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_dequeue_style( 'hello-elementor-header-footer' );
		foreach ( array( 'header', 'footer', 'tabbar' ) as $nom ) {
			wp_enqueue_style( "bigm-$nom", bigm_child_asset_url( "assets/css/$nom.css" ), array( 'bigm-base' ), bigm_child_asset_version( "assets/css/$nom.css" ) );
		}
		wp_enqueue_script(
			'bigm-header',
			bigm_child_asset_url( 'assets/js/header.js' ),
			array(),
			bigm_child_asset_version( 'assets/js/header.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	},
	21
);
