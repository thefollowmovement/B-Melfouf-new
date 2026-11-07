#!/usr/bin/env python3
"""Formulaire WPForms « Candidature franchise » (import : WPForms → Outils → Importer).

Mêmes questions que l'ancien formulaire de la page « Devenir franchisé », deux champs par ligne.

Usage : python3 franchise_wpforms.py candidature-franchise-wpforms.json
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from bigm_wpforms import EMAIL_TO, consent, email, form, layout, phone, select, text, textarea, write  # noqa: E402

fields = [
    layout(1, [2, 4, 6], [3, 5, 7]),
    text(2, 'Prénom'),
    text(3, 'Nom'),
    email(4),
    phone(5),
    text(6, 'Zone géographique du restaurant', placeholder='Département ou ville'),
    text(7, 'Adresse du local (si connue)', required=False),
    text(8, 'Situation actuelle', placeholder='Métier, expériences…'),
    layout(9, [10], [11]),
    select(10, 'Apport personnel (hors emprunt bancaire)', 'Choisissez une tranche',
           ['130 000 € – 150 000 €', '150 000 € – 200 000 €', 'Plus de 200 000 €']),
    select(11, 'Délai pour réaliser votre projet', 'Choisissez un délai',
           ['3 mois', '6 mois', '12 mois', 'Plus de 12 mois']),
    textarea(12, 'Questions supplémentaires'),
    consent(13, 'J’accepte que Big M utilise ces informations pour étudier ma candidature et me recontacter.'),
]

write(sys.argv[1], form(
    'Candidature franchise', 'Envoyer ma candidature', fields,
    notification={
        'notification_name': 'Équipe franchise',
        'email': EMAIL_TO,
        'subject': 'Candidature franchise : {field_id="2"} {field_id="3"} ({field_id="6"})',
        'sender_name': '{site_name}',
        'sender_address': '{admin_email}',
        'replyto': '{field_id="4"}',
        'message': '{all_fields}',
    },
    confirmation='<p>Merci ! Votre candidature a bien été envoyée. Notre équipe franchise étudie votre projet et vous recontacte rapidement.</p>',
))
