"""Construit la page « Devenir franchisé » Big M au format Elementor.

Contenu repris de https://bigm-burger.fr/devenir-franchise/ (orthographe corrigée),
mise en forme de la charte Big M (kit Elementor de bigm-child : couleurs globales,
Fredoka / Figtree, cartes blanches arrondies sur fond crème, boutons arrondis).

Usage : python3 -I franchise_page.py <elements.json> <modele.json> [section.json]
  - elements.json : contenu à mettre dans _elementor_data (page de test locale) ;
  - modele.json   : modèle de la page entière à importer dans Elementor (Modèles → Importer) ;
  - section.json  : la seule section « Candidature », pour la remplacer sur une page déjà publiée.
"""
import os
import random
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from bigm_elementor import *  # noqa: E402,F401,F403  (briques communes)

random.seed(20261105)  # identifiants stables d'une génération à l'autre

# Images de la médiathèque de preprod. À l'import, Elementor les télécharge depuis ces
# adresses et en crée une copie dans la médiathèque (le widget Image exige une adresse).
IMG_HERO = {'id': 45, 'url': PREPROD + 'b-franchise-poignee.jpg', 'alt': 'Deux associés se serrent la main dans un restaurant Big M', 'source': 'library'}
IMG_SPORT = {'id': 46, 'url': PREPROD + 'b-mhsc-maillot.jpg', 'alt': 'Le logo Big M sur le maillot du MHSC', 'source': 'library'}
IMG_RESTO = {'id': 50, 'url': PREPROD + 'b-restaurant-saint-ouen.jpg', 'alt': 'Le comptoir du Big M de Saint-Ouen', 'source': 'library'}

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

# ── 8. Candidature (formulaire WPForms, voir bigm_elementor.wpforms_widget) ─
form = wpforms_widget()

candidature = section([
    container([
        section_head('N’attendez plus !', 'Nouvelle aventure, nouvelle vie', 'Parlez-nous de votre projet : remplissez le formulaire ci-dessous et notre équipe franchise étudiera votre candidature.'),
        card([form], pad=36),
    ], flex_gap=gap(28), width=px(860), width_tablet=px(100, '%')),
], anchor='candidature', padding=(64, 40), flex_align_items='center')

content = [hero, chiffres, formules, pourquoi, vision, conditions, histoire, candidature]

write(content, sys.argv[1], sys.argv[2], 'Big M — Devenir franchisé',
      sys.argv[3] if len(sys.argv) > 3 else None, 'Big M — Candidature franchise (WPForms)')
