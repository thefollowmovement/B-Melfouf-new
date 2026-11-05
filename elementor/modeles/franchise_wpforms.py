#!/usr/bin/env python3
"""Formulaire WPForms « Candidature franchise » (import : WPForms → Outils → Importer).

Mêmes questions que l'ancien formulaire Elementor Pro de la page « Devenir franchisé ».
Les champs vont par deux grâce au champ Mise en page (Layout, WPForms Pro), comme dans la maquette.

Usage : python3 franchise_wpforms.py candidature-franchise-wpforms.json
"""
import json
import sys

EMAIL_TO = 'contact@bigm-burger.fr'


def text(fid, label, required=True, placeholder=''):
    return {'id': str(fid), 'type': 'text', 'label': label, 'description': '', 'required': '1' if required else '',
            'size': 'large', 'placeholder': placeholder, 'limit_count': '1', 'limit_mode': 'characters',
            'default_value': '', 'input_mask': '', 'css': ''}


def select(fid, label, placeholder, options):
    choices = {str(i): {'label': o, 'value': '', 'image': '', 'icon': '', 'icon_style': ''} for i, o in enumerate(options, 1)}
    return {'id': str(fid), 'type': 'select', 'label': label, 'choices': choices, 'description': '', 'required': '1',
            'style': 'classic', 'size': 'large', 'placeholder': placeholder, 'default_value': '', 'css': ''}


def layout(fid, left, right):
    """Deux colonnes de 50 %, affichées en lignes : left[i] et right[i] restent côte à côte."""
    col = lambda ids: {'width_custom': '', 'width_preset': '50', 'fields': ids}
    return {'id': str(fid), 'type': 'layout', 'label': 'Mise en page', 'name': 'Mise en page', 'description': '',
            'label_hide': '1', 'size': 'large', 'preset': '50-50', 'display': 'rows', 'css': '',
            'columns': [col(left), col(right)]}


fields = [
    layout(1, [2, 4, 6], [3, 5, 7]),
    text(2, 'Prénom'),
    text(3, 'Nom'),
    {'id': '4', 'type': 'email', 'label': 'E-mail', 'description': '', 'required': '1', 'size': 'large',
     'placeholder': '', 'confirmation_placeholder': '', 'default_value': '', 'filter_type': '',
     'allowlist': '', 'denylist': '', 'css': ''},
    {'id': '5', 'type': 'phone', 'label': 'Téléphone', 'format': 'international', 'description': '', 'required': '1',
     'size': 'large', 'placeholder': '', 'default_value': '', 'css': ''},
    text(6, 'Zone géographique du restaurant', placeholder='Département ou ville'),
    text(7, 'Adresse du local (si connue)', required=False),
    text(8, 'Situation actuelle', placeholder='Métier, expériences…'),
    layout(9, [10], [11]),
    select(10, 'Apport personnel (hors emprunt bancaire)', 'Choisissez une tranche',
           ['130 000 € – 150 000 €', '150 000 € – 200 000 €', 'Plus de 200 000 €']),
    select(11, 'Délai pour réaliser votre projet', 'Choisissez un délai',
           ['3 mois', '6 mois', '12 mois', 'Plus de 12 mois']),
    {'id': '12', 'type': 'textarea', 'label': 'Questions supplémentaires', 'description': '', 'required': '',
     'size': 'medium', 'placeholder': '', 'limit_count': '1', 'limit_mode': 'characters', 'default_value': '', 'css': ''},
    {'id': '13', 'type': 'checkbox', 'label': 'Données personnelles', 'description': '', 'required': '1',
     'choices': {'1': {'label': 'J’accepte que Big M utilise ces informations pour étudier ma candidature et me recontacter.',
                       'value': '', 'image': '', 'icon': '', 'icon_style': ''}},
     'choices_images_style': 'modern', 'choices_icons_color': '', 'choices_icons_size': 'large',
     'choices_icons_style': 'default', 'input_columns': '', 'choice_limit': '', 'dynamic_choices': '', 'css': ''},
]


def typo(v):
    """Espaces insécables à la française, comme dans le modèle Elementor (\u202f avant ! ? ; :, \u00a0 dans « Big M »)."""
    import re
    v = v.replace('Big M', 'Big\u00a0M')
    for sign in ('!', '?'):
        v = v.replace(' ' + sign, '\u202f' + sign)
    v = re.sub(r'(\d) (\d{3})', '\\1\u202f\\2', v)
    return v.replace(' €', '\u00a0€')


for f in fields:
    f['label'] = typo(f['label'])
    for c in f.get('choices', {}).values():
        c['label'] = typo(c['label'])

form = {
    'id': '0',
    'field_id': 14,
    'fields': {f['id']: f for f in fields},
    'settings': {
        'form_title': 'Candidature franchise',
        'form_desc': '',
        'submit_text': 'Envoyer ma candidature',
        'submit_text_processing': 'Envoi en cours…',
        'form_class': '',
        'submit_class': '',
        'ajax_submit': '1',
        'antispam_v3': '1',
        'store_spam_entries': '1',
        'notification_enable': '1',
        'notifications': {
            '1': {
                'notification_name': 'Équipe franchise',
                'email': EMAIL_TO,
                'subject': 'Candidature franchise : {field_id="2"} {field_id="3"} ({field_id="6"})',
                'sender_name': '{site_name}',
                'sender_address': '{admin_email}',
                'replyto': '{field_id="4"}',
                'message': '{all_fields}',
            },
        },
        'confirmations': {
            '1': {
                'name': 'Message de confirmation',
                'type': 'message',
                'message': typo('<p>Merci ! Votre candidature a bien été envoyée. Notre équipe franchise étudie votre projet et vous recontacte rapidement.</p>'),
                'message_scroll': '1',
                'page': '',
                'redirect': '',
            },
        },
    },
    'meta': {'template': 'blank'},
}

with open(sys.argv[1], 'w', encoding='utf-8') as fh:
    json.dump([form], fh, ensure_ascii=False, indent=1)
print('ok', len(fields), 'champs')
