# Modèles Elementor Big M

## Devenir franchisé — `devenir-franchise.json`

Page `/devenir-franchise/` du nouveau site, reconstruite d'après l'ancienne page
(<https://bigm-burger.fr/devenir-franchise/>) avec la charte Big M : couleurs et polices
du kit Elementor de `bigm-child`, cartes blanches arrondies sur fond crème, boutons arrondis.
C'est l'adresse des liens « Franchise » du menu et de la carte « Devenez franchisé » de l'accueil.

### Sections

1. **Bandeau orange** : « Devenez franchisé Big M », boutons « Déposer ma candidature » (→ `#candidature`) et « Voir les conditions » (→ `#conditions`), photo de la poignée de main.
2. **Chiffres clés** : 2019 (Bondy), plus de 70 restaurants en France et au Sénégal, 100 % halal.
3. **Trois façons de rejoindre Big M** : Création, Transformation, Reprise.
4. **Pourquoi Big M** : avantages de la franchise, opportunités.
5. **Une vision 360** (aplat brun) : publicités TV pendant les grands matchs, partenariat RMC Sport (PFL, UFC), photo du maillot du MHSC.
6. **Nos conditions** (ancre `#conditions`) : 5 ans, 30 000 € HT, 5 000 € HT, aide juridique, 5 %, 2 %.
7. **Notre histoire** : présentation et citation des fondateurs, photo du comptoir de Saint-Ouen.
8. **Candidature** (ancre `#candidature`) : formulaire **WPForms** « Candidature franchise », mêmes questions que l'ancien formulaire, plus une case de consentement.

Textes repris de l'ancienne page, fautes corrigées (« prix juste », « Contactez-nous », « Faites la reprise »…),
doublon « Approvisionnement négocié » retiré. Logos de compétitions et de partenaires (UEFA, FIFA, RMC, UFC…) non repris.

### Fichiers

| Fichier | Rôle |
| ------- | ---- |
| `devenir-franchise.json` | Modèle Elementor de la page entière (formulaire WPForms inclus). |
| `devenir-franchise-candidature.json` | La seule section « Candidature » : sert à remplacer l'ancien formulaire Elementor Pro sur une page déjà publiée. |
| `candidature-franchise-wpforms.json` | Formulaire WPForms à importer (WPForms → Outils → Importer). |
| `franchise_page.py`, `franchise_wpforms.py` | Générateurs des fichiers ci-dessus. |

### Mettre la page en ligne (nouvelle page)

1. **WPForms → Outils → Importer** → choisir `candidature-franchise-wpforms.json` → **Importer les formulaires**.
2. **Modèles → Modèles enregistrés → Importer des modèles** → choisir `devenir-franchise.json` → Importer.
3. **Pages → Ajouter** : titre « Devenir franchisé », slug `devenir-franchise`, modèle de page **Elementor pleine largeur**.
4. **Modifier avec Elementor** → icône dossier « Ajouter un modèle » → **Mes modèles** → « Big M — Devenir franchisé » → **Insérer**.
5. Cliquer sur le formulaire (widget WPForms, en bas) → **Formulaire** : choisir « Candidature franchise » → **Publier**.
6. À l'import, Elementor copie les trois photos dans la médiathèque. Les copies n'ont **pas de texte alternatif** :
   remplir le texte alternatif des copies, ou resélectionner les originaux (n° 45, 46, 50) dans les widgets Image.
7. Vider le cache du CDN Hostinger, puis envoyer une candidature d'essai (voir plus bas).

### Passer une page déjà publiée au formulaire WPForms

1. **WPForms → Outils → Importer** → `candidature-franchise-wpforms.json`.
2. **Modèles → Modèles enregistrés → Importer des modèles** → `devenir-franchise-candidature.json`.
3. Modifier la page avec Elementor → clic droit sur la dernière section (« N'attendez plus ! ») → **Supprimer**.
4. Icône dossier en bas de page → **Mes modèles** → « Big M — Candidature franchise (WPForms) » → **Insérer**
   (la section garde l'ancre `#candidature` des boutons du haut de page).
5. Cliquer sur le formulaire → **Formulaire** : « Candidature franchise » → **Mettre à jour**. Vider le cache du CDN.

### Formulaire de candidature (WPForms)

- Champs, deux par ligne grâce au champ **Mise en page** de WPForms Pro (un par ligne sur mobile) :
  prénom | nom, e-mail | téléphone, zone géographique | adresse du local (facultatif), situation actuelle,
  apport personnel (130–150 k€ / 150–200 k€ / plus de 200 k€) | délai (3, 6, 12, plus de 12 mois),
  questions (facultatif), consentement.
- **Suivi** : chaque candidature est enregistrée dans **WPForms → Entrées** (statut lu / non lu, étoiles, notes,
  export CSV) et envoyée par e-mail à `contact@bigm-burger.fr`, avec réponse directe au candidat.
- Sujet de l'e-mail : `Candidature franchise : [prénom] [nom] ([zone])`.
- Message après envoi : « Merci ! Votre candidature a bien été envoyée… » (WPForms → formulaire → Réglages → Confirmations).
- Anti-spam WPForms activé ; les envois jugés indésirables sont gardés dans Entrées → Spam.
- Téléphone au format « International » : accepte `06 12 34 56 78` comme `+33 6…`, sans liste de pays.
- **Style** : réglé dans le widget WPForms (onglet Style : champs blancs, bordure brune légère, arrondis 12 px,
  bouton couleur Big M) et dans son **CSS personnalisé** (onglet Avancé : polices Fredoka / Figtree, hauteurs,
  survol du bouton, encadré de confirmation crème). Les couleurs suivent le kit Elementor.
- **Tester** : envoyer une candidature d'essai, vérifier l'e-mail et la ligne dans WPForms → Entrées, puis la supprimer.

### À vérifier avant la mise en production

- **Destinataire des candidatures** : `contact@bigm-burger.fr` par défaut (e-mail de contact des Réglages Big M).
  L'ancien formulaire envoyait peut-être ailleurs : à corriger dans WPForms → Candidature franchise → Réglages → Notifications.
- **Chiffres du contrat** : écrits dans la page, et aussi dans Réglages Big M → Franchise (utilisés par la carte de l'accueil).
  Les modifier aux deux endroits.
- **« Plus de 70 restaurants »** et **« M & Y, fondateurs de Big M »** (l'ancienne page disait « Owner ») : à confirmer.
- **Consentement** : ajouter un lien vers la politique de confidentialité quand la page existera
  (WPForms → Candidature franchise → champ « Données personnelles » → Description).
- **Ancien formulaire Elementor Pro** : une fois la page passée à WPForms, les éventuels envois de test restent dans
  Elementor → Soumissions ; on peut les supprimer.

### Regénérer le modèle

Le modèle est produit par `franchise_page.py` (Python 3, sans dépendance) :

```bash
python3 franchise_page.py /tmp/elements.json devenir-franchise.json devenir-franchise-candidature.json
```

```bash
python3 franchise_wpforms.py candidature-franchise-wpforms.json
```

Le premier fichier contient le seul contenu Elementor (`_elementor_data`), le deuxième le modèle de la page,
le troisième la seule section Candidature. Le second script produit le formulaire WPForms.
