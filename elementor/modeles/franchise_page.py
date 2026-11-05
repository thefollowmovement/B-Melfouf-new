"""Construit la page « Devenir franchisé » Big M au format Elementor.

Contenu repris de https://bigm-burger.fr/devenir-franchise/ (orthographe corrigée),
mise en forme de la charte Big M (kit Elementor de bigm-child : couleurs globales,
Fredoka / Figtree, cartes blanches arrondies sur fond crème, boutons arrondis).

Usage : python3 -I franchise_page.py <elements.json> <modele.json> [section.json]
  - elements.json : contenu à mettre dans _elementor_data (page de test locale) ;
  - modele.json   : modèle de la page entière à importer dans Elementor (Modèles → Importer) ;
  - section.json  : la seule section « Candidature », pour la remplacer sur une page déjà publiée.
"""
import json
import random
import sys

random.seed(20261105)  # identifiants stables d'une génération à l'autre

PREPROD = 'https://preprod.bigm-burger.fr/wp-content/uploads/2026/11/'
# Images de la médiathèque de preprod. À l'import, Elementor les télécharge depuis ces
# adresses et en crée une copie dans la médiathèque (le widget Image exige une adresse).
IMG_HERO = {'id': 45, 'url': PREPROD + 'b-franchise-poignee.jpg', 'alt': 'Deux associés se serrent la main dans un restaurant Big M', 'source': 'library'}
IMG_SPORT = {'id': 46, 'url': PREPROD + 'b-mhsc-maillot.jpg', 'alt': 'Le logo Big M sur le maillot du MHSC', 'source': 'library'}
IMG_RESTO = {'id': 50, 'url': PREPROD + 'b-restaurant-saint-ouen.jpg', 'alt': 'Le comptoir du Big M de Saint-Ouen', 'source': 'library'}

INK, MUTED, BTN, BTN_HOVER = '#2B1406', '#6B4A3A', '#C8400A', '#A93506'
ORANGE, TILE, CARD, FOOTER_INK = '#FA5C19', '#F2EDE7', '#FFFFFF', '#F6E4D6'


def eid():
    return '%07x' % random.getrandbits(28)


def px(v, unit='px'):
    return {'unit': unit, 'size': v, 'sizes': []}


def box(t, r=None, b=None, l=None, unit='px'):
    r = t if r is None else r
    b = t if b is None else b
    l = r if l is None else l
    return {'unit': unit, 'top': str(t), 'right': str(r), 'bottom': str(b), 'left': str(l), 'isLinked': t == r == b == l}


def gap(v):
    return {'column': str(v), 'row': str(v), 'isLinked': True, 'unit': 'px', 'size': v}


def glob(**kw):
    """__globals__ : couleur globale du kit (id Elementor)."""
    return {k: f'globals/colors?id={v}' for k, v in kw.items()}


def font(prefix, family, weight, size, size_mobile=None, line_height=None, **extra):
    s = {f'{prefix}_typography': 'custom', f'{prefix}_font_family': family, f'{prefix}_font_weight': str(weight), f'{prefix}_font_size': px(size)}
    if size_mobile:
        s[f'{prefix}_font_size_mobile'] = px(size_mobile)
    if line_height:
        s[f'{prefix}_line_height'] = px(line_height, 'em')
    s.update(extra)
    return s


def container(children, **settings):
    s = {'content_width': 'full', 'flex_direction': 'column', 'flex_gap': gap(16)}
    s.update(settings)
    return {'id': eid(), 'elType': 'container', 'isInner': False, 'settings': s, 'elements': children}


def section(children, background=None, globals_bg=None, anchor=None, padding=(72, 48), **settings):
    """Section pleine largeur au contenu centré sur 1240 px, comme .section de bigm-child."""
    s = {'content_width': 'boxed', 'flex_direction': 'column', 'flex_gap': gap(32),
         'padding': box(padding[0], 32, padding[0], 32), 'padding_mobile': box(padding[1], 16, padding[1], 16)}
    if background:
        s.update({'background_background': 'classic', 'background_color': background})
        if globals_bg:
            s['__globals__'] = glob(background_color=globals_bg)
    if anchor:
        s['_element_id'] = anchor
    s.update(settings)
    return container(children, **s)


def card(children, bg=CARD, bg_global='bigmcard', pad=28, **settings):
    s = {'background_background': 'classic', 'background_color': bg, '__globals__': glob(background_color=bg_global),
         'border_radius': box(20), 'padding': box(pad), 'padding_mobile': box(22, 20), 'flex_gap': gap(14)}
    s.update(settings)
    return container(children, **s)


