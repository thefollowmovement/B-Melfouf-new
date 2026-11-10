<?php
/**
 * Galerie commune des restaurants : une seule liste de photos, réglée dans Restaurants → Galerie
 * commune, affichée sur toutes les fiches à la place de la galerie de chaque restaurant.
 *
 * StoreLocatore n'est pas modifié : il lit la galerie d'une fiche par
 * get_post_meta( $id, 'tfmsl_gallery', true ) (gabarit, widget Elementor, API, score de
 * complétude), et cette lecture passe par le filtre get_post_metadata. Galerie commune vide :
 * chaque restaurant garde la sienne, qui n'est jamais effacée. Dans la fiche d'un restaurant, le
 * champ « Galerie » du plugin est remplacé par un renvoi vers la page de réglage.
 *
 * @package BigM\Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Photos de la galerie commune (identifiants d'images, dans l'ordre), ou liste vide.
 *
 * @return int[]
 */
function bigm_galerie_commune(): array {
	static $cache = null;
	static $brut  = null;
	$option = get_option( 'bigm_galerie_restaurants', array() );
	if ( null === $cache || $brut !== $option ) {
		$brut  = $option;
		$cache = array_values( array_filter( array_map( 'absint', (array) $option ), static fn( int $id ): bool => $id > 0 && wp_attachment_is_image( $id ) ) );
	}
	return $cache;
}

/**
 * Galerie propre d'un restaurant (celle de sa fiche, sans la galerie commune).
 *
 * @param int $id Restaurant (tfmsl_store).
 * @return int[]
 */
function bigm_galerie_du_restaurant( int $id ): array {
	$GLOBALS['bigm_galerie_brute'] = true;
	$ids                           = get_post_meta( $id, 'tfmsl_gallery', true );
	unset( $GLOBALS['bigm_galerie_brute'] );
	return array_values( array_filter( array_map( 'absint', is_array( $ids ) ? $ids : array() ) ) );
}

/*
 * La galerie de chaque fiche est la galerie commune, quand elle n'est pas vide.
 */
add_filter(
	'get_post_metadata',
	static function ( $valeur, $objet_id, $cle, $single ) {
		if ( 'tfmsl_gallery' !== $cle || ! empty( $GLOBALS['bigm_galerie_brute'] ) || 'tfmsl_store' !== get_post_type( (int) $objet_id ) ) {
			return $valeur;
		}
		$ids = bigm_galerie_commune();
		if ( ! $ids ) {
			return $valeur;
		}
		// get_metadata() renvoie le premier élément si $single, sinon la liste des valeurs.
		return array( $ids );
	},
	10,
	4
);

/**
 * Réglage de la galerie commune (page de réglage et API REST : /wp/v2/settings).
 */
function bigm_galerie_reglage(): void {
	register_setting(
		'bigm_galerie',
		'bigm_galerie_restaurants',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => static fn( $v ): array => array_values( array_unique( array_filter( array_map( 'absint', is_array( $v ) ? $v : explode( ',', (string) $v ) ), static fn( int $id ): bool => $id > 0 && wp_attachment_is_image( $id ) ) ) ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
			),
		)
	);
}
add_action( 'admin_init', 'bigm_galerie_reglage' );
add_action( 'rest_api_init', 'bigm_galerie_reglage' );

/*
 * Pages en cache : vidées quand la galerie commune change.
 */
add_action( 'add_option_bigm_galerie_restaurants', 'bigm_vider_cache' );
add_action( 'update_option_bigm_galerie_restaurants', 'bigm_vider_cache' );

add_action(
	'admin_menu',
	static function () {
		if ( post_type_exists( 'tfmsl_store' ) ) {
			add_submenu_page( 'edit.php?post_type=tfmsl_store', 'Galerie commune', 'Galerie commune', 'manage_options', 'bigm-galerie', 'bigm_galerie_page' );
		}
	},
	20
);

/**
 * Page « Galerie commune ».
 */
