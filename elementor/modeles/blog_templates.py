"""Blog Big M : modèles du Theme Builder d'Elementor Pro pour la page Actualités et les articles.

- blog-actualites.json (type « archive ») : en-tête, filtres par catégorie, article à la une,
  cartes des articles, pagination. Conditions : Archives → Toutes les archives (la page des
  articles /actualites/ et les pages de catégorie).
- blog-article.json (type « single-post ») : retour à la liste, catégorie et date, titre, image,
  texte de l'article, partage, « À lire aussi ». Condition : Articles → Tous les articles.

Les cartes reprennent celles de l'accueil (widget « Big M — Actualités ») : cartes blanches
arrondies, image 16:9, titre en Fredoka, avec en plus la catégorie en pastille et un extrait.

Usage : python3 -I blog_templates.py <dossier de sortie>
"""
import os
import random
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from bigm_elementor import *  # noqa: E402,F401,F403  (briques communes)

random.seed(20261107)  # identifiants stables d'une génération à l'autre
OUT = sys.argv[1] if len(sys.argv) > 1 else '.'
LINE = '#2B140614'  # filet brun très léger, comme --line de bigm-child


# ── Cartes d'articles (widgets « Archive d'articles » et « Articles », habillage Cartes) ──
def cards(skin, columns=3, excerpt=True, title_size=22):
    """Réglages de l'habillage Cartes ; `skin` : archive_cards (archive) ou cards (widget Articles)."""
    s = {
        '_skin': skin, 'columns': '3', 'columns_tablet': '2', 'columns_mobile': '1',
        'thumbnail': 'top', 'thumbnail_size_size': 'medium_large', 'item_ratio': px(0.5625),
        'show_title': 'yes', 'title_tag': 'h2' if skin == 'archive_cards' else 'h3',
        'show_excerpt': 'yes' if excerpt else '', 'excerpt_length': 22, 'apply_to_custom_excerpt': 'yes',
        'show_read_more': 'yes' if excerpt else '', 'read_more_text': 'Lire l’article', 'open_new_tab': 'no',
        'meta_data': ['date'], 'meta_separator': '•',
        'show_badge': 'yes', 'badge_taxonomy': 'category', 'show_avatar': '',
        # Mise en page
        'column_gap': px(24), 'row_gap': px(24), 'row_gap_mobile': px(16), 'alignment': 'left',
        # Carte
        'card_bg_color': CARD, 'card_border_color': CARD, 'card_border_width': px(0), 'card_border_radius': px(20),
        'card_padding': px(22), 'box_shadow_box_shadow_type': '', 'hover_effect': 'none',
        'meta_border_color': LINE,
        # Pastille de catégorie
        'badge_position': 'left', 'badge_bg_color': BTN, 'badge_color': '#FFFFFF', 'badge_radius': px(999),
        'badge_size': px(13), 'badge_margin': px(16),
        **font('badge_typography', 'Fredoka', 600, 13, badge_typography_text_transform='none', badge_typography_letter_spacing=px(0.2)),
        # Textes
        'title_color': INK, 'title_spacing': px(10),
        **font('title_typography', 'Fredoka', 600, title_size, 20, 1.2),
        'meta_color': MUTED, **font('meta_typography', 'Figtree', 400, 14),
        'excerpt_color': MUTED, 'excerpt_spacing': px(14), **font('excerpt_typography', 'Figtree', 400, 16, 15, 1.5),
        'read_more_color': BTN, **font('read_more_typography', 'Fredoka', 600, 15, read_more_typography_text_transform='none'),
        '__globals__': {},
    }
    # Les noms des réglages d'un habillage commencent par son identifiant (archive_cards_columns…).
    out = {k if k in ('_skin', '__globals__') else f'{skin}_{k}': v for k, v in s.items()}
    out['__globals__'] = {f'{skin}_{k}': f'globals/colors?id={g}' for k, g in
                          [('title_color', 'text'), ('meta_color', 'secondary'), ('excerpt_color', 'secondary'),
                           ('read_more_color', 'accent'), ('badge_bg_color', 'accent'), ('card_bg_color', 'bigmcard')]}
    return out