def heading(text, tag='h2', size=44, size_mobile=30, color=INK, color_global='primary', align=None, **extra):
    s = {'title': text, 'header_size': tag, 'title_color': color, '__globals__': glob(title_color=color_global)}
    s.update(font('typography', 'Fredoka', 600, size, size_mobile, 1.05, typography_letter_spacing=px(-0.2)))
    if align:
        s['align'] = align
    s.update(extra)
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'heading', 'settings': s, 'elements': []}


def eyebrow(text, color=BTN, color_global='accent', align=None):
    s = {'title': text, 'header_size': 'p', 'title_color': color, '__globals__': glob(title_color=color_global)}
    s.update(font('typography', 'Fredoka', 600, 15, 14, typography_text_transform='uppercase', typography_letter_spacing=px(1)))
    if align:
        s['align'] = align
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'heading', 'settings': s, 'elements': []}


def text(html, size=17, color=MUTED, color_global='secondary', align=None, weight=400):
    s = {'editor': html, 'text_color': color, '__globals__': glob(text_color=color_global)}
    s.update(font('typography', 'Figtree', weight, size, min(size, 17), 1.5))
    if align:
        s['align'] = align
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'text-editor', 'settings': s, 'elements': []}


def button(label, url, style='primary', icon=None):
    s = {'text': label, 'link': {'url': url, 'is_external': '', 'nofollow': '', 'custom_attributes': ''}, 'size': 'md',
         'border_radius': box(12), 'text_padding': box(14, 24), 'button_hover_transition_duration': px(0.2, 's')}
    s.update(font('typography', 'Fredoka', 600, 17, 16))
    if style == 'primary':
        s.update({'background_background': 'classic', 'background_color': BTN, 'button_text_color': '#FFFFFF',
                  'button_background_hover_background': 'classic', 'button_background_hover_color': BTN_HOVER, 'hover_color': '#FFFFFF',
                  '__globals__': glob(background_color='accent', button_background_hover_color='bigmbtnhover')})
    elif style == 'light':  # .btn--light : fond blanc, texte brun
        s.update({'background_background': 'classic', 'background_color': '#FFFFFF', 'button_text_color': INK,
                  'border_border': 'solid', 'border_width': box(2), 'border_color': '#FFFFFF', 'button_hover_border_color': TILE,
                  'button_background_hover_background': 'classic', 'button_background_hover_color': TILE, 'hover_color': INK,
                  '__globals__': glob(button_text_color='primary', hover_color='primary', button_background_hover_color='bigmtile')})
    elif style == 'outline-light':  # contour blanc sur aplat orange
        s.update({'background_background': 'classic', 'background_color': '#FFFFFF00', 'button_text_color': '#FFFFFF',
                  'border_border': 'solid', 'border_width': box(2), 'border_color': '#FFFFFF',
                  'button_background_hover_background': 'classic', 'button_background_hover_color': '#FFFFFF', 'hover_color': INK,
                  '__globals__': glob(hover_color='primary')})
    elif style == 'link':  # .link : texte orange bouton, sans fond
        s.update({'background_background': 'classic', 'background_color': '#FFFFFF00', 'button_text_color': BTN, 'text_padding': box(0),
                  'button_background_hover_background': 'classic', 'button_background_hover_color': '#FFFFFF00', 'hover_color': BTN_HOVER,
                  '__globals__': glob(button_text_color='accent', hover_color='bigmbtnhover')})
    if icon:
        s.update({'selected_icon': {'value': icon, 'library': 'fa-solid'}, 'icon_align': 'row-reverse' if style == 'link' else 'row', 'icon_indent': px(8)})
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'button', 'settings': s, 'elements': []}


def icon(name, size=28):
    s = {'selected_icon': {'value': name, 'library': 'fa-solid'}, 'view': 'stacked', 'shape': 'square',
         'primary_color': TILE, 'secondary_color': BTN, 'size': px(size), 'icon_padding': px(14), 'border_radius': box(12), 'align': 'left',
         '__globals__': glob(primary_color='bigmtile', secondary_color='accent')}
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'icon', 'settings': s, 'elements': []}


def checklist(items):
    s = {'icon_list': [{'_id': eid(), 'text': t, 'selected_icon': {'value': 'fas fa-check-circle', 'library': 'fa-solid'}} for t in items],
         'icon_color': BTN, 'text_color': INK, 'icon_size': px(20), 'space_between': px(14), 'text_indent': px(10),
         '__globals__': glob(icon_color='accent', text_color='text')}
    s.update(font('icon_typography', 'Figtree', 400, 17, 16, 1.4))
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'icon-list', 'settings': s, 'elements': []}


