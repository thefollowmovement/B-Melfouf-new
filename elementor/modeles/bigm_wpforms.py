"""Briques communes des formulaires WPForms Big M (import : WPForms → Outils → Importer).

Format d'un export WPForms : liste de formulaires { id, field_id, fields, settings, meta }.
Les champs vont par deux grâce au champ Mise en page (Layout, WPForms Pro) ; sur mobile, un par ligne.
"""
import json
import re

EMAIL_TO = 'contact@bigm-burger.fr'


def text(fid, label, required=True, placeholder=''):
    return {'id': str(fid), 'type': 'text', 'label': label, 'description': '', 'required': '1' if required else '',
            'size': 'large', 'placeholder': placeholder, 'limit_count': '1', 'limit_mode': 'characters',
            'default_value': '', 'input_mask': '', 'css': ''}


def email(fid, label='E-mail'):
    return {'id': str(fid), 'type': 'email', 'label': label, 'description': '', 'required': '1', 'size': 'large',
            'placeholder': '', 'confirmation_placeholder': '', 'default_value': '', 'filter_type': '',
            'allowlist': '', 'denylist': '', 'css': ''}


def phone(fid, label='Téléphone'):
    """Format « International » : accepte 06 12 34 56 78 comme +33 6…, sans liste de pays."""
    return {'id': str(fid), 'type': 'phone', 'label': label, 'format': 'international', 'description': '', 'required': '1',
            'size': 'large', 'placeholder': '', 'default_value': '', 'css': ''}


def select(fid, label, placeholder, options):
    choices = {str(i): {'label': o, 'value': '', 'image': '', 'icon': '', 'icon_style': ''} for i, o in enumerate(options, 1)}
    return {'id': str(fid), 'type': 'select', 'label': label, 'choices': choices, 'description': '', 'required': '1',
            'style': 'classic', 'size': 'large', 'placeholder': placeholder, 'default_value': '', 'css': ''}


def textarea(fid, label, placeholder=''):
    return {'id': str(fid), 'type': 'textarea', 'label': label, 'description': '', 'required': '',
            'size': 'medium', 'placeholder': placeholder, 'limit_count': '1', 'limit_mode': 'characters', 'default_value': '', 'css': ''}


def consent(fid, sentence, label='Données personnelles'):
    return {'id': str(fid), 'type': 'checkbox', 'label': label, 'description': '', 'required': '1',
            'choices': {'1': {'label': sentence, 'value': '', 'image': '', 'icon': '', 'icon_style': ''}},
            'choices_images_style': 'modern', 'choices_icons_color': '', 'choices_icons_size': 'large',
            'choices_icons_style': 'default', 'input_columns': '', 'choice_limit': '', 'dynamic_choices': '', 'css': ''}


def file_upload(fid, label, description, extensions, max_size_mb, max_files):
    """Dépôt de fichiers (style moderne : glisser-déposer). Fichiers hors médiathèque, accès réservé
    aux administrateurs connectés (WPForms → Entrées) ; ils sont aussi joints à l'e-mail de notification."""
    return {'id': str(fid), 'type': 'file-upload', 'label': label, 'description': description, 'required': '1',
            'extensions': extensions, 'max_size': str(max_size_mb), 'max_file_number': str(max_files),
            'style': 'modern', 'css': '', 'media_library': '',
            'is_restricted': '1', 'user_restrictions': 'logged', 'user_roles_restrictions': '["administrator"]',
            'user_names_restrictions': '', 'is_protected': '', 'protection_password': '',
            'camera_enabled': '', 'label_hide': ''}


def layout(fid, left, right):
    """Deux colonnes de 50 %, affichées en lignes : left[i] et right[i] restent côte à côte."""
    col = lambda ids: {'width_custom': '', 'width_preset': '50', 'fields': ids}
    return {'id': str(fid), 'type': 'layout', 'label': 'Mise en page', 'name': 'Mise en page', 'description': '',
            'label_hide': '1', 'size': 'large', 'preset': '50-50', 'display': 'rows', 'css': '',
            'columns': [col(left), col(right)]}


def typo(v):
    """Espaces insécables à la française, comme dans le modèle Elementor (\u202f avant ! ?, \u00a0 dans « Big M »)."""
    v = v.replace('Big M', 'Big\u00a0M')
    for sign in ('!', '?'):
        v = v.replace(' ' + sign, '\u202f' + sign)
    v = re.sub(r'(\d) (\d{3})', '\\1\u202f\\2', v)
    return v.replace(' €', '\u00a0€')


def form(title, submit, fields, notification, confirmation):
    """Formulaire complet : anti-spam, envoi en AJAX, entrées enregistrées (WPForms Pro)."""
    for f in fields:
        f['label'] = typo(f['label'])
        if f.get('description'):
            f['description'] = typo(f['description'])
        for c in f.get('choices', {}).values():
            c['label'] = typo(c['label'])
    return {
        'id': '0',
        'field_id': max(int(f['id']) for f in fields) + 1,
        'fields': {f['id']: f for f in fields},
        'settings': {
            'form_title': title,
            'form_desc': '',
            'submit_text': submit,
            'submit_text_processing': 'Envoi en cours…',
            'form_class': '',
            'submit_class': '',
            'ajax_submit': '1',
            'antispam_v3': '1',
            'store_spam_entries': '1',
            'notification_enable': '1',
            'notifications': {'1': notification},
            'confirmations': {
                '1': {
                    'name': 'Message de confirmation',
                    'type': 'message',
                    'message': typo(confirmation),
                    'message_scroll': '1',
                    'page': '',
                    'redirect': '',
                },
            },
        },
        'meta': {'template': 'blank'},
    }


def write(path, *forms):
    with open(path, 'w', encoding='utf-8') as fh:
        json.dump(list(forms), fh, ensure_ascii=False, indent=1)
    print('ok', ', '.join(f"{f['settings']['form_title']} ({len(f['fields'])} champs)" for f in forms))
