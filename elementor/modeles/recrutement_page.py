"""Construit la page « Nous rejoindre » (recrutement) Big M au format Elementor.

Contenu repris de https://bigm-burger.fr/contact/ (ancienne page « Nous Rejoindre »), orthographe
corrigée, mise en forme de la charte Big M comme la page « Devenir franchisé ».
Page prévue à l'adresse /recrutement/, celle des liens du menu et de la carte de l'accueil.

Usage : python3 -I recrutement_page.py <elements.json> <modele.json>
  - elements.json : contenu à mettre dans _elementor_data (page de test locale) ;
  - modele.json   : modèle de la page à importer dans Elementor (Modèles → Importer).
"""
import os
import random
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from bigm_elementor import *  # noqa: E402,F401,F403  (briques communes)

random.seed(20261106)  # identifiants stables d'une génération à l'autre

# Images de la médiathèque de preprod (copiées dans la médiathèque à l'import, voir README).
IMG_EQUIPE = {'id': 44, 'url': PREPROD + 'b-chicken-gants.jpg', 'alt': 'Un burger au poulet préparé par des mains gantées', 'source': 'library'}
IMG_DEVANTURE = {'id': 206, 'url': PREPROD + 'A7409086-1-e1793815908585.jpg', 'alt': 'La devanture d’un restaurant Big M', 'source': 'library'}

# ── 1. Bandeau ─────────────────────────────────────────────────────────
hero = section([
    row([
        container([
            eyebrow('Recrutement', color='#FFFFFF', color_global='bigmcard'),
            heading('Nous rejoindre', tag='h1', size=64, size_mobile=40, color='#FFFFFF', color_global='bigmcard'),
            text('<p>Pour être recruté·e, rien de plus simple : suivez les étapes ci-dessous, nous nous occupons du reste. Et n’oubliez pas nos quelques conseils pour optimiser votre candidature !</p>', size=19, color='#FFFFFF', color_global='bigmcard'),
            container([button('Postuler', '#candidature', 'light'), button('Voir nos restaurants', '/restaurants/', 'outline-light')],
                      flex_direction='row', flex_wrap='wrap', flex_gap=gap(12), padding=box(8, 0, 0, 0)),
        ], flex_gap=gap(18), flex_justify_content='center'),
        image(IMG_EQUIPE, radius=28, height=420),
    ], cols=2, gap_px=48, align='center'),
], background=ORANGE, globals_bg='bigmorange', padding=(64, 40))

# ── 2. Nos métiers (ceux de la carte « Nous rejoindre » de l'accueil) ──
def metier(ico, title, body):
    return card([icon(ico), heading(title, tag='h3', size=26, size_mobile=24), text(f'<p>{body}</p>'),
                 container([button('Je postule', '#candidature', 'link', icon='fas fa-arrow-right')], flex_grow=1, flex_justify_content='flex-end')],
                flex_gap=gap(14))

metiers = section([
    section_head('Nos métiers', 'Trois métiers, une même équipe', 'Débutant ou expérimenté, il y a une place pour vous dans l’un de nos restaurants.'),
    row([metier('fas fa-hamburger', 'Équipier ou équipière', 'En cuisine, au comptoir ou en salle, vous faites vivre le restaurant au quotidien. Débutant ? Nous vous formons.'),
         metier('fas fa-users', 'Manager', 'Vous animez l’équipe et faites tourner le restaurant. Pour ce poste, un pré-entretien précède l’entretien.'),
         metier('fas fa-chalkboard-teacher', 'Formateur ou formatrice', 'Vous formez et accompagnez les nouvelles recrues tout au long de leur intégration.')], cols=3),
], padding=(56, 40))