# Survol et image des cartes, comme les cartes de l'accueil (.news-card).
CARDS_CSS = """selector .elementor-post__card { overflow: hidden; transition: transform .35s cubic-bezier(.34, 1.56, .64, 1); }
selector .elementor-post:hover .elementor-post__card { transform: translateY(-4px); }
selector .elementor-post__thumbnail img { width: 100%; height: 100%; object-fit: cover; top: 0; left: 0; transform: none; }
selector .elementor-post__thumbnail__link { margin-bottom: 0; }
selector .elementor-post__title a:hover { color: var(--e-global-color-accent, #C8400A); }
selector .elementor-post__read-more::after { content: " →"; }
selector .elementor-post__meta-data { border-top-width: 1px; }"""

# Article à la une : la 1re carte de la 1re page prend toute la largeur (tablette et ordinateur), image à gauche sur ordinateur.
FEATURED_CSS = """@media (min-width: 768px) {
  body:not(.paged) selector .elementor-posts-container > .elementor-post:first-child { grid-column: 1 / -1; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__title,
  body:not(.paged) selector .elementor-post:first-child .elementor-post__title a { font-size: 28px; line-height: 1.15; }
}
@media (min-width: 1025px) {
  body:not(.paged) selector .elementor-post:first-child .elementor-post__card { display: grid; grid-template-columns: minmax(0, 7fr) minmax(0, 5fr); grid-template-rows: 1fr auto; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__thumbnail__link { grid-column: 1; grid-row: 1 / 3; width: 100%; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__thumbnail { padding-bottom: 0; height: 100%; min-height: 380px; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__text { grid-column: 2; grid-row: 1; align-self: center; padding: 36px 40px 16px; margin: 0; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__meta-data { grid-column: 2; grid-row: 2; padding: 16px 40px; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__title,
  body:not(.paged) selector .elementor-post:first-child .elementor-post__title a { font-size: 34px; line-height: 1.12; }
  body:not(.paged) selector .elementor-post:first-child .elementor-post__excerpt p { font-size: 17px; }
}"""

# Pagination en pastilles, comme les boutons du site.
PAGINATION_CSS = """selector .elementor-pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 40px; }
selector .elementor-pagination .page-numbers { display: inline-flex; align-items: center; justify-content: center; min-width: 44px; height: 44px; margin: 0; padding: 0 16px; border-radius: 12px; background: #FFFFFF; color: var(--e-global-color-text, #2B1406); font-family: 'Fredoka', sans-serif; font-weight: 600; font-size: 16px; text-decoration: none; }
selector .elementor-pagination a.page-numbers:hover { background: var(--e-global-color-bigmtile, #F2EDE7); }
selector .elementor-pagination .page-numbers.current { background: var(--e-global-color-accent, #C8400A); color: #FFFFFF; }
selector .elementor-pagination .page-numbers.dots { background: transparent; min-width: 0; padding: 0 4px; }
selector .elementor-pagination span.prev, selector .elementor-pagination span.next { opacity: .45; }"""

# Pastilles des catégories (widget WordPress « Catégories ») : la catégorie affichée est en orange.
PILLS_CSS = """selector h5 { display: none; }
selector ul { display: flex; flex-wrap: wrap; gap: 8px; margin: 0; padding: 0; list-style: none; }
selector li { margin: 0; padding: 0; }
selector li a { display: inline-flex; align-items: center; height: 40px; padding: 0 18px; border-radius: 999px; background: #FFFFFF; color: var(--e-global-color-text, #2B1406); font-family: 'Fredoka', sans-serif; font-weight: 600; font-size: 15px; text-decoration: none; transition: background .2s; }
selector li a:hover { background: var(--e-global-color-bigmtile, #F2EDE7); }
selector li.current-cat a { background: var(--e-global-color-accent, #C8400A); color: #FFFFFF; }"""

# « Tout » : en orange sur la page des articles, blanc ailleurs (pages de catégorie).
TOUT_CSS = """selector .elementor-button { height: 40px; padding: 0 18px; border-radius: 999px; }
body.blog selector .elementor-button { background: var(--e-global-color-accent, #C8400A); color: #FFFFFF; }"""