def image(img, radius=28, height=None):
    s = {'image': img, 'image_size': 'large', 'image_border_radius': box(radius), 'width': px(100, '%')}
    if height:
        s.update({'height': px(height), 'height_mobile': px(260), 'object-fit': 'cover'})
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'image', 'settings': s, 'elements': []}


def row(children, cols=2, gap_px=24, align='stretch', mobile_cols=1):
    """Grille responsive : `cols` colonnes, une seule sur mobile."""
    return container(children, container_type='grid',
                     grid_columns_grid={'unit': 'fr', 'size': cols, 'sizes': []},
                     grid_columns_grid_tablet={'unit': 'fr', 'size': min(cols, 2), 'sizes': []},
                     grid_columns_grid_mobile={'unit': 'fr', 'size': mobile_cols, 'sizes': []},
                     grid_gaps_mobile=gap(12 if mobile_cols > 1 else gap_px),
                     grid_rows_grid={'unit': 'fr', 'size': 1, 'sizes': []},
                     grid_gaps=gap(gap_px), grid_auto_flow='row', grid_align_items=align)


def section_head(kicker, title, intro=None, align=None):
    kids = [eyebrow(kicker, align=align), heading(title, align=align)]
    if intro:
        kids.append(text(f'<p>{intro}</p>', size=19, align=align))
    return container(kids, flex_gap=gap(10), width=px(100, '%'))


# ── 1. Bandeau ─────────────────────────────────────────────────────────
hero = section([
    row([
        container([
            eyebrow('Franchise Big M', color='#FFFFFF', color_global='bigmcard'),
            heading('Devenez franchisé Big M', tag='h1', size=64, size_mobile=40, color='#FFFFFF', color_global='bigmcard'),
            text('<p>Créez, transformez ou reprenez un restaurant Big M avec un concept clé en main, une formation complète et la visibilité de nos campagnes TV.</p>', size=19, color='#FFFFFF', color_global='bigmcard'),
            container([button('Déposer ma candidature', '#candidature', 'light'), button('Voir les conditions', '#conditions', 'outline-light')],
                      flex_direction='row', flex_wrap='wrap', flex_gap=gap(12), padding=box(8, 0, 0, 0)),
        ], flex_gap=gap(18), flex_justify_content='center'),
        image(IMG_HERO, radius=28, height=420),
    ], cols=2, gap_px=48, align='center'),
], background=ORANGE, globals_bg='bigmorange', padding=(64, 40))

# ── 2. Chiffres clés ───────────────────────────────────────────────────
def stat(value, label):
    return card([heading(value, tag='p', size=40, size_mobile=34, color=BTN, color_global='accent'), text(f'<p>{label}</p>', size=17)], flex_gap=gap(6))

chiffres = section([
    row([stat('2019', 'Ouverture du premier Big M, à Bondy'),
         stat('+ de 70', 'restaurants en France et au Sénégal'),
         stat('100 % halal', 'tous nos produits')], cols=3),
], padding=(56, 40))

# ── 3. Trois façons de rejoindre Big M ─────────────────────────────────
def formule(ico, title, body):
    return card([icon(ico), heading(title, tag='h3', size=26, size_mobile=24), text(f'<p>{body}</p>'),
                 container([button('Je candidate', '#candidature', 'link', icon='fas fa-arrow-right')], flex_grow=1, flex_justify_content='flex-end')],
                flex_gap=gap(14))

formules = section([
    section_head('Votre projet', 'Trois façons de rejoindre Big M', 'Que vous partiez de zéro, d’un commerce existant ou d’un restaurant Big M en activité, nous vous accompagnons.'),
    row([formule('fas fa-store', 'Création', 'Vous avez un projet d’ouverture de restaurant ? Faites confiance à Big M.'),
         formule('fas fa-sync-alt', 'Transformation', 'Transformez votre commerce actuel en restaurant Big M.'),
         formule('fas fa-key', 'Reprise', 'Reprenez un restaurant Big M déjà en activité.')], cols=3),
], padding=(40, 32))

# ── 4. Pourquoi Big M ──────────────────────────────────────────────────
pourquoi = section([
    section_head('Pourquoi Big M', 'Un concept qui a fait ses preuves'),
    row([
        card([heading('Les avantages de la franchise', tag='h3', size=26, size_mobile=24),
              checklist(['Une franchise à prix juste', 'Un concept abouti et novateur', 'Une formation simple pour votre équipe',
                         'Toutes nos compétences à votre service', 'Un approvisionnement négocié avec nos partenaires'])]),
        card([heading('Les opportunités Big M', tag='h3', size=26, size_mobile=24),
              checklist(['Une duplication rapide de vos points de vente', 'Un marché durable et en forte croissance chaque année',
                         'Une stabilité assurée en temps de crise'])]),
    ], cols=2),
], padding=(40, 32))