# ── 3. Vous préparer, nous convaincre ──────────────────────────────────
def etapes(items):
    """Liste à icônes : une icône par étape."""
    s = {'icon_list': [{'_id': eid(), 'text': t, 'selected_icon': {'value': ico, 'library': 'fa-solid'}} for ico, t in items],
         'icon_color': BTN, 'text_color': INK, 'icon_size': px(20), 'space_between': px(16), 'text_indent': px(12),
         'icon_self_vertical_align': 'flex-start', 'icon_vertical_offset': px(2),
         '__globals__': glob(icon_color='accent', text_color='text')}
    s.update(font('icon_typography', 'Figtree', 400, 17, 16, 1.45))
    return {'id': eid(), 'elType': 'widget', 'widgetType': 'icon-list', 'settings': s, 'elements': []}

conseils = section([
    section_head('Vous préparer, nous convaincre', 'Mettez toutes les chances de votre côté',
                 'Big M vous aide à préparer votre entretien de recrutement. Préparez-vous attentivement : vous serez plus à l’aise le jour J.'),
    row([
        card([heading('Des astuces pour votre CV', tag='h3', size=26, size_mobile=24),
              checklist(['Indiquez le poste que vous souhaitez et vos disponibilités (pour un CDD, par exemple).',
                         'Précisez les emplois que vous avez occupés, même de courte durée, et ce que vous y avez appris.',
                         'Vous avez des passions, des loisirs, vous faites du bénévolat ? C’est le moment de nous le dire.'])]),
        card([heading('Et après ?', tag='h3', size=26, size_mobile=24),
              etapes([('fas fa-envelope-open-text', 'Vous recevez vite une réponse. Si votre candidature n’est pas retenue, vous le saurez par e-mail : ne vous laissez pas abattre, vous pouvez postuler dans un autre restaurant.'),
                      ('fas fa-comment-dots', 'Bonne nouvelle ? Nous vous l’annonçons par téléphone, e-mail ou SMS.'),
                      ('fas fa-handshake', 'Vient ensuite l’entretien (ou un pré-entretien pour le poste de manager) : nous vous indiquons la date, le lieu, l’heure et le nom de votre interlocuteur.')])]),
    ], cols=2),
], padding=(40, 32))

# ── 4. Témoignages (aplat brun, comme « Une vision 360 » de la page franchise) ──
def temoignage(quote, name, role):
    return container([
        text(f'<p>« {quote} »</p>', size=19, color='#FFFFFF', color_global='bigmcard'),
        text(f'<p><strong>{name}</strong>, {role}</p>', size=15, color=FOOTER_INK, color_global='bigmfooterink'),
    ], flex_gap=gap(4))

temoignages = section([
    row([
        image(IMG_DEVANTURE, radius=28, height=440),
        container([
            eyebrow('Ils ont rejoint Big M', color=ORANGE, color_global='bigmorange'),
            heading('Une équipe qui vous fait grandir', color='#FFFFFF', color_global='bigmcard', size=40, size_mobile=30),
            temoignage('L’ambiance et la qualité de travail au sein de Big M sont inégalables. Nous sommes souvent challengés à devenir meilleurs, et à le transmettre à nos collaborateurs.',
                       'Yacine', 'formateur'),
            temoignage('Je suis manager au Big M de Bondy depuis quelques mois. J’ai été formé et accompagné tout au long de mon intégration. C’est une expérience forte en responsabilités, qui m’a énormément apporté.',
                       'Mina L.', 'manager à Bondy'),
        ], flex_gap=gap(20), flex_justify_content='center'),
    ], cols=2, gap_px=48, align='center'),
], background=INK, globals_bg='primary', padding=(72, 48), margin=box(40, 0, 0, 0))

# ── 5. Candidature (formulaire WPForms, voir bigm_elementor.wpforms_widget) ─
candidature = section([
    container([
        section_head('Faites votre check-list !', 'Un premier pas : votre candidature',
                     'Remplissez le formulaire et joignez votre CV, et votre lettre de motivation si vous en avez une. Nous nous occupons du reste.'),
        card([wpforms_widget()], pad=36),
    ], flex_gap=gap(28), width=px(860), width_tablet=px(100, '%')),
], anchor='candidature', padding=(64, 40), flex_align_items='center')

content = [hero, metiers, conseils, temoignages, candidature]

write(content, sys.argv[1], sys.argv[2], 'Big M — Nous rejoindre')
