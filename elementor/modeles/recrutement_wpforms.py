#!/usr/bin/env python3
"""Formulaire WPForms « Candidature — Nous rejoindre » (import : WPForms → Outils → Importer).

Reprend l'ancien formulaire n° 1006 de https://bigm-burger.fr/contact/, avec deux changements :
  - ajout de l'e-mail (absent de l'ancien formulaire, alors que les réponses partent par e-mail)
    et du poste souhaité (équipier, formateur, manager, directeur, comme la page « Nous rejoindre ») ;
  - adresse postale complète retirée : la ville où l'on veut travailler suffit pour orienter la candidature.
CV et lettre de motivation : 3 fichiers maximum, joints à l'e-mail et gardés dans WPForms → Entrées.

Usage : python3 recrutement_wpforms.py candidature-recrutement-wpforms.json
"""
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from bigm_wpforms import EMAIL_TO, consent, email, file_upload, form, layout, phone, select, text, textarea, write  # noqa: E402

CV = 8  # champ des fichiers, joint à l'e-mail

fields = [
    layout(1, [2, 4, 6], [3, 5, 7]),
    text(2, 'Prénom'),
    text(3, 'Nom'),
    email(4),
    phone(5),
    select(6, 'Poste souhaité', 'Choisissez un poste',
           ['Équipier ou équipière', 'Formateur ou formatrice', 'Manager', 'Directeur ou directrice', 'Je ne sais pas encore']),
    text(7, 'Ville(s) où vous aimeriez travailler', placeholder='Ex. Bondy, Saint-Ouen…'),
    file_upload(CV, 'CV et lettre de motivation', 'PDF, Word ou photo : 3 fichiers maximum, 5 Mo chacun.',
                'pdf,doc,docx,odt,jpg,jpeg,png', 5, 3),
    textarea(9, 'Message', placeholder='Vos disponibilités, votre expérience, ce qui vous motive…'),
    consent(10, 'J’accepte que Big M conserve ma candidature pour étudier mon profil et me recontacter.'),
]

write(sys.argv[1], form(
    'Candidature — Nous rejoindre', 'Envoyer ma candidature', fields,
    notification={
        'notification_name': 'Équipe recrutement',
        'email': EMAIL_TO,
        'subject': 'Candidature : {field_id="2"} {field_id="3"}, {field_id="6"} ({field_id="7"})',
        'sender_name': '{site_name}',
        'sender_address': '{admin_email}',
        'replyto': '{field_id="4"}',
        'message': '{all_fields}',
        'file_upload_attachment_enable': '1',
        'file_upload_attachment_fields': [CV],
    },
    confirmation='<p>Merci ! Votre candidature a bien été envoyée. Nous vous répondons rapidement : par e-mail si votre profil ne correspond pas pour le moment, par téléphone, e-mail ou SMS s’il est retenu.</p>',
))
