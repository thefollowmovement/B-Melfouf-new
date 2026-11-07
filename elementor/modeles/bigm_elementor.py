"""Briques communes des modèles Elementor Big M (pages Devenir franchisé, Nous rejoindre…).

Charte de bigm-child : couleurs globales du kit Elementor, Fredoka (titres, boutons) et Figtree (texte),
cartes blanches arrondies sur fond crème, boutons arrondis. Chaque page appelle random.seed() avant de
construire ses éléments, pour garder des identifiants stables d'une génération à l'autre.
"""
import json
import random
import re

PREPROD = 'https://preprod.bigm-burger.fr/wp-content/uploads/2026/11/'

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


# ── Formulaire WPForms ─────────────────────────────────────────────────
# Le formulaire est importé dans WPForms (WPForms → Outils → Importer) ; son numéro n'est connu
# qu'après l'import, d'où form_id « 0 » : on choisit le formulaire dans le widget.
# Style : réglages du widget WPForms (couleurs, arrondis, tailles) + CSS du widget (polices,
# hauteurs, survol du bouton, message de confirmation), pour garder le rendu des maquettes.
# La 1re ligne du CSS neutralise l'image de fond du formulaire (voir backgroundUrl plus bas).
WPFORMS_CSS = """selector { --wpforms-background-url: none; }
selector .wpforms-container { margin: 0; }
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
selector .wpforms-field-label-inline { font-weight: 400; hyphens: manual; }
selector .wpforms-required-label { color: var(--e-global-color-accent, #C8400A); }
selector button.wpforms-submit { font-family: 'Fredoka', sans-serif; font-weight: 600; }
selector button.wpforms-submit:hover { background: var(--e-global-color-bigmbtnhover, #A93506); }
selector .wpforms-confirmation-container-full {
  background: var(--e-global-color-bigmtile, #F2EDE7); border: 0; border-radius: 12px;
  color: var(--e-global-color-text, #2B1406); font-family: 'Figtree', sans-serif; font-size: 17px; padding: 20px 24px;
}
selector .wpforms-confirmation-container-full p { margin: 0; }"""


def wpforms_widget():
    """Widget WPForms aux couleurs Big M (le formulaire se choisit dans le widget après l'import)."""
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'wpforms', 'settings': {
    'form_id': '0',
    'display_form_name': '', 'display_form_description': '',
    'wpformsTheme': 'default', 'isMigrated': 'true',
    'fieldSize': 'medium', 'fieldBorderStyle': 'solid', 'fieldBorderSize': '1', 'fieldBorderRadius': '12',
    'fieldBackgroundColor': '#FFFFFF', 'fieldBorderColor': '#2B140626', 'fieldTextColor': INK, 'fieldMenuColor': '#FFFFFF',
    'labelSize': 'medium', 'labelColor': INK, 'labelSublabelColor': MUTED, 'labelErrorColor': '#D63637',
    'buttonSize': 'medium', 'buttonBorderStyle': 'none', 'buttonBorderRadius': '12',
    'buttonBackgroundColor': BTN, 'buttonTextColor': '#FFFFFF',
    'containerPadding': '0', 'containerBorderStyle': 'none', 'containerShadowSize': 'none',
    # Image de fond vide explicite : sinon Elementor, à l'import, essaie de télécharger la valeur
    # par défaut de WPForms (« url() »), échoue et met son image grise de remplacement.
    'backgroundImage': 'none', 'backgroundUrl': {'url': '', 'id': ''},
    'pageBreakColor': BTN,
    '__globals__': glob(fieldTextColor='text', labelColor='text', buttonBackgroundColor='accent', pageBreakColor='accent'),
    'custom_css': WPFORMS_CSS,
}, 'elements': []}


# ── Typographie et écriture des fichiers ───────────────────────────────
def typo(v):
    """Espaces insécables à la française, comme dans bigm-core (\u202f avant ! : ? %, \u00a0 dans « Big M »)."""
    if not isinstance(v, str) or v.startswith(('http', '#', 'globals/')):
        return v
    v = v.replace('Big M', 'Big\u00a0M')
    for sign in (':', '!', '?', '%', '»'):
        v = v.replace(' ' + sign, '\u202f' + sign)
    v = v.replace('« ', '«\u202f')
    v = re.sub(r'(\d) (\d{3})', '\\1\u202f\\2', v)  # 30 000 → 30 000 (espace fine)
    v = v.replace(' €', '\u00a0€').replace('€ HT', '€\u00a0HT').replace(' m²', '\u00a0m²')
    return v


def walk(o):
    if isinstance(o, dict):
        return {k: (v if k in ('url', 'custom_id', 'id', '_id', 'widgetType', 'elType', 'email_to', 'email_reply_to', 'email_content', 'custom_css', '__dynamic__', 'custom_date_format') else walk(v)) for k, v in o.items()}
    if isinstance(o, list):
        return [walk(x) for x in o]
    return typo(o)



def write(content, elements_path, template_path, title, section_path=None, section_title=None):
    """Écrit le contenu Elementor, le modèle de page et, si demandé, la dernière section seule."""
    content = walk(content)
    with open(elements_path, 'w', encoding='utf-8') as f:
        json.dump(content, f, ensure_ascii=False)
    with open(template_path, 'w', encoding='utf-8') as f:
        json.dump({'content': content, 'page_settings': {'hide_title': 'yes'}, 'version': '0.4',
                   'title': title, 'type': 'page'}, f, ensure_ascii=False, indent=1)
    if section_path:
        with open(section_path, 'w', encoding='utf-8') as f:
            json.dump({'content': [content[-1]], 'page_settings': [], 'version': '0.4',
                       'title': section_title, 'type': 'container'}, f, ensure_ascii=False, indent=1)
    print('ok', sum(1 for _ in json.dumps(content)), 'octets')


def widget(kind, settings):
    """Widget Elementor quelconque (type, réglages)."""
    return {'id': eid(), 'elType': 'widget', 'widgetType': kind, 'settings': settings, 'elements': []}


def dynamic(name, **settings):
    """Balise dynamique d'Elementor, au format enregistré : [elementor-tag id=… name=… settings=…]."""
    from urllib.parse import quote_plus
    return '[elementor-tag id="%s" name="%s" settings="%s"]' % (eid(), name, quote_plus(json.dumps(settings or {}, separators=(',', ':'))))


def write_theme_template(content, path, title, kind, page_settings=None):
    """Modèle du Theme Builder d'Elementor Pro (archive, single-post…), à importer comme un modèle de page."""
    content = walk(content)
    with open(path, 'w', encoding='utf-8') as f:
        json.dump({'content': content, 'page_settings': page_settings or [], 'version': '0.4',
                   'title': title, 'type': kind}, f, ensure_ascii=False, indent=1)
    print('ok', path, sum(1 for _ in json.dumps(content)), 'octets')