# ── 5. Une vision 360 (aplat brun, comme le pied de page) ─────────────
vision = section([
    row([
        container([
            eyebrow('Visibilité', color=ORANGE, color_global='bigmorange'),
            heading('Une vision 360', color='#FFFFFF', color_global='bigmcard'),
            text('<p><strong>À la télévision.</strong> Des publicités pendant les grands matchs de football : Coupe du monde, Ligue des champions…</p>'
                 '<p><strong>Dans le sport.</strong> Big M s’associe à des événements sportifs et urbains pour créer une expérience unique, et il est partenaire de RMC Sport pour la PFL et l’UFC, diffusés sur la chaîne Twitch de RMC.</p>',
                 size=18, color=FOOTER_INK, color_global='bigmfooterink'),
        ], flex_gap=gap(14), flex_justify_content='center'),
        image(IMG_SPORT, radius=28, height=380),
    ], cols=2, gap_px=48, align='center'),
], background=INK, globals_bg='primary', padding=(72, 48), margin=box(40, 0, 0, 0))

# ── 6. Nos conditions ──────────────────────────────────────────────────
def condition(value, label):
    return card([heading(value, tag='p', size=34, size_mobile=22, color=BTN, color_global='accent'), text(f'<p>{label}</p>', size=17)], flex_gap=gap(6), padding_mobile=box(18, 16))

conditions = section([
    section_head('Nos contrats', 'Nos conditions', 'Les chiffres clés du contrat de franchise Big M.'),
    row([condition('5 ans', 'Contrat de franchise'), condition('30 000 € HT', 'Droit d’entrée'), condition('5 000 € HT', 'Formation initiale'),
         condition('Aide juridique', 'Comprise dans le droit d’entrée'), condition('5 %', 'Redevance d’enseigne'), condition('2 %', 'Redevance communication')], cols=3, mobile_cols=2),
], anchor='conditions')

# ── 7. Notre histoire ──────────────────────────────────────────────────
histoire = section([
    row([
        image(IMG_RESTO, radius=28, height=440),
        card([
            eyebrow('Notre histoire'),
            heading('Des pionniers du fast-food halal', size=36, size_mobile=28),
            text('<p>Big M fait partie des pionniers du fast-food halal en France. Créé en 2019 à Bondy, le concept est simple mais pertinent : proposer une restauration rapide qui respecte les normes halal, pour répondre à une vraie demande.</p>'),
            text('<p>« Big M, au départ, c’était une enseigne de 30 m² à Bondy, dans le 93, ouverte en 2019. Aujourd’hui, notre marque est présente sur tout le territoire français et continue de se développer de jour en jour. La clé, c’est de toujours croire en ce que l’on propose. »</p>', size=19, color=INK, color_global='text'),
            text('<p><strong>M &amp; Y</strong>, fondateurs de Big M</p>', size=15),
        ], flex_gap=gap(14), flex_justify_content='center', pad=40),
    ], cols=2, gap_px=24, align='stretch'),
], padding=(32, 24))

# ── 8. Candidature (formulaire WPForms) ────────────────────────────────
# Le formulaire « Candidature franchise » est importé dans WPForms (franchise_wpforms.py) :
# les candidatures arrivent par e-mail et dans WPForms → Entrées. Son numéro n'est connu
# qu'après l'import, d'où form_id « 0 » : on choisit le formulaire dans le widget.
# Style : réglages du widget WPForms (couleurs, arrondis, tailles) + CSS du widget (polices,
# hauteurs, survol du bouton, message de confirmation), pour garder le rendu de la maquette.
WPFORMS_CSS = """selector .wpforms-container { margin: 0; }
selector .wpforms-container .wpforms-form {
  --wpforms-label-size-font-size: 15px;
  --wpforms-label-size-line-height: 20px;
  --wpforms-field-size-input-height: 48px;
  --wpforms-field-size-padding-h: 16px;
  --wpforms-button-size-height: 52px;
  --wpforms-button-size-padding-h: 28px;
  --wpforms-button-size-font-size: 17px;
  --wpforms-button-size-margin-top: 8px;
}
selector .wpforms-field-label,
selector .wpforms-field-label-inline,
selector .wpforms-field-description,
selector input, selector select, selector textarea { font-family: 'Figtree', sans-serif; }
selector .wpforms-field-label { font-weight: 600; }
selector .wpforms-field-label-inline { font-weight: 400; }
selector .wpforms-required-label { color: var(--e-global-color-accent, #C8400A); }
selector button.wpforms-submit { font-family: 'Fredoka', sans-serif; font-weight: 600; }
selector button.wpforms-submit:hover { background: var(--e-global-color-bigmbtnhover, #A93506); }
selector .wpforms-confirmation-container-full {
  background: var(--e-global-color-bigmtile, #F2EDE7); border: 0; border-radius: 12px;
  color: var(--e-global-color-text, #2B1406); font-family: 'Figtree', sans-serif; font-size: 17px; padding: 20px 24px;
}
selector .wpforms-confirmation-container-full p { margin: 0; }"""