def pill_tout():
    s = {'text': 'Tout', 'link': {'url': '/actualites/', 'is_external': '', 'nofollow': '', 'custom_attributes': ''}, 'size': 'sm',
         'background_background': 'classic', 'background_color': '#FFFFFF', 'button_text_color': INK,
         'button_background_hover_background': 'classic', 'button_background_hover_color': TILE, 'hover_color': INK,
         'border_radius': box(999), 'text_padding': box(0, 18),
         '__globals__': glob(button_text_color='text', hover_color='text', button_background_hover_color='bigmtile'),
         'custom_css': TOUT_CSS}
    s.update(font('typography', 'Fredoka', 600, 15))
    return widget('button', s)


def categories():
    return widget('wp-widget-categories', {'wp': {'title': '', 'count': '', 'hierarchical': '', 'dropdown': ''},
                                           'custom_css': PILLS_CSS, '_flex_size': 'none'})


# ── 1. Page Actualités (archive) ───────────────────────────────────────
titre_blog = heading('Actualités et presse', tag='h1', size=56, size_mobile=38)
titre_blog['settings']['custom_css'] = 'body:not(.blog) selector { display: none; }'
# Sur une page de catégorie : le nom de la catégorie (sans « Catégorie : »).
titre_categorie = widget('theme-archive-title', {
    'title': '', 'header_size': 'h1', 'title_color': INK, '__globals__': glob(title_color='primary'),
    '__dynamic__': {'title': dynamic('archive-title', include_context='no')},
    **font('typography', 'Fredoka', 600, 56, 38, 1.05, typography_letter_spacing=px(-0.2)),
    'custom_css': 'body.blog selector { display: none; }'})

entete = section([
    container([
        eyebrow('Le blog Big M'),
        titre_blog, titre_categorie,
        text('<p>Nouveautés, ouvertures de restaurants, partenariats et articles de presse : toute l’actualité de Big M.</p>', size=19),
        container([pill_tout(), categories()], flex_direction='row', flex_wrap='wrap', flex_gap=gap(8),
                  flex_align_items='center', padding=box(12, 0, 0, 0)),
    ], flex_gap=gap(12), width=px(100, '%')),
], padding=(48, 32))

liste = widget('archive-posts', {
    **cards('archive_cards'),
    'pagination_type': 'numbers_and_prev_next', 'pagination_page_limit': '', 'pagination_numbers_shorten': 'yes',
    'pagination_prev_label': '← Précédent', 'pagination_next_label': 'Suivant →', 'pagination_align': 'center',
    'pagination_spacing': px(0), 'pagination_spacing_top': px(40),
    'nothing_found_message': 'Aucun article pour l’instant. Revenez bientôt !',
    'custom_css': '\n'.join([CARDS_CSS, FEATURED_CSS, PAGINATION_CSS]),
})

articles = section([liste], padding=(8, 8))
articles['settings'].update({'padding': box(8, 32, 80, 32), 'padding_mobile': box(8, 16, 56, 16)})

# ── 2. Article (single-post) ───────────────────────────────────────────
retour = button('Toutes les actualités', '/actualites/', 'link', icon='fas fa-arrow-left')
retour['settings']['icon_align'] = 'row'  # flèche avant le texte

infos = widget('post-info', {
    'view': 'inline',
    'icon_list': [
        {'_id': eid(), 'type': 'terms', 'taxonomy': 'category', 'link': 'yes', 'show_icon': 'none'},
        {'_id': eid(), 'type': 'date', 'date_format': 'custom', 'custom_date_format': 'j F Y', 'link': '', 'show_icon': 'none'},
    ],
    'space_between': px(0), 'text_indent': px(0), 'text_color': MUTED, '__globals__': glob(text_color='secondary'),
    **font('icon_typography', 'Figtree', 400, 15),
    'custom_css': """selector .elementor-icon-list-item + .elementor-icon-list-item::before { content: "·"; margin: 0 8px; color: var(--e-global-color-secondary, #6B4A3A); }
selector .elementor-post-info__terms-list-item { color: var(--e-global-color-accent, #C8400A); font-weight: 600; text-decoration: none; }
selector .elementor-post-info__terms-list-item:hover { color: var(--e-global-color-bigmbtnhover, #A93506); }""",
})

titre_article = widget('theme-post-title', {
    'title': '', 'header_size': 'h1', 'title_color': INK, '__globals__': glob(title_color='primary'),
    '__dynamic__': {'title': dynamic('post-title')},
    **font('typography', 'Fredoka', 600, 48, 34, 1.08, typography_letter_spacing=px(-0.2)),
})