function bigm_galerie_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ids     = bigm_galerie_commune();
	$restos  = get_posts(
		array(
			'post_type'      => 'tfmsl_store',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	$sources = array();
	foreach ( $restos as $r ) {
		$propres = array_values( array_filter( bigm_galerie_du_restaurant( (int) $r ), 'wp_attachment_is_image' ) );
		if ( $propres ) {
			$sources[ (int) $r ] = $propres;
		}
	}
	$vignette = static function ( int $id ): string {
		$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		return '<li class="bigm-galerie__photo" data-id="' . $id . '">' . wp_get_attachment_image( $id, 'thumbnail', false, array( 'alt' => '' ) )
			. ( '' === $alt ? '<a class="bigm-galerie__alt" href="' . esc_url( get_edit_post_link( $id ) ) . '" title="Ajouter un texte de remplacement (lecteurs d’écran, Google)">sans texte alt.</a>' : '' )
			. '<button type="button" class="bigm-galerie__retirer" aria-label="Retirer cette photo">×</button></li>';
	};
	$donnees = array();
	foreach ( $sources as $r => $propres ) {
		$donnees[ $r ] = array_map(
			static fn( int $id ): array => array(
				'id'  => $id,
				'url' => (string) wp_get_attachment_image_url( $id, 'thumbnail' ),
				'alt' => '' !== trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			),
			$propres
		);
	}
	?>
	<div class="wrap">
		<h1>Galerie commune des restaurants</h1>
		<p style="max-width:70ch">Ces photos s’affichent dans la section « Galerie » de <strong>toutes les fiches restaurants</strong> (<?php echo (int) count( $restos ); ?>), à la place de la galerie de chaque fiche. Une seule liste à tenir à jour, et les mêmes fichiers partout : le navigateur des visiteurs les garde d’une fiche à l’autre. Glisser les photos pour changer l’ordre. Sans photo ici, chaque restaurant garde sa propre galerie (elle n’est jamais effacée).</p>
		<?php settings_errors(); ?>
		<form method="post" action="options.php" class="bigm-galerie" data-bigm-galerie>
			<?php settings_fields( 'bigm_galerie' ); ?>
			<input type="hidden" name="bigm_galerie_restaurants" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-galerie-ids>
			<ul class="bigm-galerie__liste" data-galerie-liste>
				<?php
				foreach ( $ids as $id ) {
					echo $vignette( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image et attributs échappés.
				}
				?>
			</ul>
			<p class="bigm-galerie__vide" data-galerie-vide<?php echo $ids ? ' hidden' : ''; ?>><em>Pas encore de photo : les fiches gardent leur propre galerie.</em></p>
			<p>
				<button type="button" class="button button-secondary" data-galerie-ajouter>Ajouter des photos</button>
				<span data-galerie-compte style="margin-left:8px"></span>
			</p>
			<?php if ( $sources ) : ?>
				<p>
					<label for="bigm-galerie-source">Ou reprendre la galerie d’un restaurant :</label>
					<select id="bigm-galerie-source" data-galerie-source>
						<?php foreach ( $sources as $r => $propres ) : ?>
							<option value="<?php echo (int) $r; ?>"><?php echo esc_html( get_the_title( $r ) . ' (' . count( $propres ) . ' photo' . ( count( $propres ) > 1 ? 's' : '' ) . ')' ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button" data-galerie-reprendre>Reprendre</button>
				</p>
			<?php endif; ?>
			<?php submit_button( 'Enregistrer la galerie' ); ?>
		</form>
	</div>
	<style>
		.bigm-galerie__liste { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0; }
		.bigm-galerie__photo { position: relative; width: 150px; height: 150px; margin: 0; border-radius: 8px; overflow: hidden; background: #f0f0f1; cursor: move; box-shadow: 0 0 0 1px #dcdcde; }
		.bigm-galerie__photo img { display: block; width: 100%; height: 100%; object-fit: cover; }
		.bigm-galerie__retirer { position: absolute; top: 6px; right: 6px; width: 28px; height: 28px; border: 0; border-radius: 50%; background: rgba(0,0,0,.65); color: #fff; font-size: 18px; line-height: 1; cursor: pointer; }
		.bigm-galerie__alt { position: absolute; left: 6px; bottom: 6px; padding: 2px 6px; border-radius: 4px; background: #d63638; color: #fff; font-size: 11px; text-decoration: none; }
		.bigm-galerie__photo.ui-sortable-placeholder { visibility: visible !important; background: #e5f0fa; box-shadow: inset 0 0 0 2px #2271b1; }
	</style>
	<script>
	// Au chargement complet : jQuery UI (glisser) et la médiathèque sont chargés en pied de page.
	jQuery(($) => {
		const form = $('[data-bigm-galerie]');
		const liste = form.find('[data-galerie-liste]');
		const champ = form.find('[data-galerie-ids]');
		const sources = <?php echo wp_json_encode( $donnees ); ?>;
		const vignette = (p) => {
			const li = $('<li class="bigm-galerie__photo">').attr('data-id', p.id).append($('<img alt="">').attr('src', p.url));
			if (!p.alt) li.append($('<span class="bigm-galerie__alt">').text('sans texte alt.'));
			return li.append('<button type="button" class="bigm-galerie__retirer" aria-label="Retirer cette photo">×</button>');
		};
		const maj = () => {
			const ids = liste.children().map((i, li) => $(li).data('id')).get();
			champ.val(ids.join(','));
			form.find('[data-galerie-vide]').prop('hidden', ids.length > 0);
			form.find('[data-galerie-compte]').text(ids.length ? ids.length + ' photo' + (ids.length > 1 ? 's' : '') : '');
		};
		liste.sortable({ items: '> li', tolerance: 'pointer', update: maj });
		liste.on('click', '.bigm-galerie__retirer', function () { $(this).closest('li').remove(); maj(); });
		form.find('[data-galerie-ajouter]').on('click', () => {
			const frame = wp.media({ title: 'Photos de la galerie commune', button: { text: 'Ajouter à la galerie' }, library: { type: 'image' }, multiple: 'add' });
			frame.on('select', () => {
				const deja = champ.val().split(',').map(Number);
				frame.state().get('selection').toJSON().forEach((a) => {
					if (deja.includes(a.id)) return;
					liste.append(vignette({ id: a.id, url: a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url, alt: !!a.alt }));
				});
				maj();
			});
			frame.open();
		});
		form.find('[data-galerie-reprendre]').on('click', () => {
			const photos = sources[form.find('[data-galerie-source]').val()] || [];
			if (liste.children().length && !window.confirm('Remplacer les photos de la galerie commune par celles de ce restaurant ?')) return;
			liste.empty();
			photos.forEach((p) => liste.append(vignette(p)));
			maj();
		});
		maj();
	});
	</script>
	<?php
}

/*
 * Fiche d'un restaurant dans l'admin : le champ « Galerie » de StoreLocatore renvoie à la galerie
 * commune (et n'est plus envoyé, pour que la galerie propre reste telle quelle).
 */
add_action( 'admin_footer-post.php', 'bigm_galerie_renvoi_fiche' );
add_action( 'admin_footer-post-new.php', 'bigm_galerie_renvoi_fiche' );

/**
 * Remplace le champ « Galerie » de la fiche d'un restaurant par un renvoi, si la galerie commune
 * a des photos.
 */
function bigm_galerie_renvoi_fiche(): void {
	$ecran = get_current_screen();
	if ( ! $ecran || 'tfmsl_store' !== $ecran->post_type || ! bigm_galerie_commune() ) {
		return;
	}
	$lien = admin_url( 'edit.php?post_type=tfmsl_store&page=bigm-galerie' );
	$n    = count( bigm_galerie_commune() );
	?>
	<script>
	(() => {
		const bouton = document.getElementById('tfmsl-gallery-btn');
		const ligne = bouton && bouton.closest('.tfmsl-field-row');
		if (!ligne) return;
		const champ = document.getElementById('tfmsl_gallery_ids');
		if (champ) champ.disabled = true;
		ligne.querySelectorAll('#tfmsl-gallery-preview, #tfmsl-gallery-btn').forEach((el) => { el.hidden = true; el.style.display = 'none'; });
		const note = document.createElement('p');
		note.className = 'description';
		note.innerHTML = <?php echo wp_json_encode( 'Galerie commune à tous les restaurants (' . $n . ' photo' . ( $n > 1 ? 's' : '' ) . ') : <a href="' . esc_url( $lien ) . '">la modifier dans Restaurants → Galerie commune</a>.' ); ?>;
		ligne.appendChild(note);
	})();
	</script>
	<?php
}