form = {'id': eid(), 'elType': 'widget', 'widgetType': 'wpforms', 'settings': {
    'form_id': '0',
    'display_form_name': '', 'display_form_description': '',
    'wpformsTheme': 'default', 'isMigrated': 'true',
    'fieldSize': 'medium', 'fieldBorderStyle': 'solid', 'fieldBorderSize': '1', 'fieldBorderRadius': '12',
    'fieldBackgroundColor': '#FFFFFF', 'fieldBorderColor': '#2B140626', 'fieldTextColor': INK, 'fieldMenuColor': '#FFFFFF',
    'labelSize': 'medium', 'labelColor': INK, 'labelSublabelColor': MUTED, 'labelErrorColor': '#D63637',
    'buttonSize': 'medium', 'buttonBorderStyle': 'none', 'buttonBorderRadius': '12',
    'buttonBackgroundColor': BTN, 'buttonTextColor': '#FFFFFF',
    'containerPadding': '0', 'containerBorderStyle': 'none', 'containerShadowSize': 'none',
    'pageBreakColor': BTN,
    '__globals__': glob(fieldTextColor='text', labelColor='text', buttonBackgroundColor='accent', pageBreakColor='accent'),
    'custom_css': WPFORMS_CSS,
}, 'elements': []}

candidature = section([
    container([
        section_head('N’attendez plus !', 'Nouvelle aventure, nouvelle vie', 'Parlez-nous de votre projet : remplissez le formulaire ci-dessous et notre équipe franchise étudiera votre candidature.'),
        card([form], pad=36),
    ], flex_gap=gap(28), width=px(860), width_tablet=px(100, '%')),
], anchor='candidature', padding=(64, 40), flex_align_items='center')

content = [hero, chiffres, formules, pourquoi, vision, conditions, histoire, candidature]


def typo(v):
    """Espaces insécables à la française, comme dans bigm-core (\u202f avant ! : ? %, \u00a0 dans « Big M »)."""
    if not isinstance(v, str) or v.startswith(('http', '#', 'globals/')):
        return v
    v = v.replace('Big M', 'Big\u00a0M')
    for sign in (':', '!', '?', '%', '»'):
        v = v.replace(' ' + sign, '\u202f' + sign)
    v = v.replace('« ', '«\u202f')
    import re
    v = re.sub(r'(\d) (\d{3})', '\\1\u202f\\2', v)  # 30 000 → 30 000 (espace fine)
    v = v.replace(' €', '\u00a0€').replace('€ HT', '€\u00a0HT').replace(' m²', '\u00a0m²')
    return v


def walk(o):
    if isinstance(o, dict):
        return {k: (v if k in ('url', 'custom_id', 'id', '_id', 'widgetType', 'elType', 'email_to', 'email_reply_to', 'email_content', 'custom_css') else walk(v)) for k, v in o.items()}
    if isinstance(o, list):
        return [walk(x) for x in o]
    return typo(o)


content = walk(content)

with open(sys.argv[1], 'w', encoding='utf-8') as f:
    json.dump(content, f, ensure_ascii=False)
with open(sys.argv[2], 'w', encoding='utf-8') as f:
    json.dump({'content': content, 'page_settings': {'hide_title': 'yes'}, 'version': '0.4',
               'title': 'Big M — Devenir franchisé', 'type': 'page'}, f, ensure_ascii=False, indent=1)
if len(sys.argv) > 3:
    with open(sys.argv[3], 'w', encoding='utf-8') as f:
        json.dump({'content': [content[-1]], 'page_settings': [], 'version': '0.4',
                   'title': 'Big M — Candidature franchise (WPForms)', 'type': 'container'}, f, ensure_ascii=False, indent=1)
print('ok', sum(1 for _ in json.dumps(content)), 'octets')