image_une = widget('theme-post-featured-image', {
    'image': {'id': '', 'url': ''}, '__dynamic__': {'image': dynamic('post-featured-image')},
    'image_size': 'large', 'width': px(100, '%'), 'image_border_radius': box(20),
    'custom_css': 'selector img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; }',
})

contenu = widget('theme-post-content', {
    'align': 'left', 'text_color': INK, '__globals__': glob(text_color='text'),
    **font('typography', 'Figtree', 400, 18, 17, 1.7),
    'custom_css': """selector h2, selector h3, selector h4 { font-family: 'Fredoka', sans-serif; font-weight: 600; color: var(--e-global-color-primary, #2B1406); line-height: 1.15; margin: 1.6em 0 .5em; }
selector h2 { font-size: 32px; } selector h3 { font-size: 25px; } selector h4 { font-size: 20px; }
selector p { margin: 0 0 1.1em; }
selector a { color: var(--e-global-color-accent, #C8400A); text-decoration: underline; text-underline-offset: 3px; }
selector a:hover { color: var(--e-global-color-bigmbtnhover, #A93506); }
selector ul, selector ol { margin: 0 0 1.1em; padding-left: 1.3em; }
selector li { margin-bottom: .4em; }
selector blockquote { margin: 1.6em 0; padding: 4px 0 4px 22px; border-left: 4px solid var(--e-global-color-bigmorange, #FA5C19); font-family: 'Fredoka', sans-serif; font-weight: 500; font-size: 22px; line-height: 1.35; color: var(--e-global-color-primary, #2B1406); }
selector img { border-radius: 12px; height: auto; }
selector figure { margin: 1.6em 0; }
selector figcaption { margin-top: 8px; font-size: 14px; color: var(--e-global-color-secondary, #6B4A3A); text-align: center; }
selector iframe { width: 100%; aspect-ratio: 16 / 9; height: auto; border-radius: 12px; }
@media (max-width: 767px) { selector h2 { font-size: 26px; } selector h3 { font-size: 21px; } selector blockquote { font-size: 19px; } }""",
})

partage = container([
    text('<p><strong>Partager cet article</strong></p>', size=16, color=INK, color_global='text'),
    widget('share-buttons', {
        'share_buttons': [{'_id': eid(), 'button': b} for b in ('facebook', 'x-twitter', 'linkedin', 'whatsapp', 'email')],
        'view': 'icon', 'skin': 'flat', 'shape': 'circle', 'columns': '0', 'alignment': 'left',
        'share_url_type': 'current_page', 'column_gap': px(8), 'row_gap': px(8), 'button_size': px(1.4),
        'icon_size': px(1.4, 'em'), 'button_height': px(3, 'em'),
        'color_source': 'custom', 'primary_color': TILE, 'secondary_color': INK,
        'primary_color_hover': BTN, 'secondary_color_hover': '#FFFFFF',
        '__globals__': glob(primary_color='bigmtile', secondary_color='text', primary_color_hover='accent'),
    }),
], flex_direction='row', flex_wrap='wrap', flex_align_items='center', flex_gap=gap(16),
    padding=box(24, 0, 0, 0), border_border='solid', border_width=box(1, 0, 0, 0), border_color=LINE)

article = section([
    container([
        retour, infos, titre_article, image_une, contenu, partage,
    ], flex_gap=gap(20), width=px(800), width_tablet=px(100, '%')),
], padding=(40, 24), flex_align_items='center')

similaires = widget('posts', {
    **cards('cards', excerpt=False, title_size=20),
    'cards_posts_per_page': 3,
    'posts_post_type': 'related', 'posts_include': ['terms'], 'posts_related_taxonomies': ['category'],
    'posts_exclude': ['current_post'], 'posts_related_fallback': 'fallback_recent',
    'posts_orderby': 'post_date', 'posts_order': 'desc', 'pagination_type': '',
    'custom_css': CARDS_CSS,
})

a_lire = section([
    section_head('Le blog Big M', 'À lire aussi'),
    similaires,
], background=TILE, globals_bg='bigmtile', padding=(64, 48))

write_theme_template([entete, articles], os.path.join(OUT, 'blog-actualites.json'), 'Big M — Actualités (blog)', 'archive')
write_theme_template([article, a_lire], os.path.join(OUT, 'blog-article.json'), 'Big M — Article', 'single-post')
