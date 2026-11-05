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
8. **Candidature** (ancre `#candidature`) : formulaire Elementor Pro, mêmes questions que l'ancien formulaire WPForms, plus une case de consentement.

Textes repris de l'ancienne page, fautes corrigées (« prix juste », « Contactez-nous », « Faites la reprise »…),
doublon « Approvisionnement négocié » retiré. Logos de compétitions et de partenaires (UEFA, FIFA, RMC, UFC…) non repris.

### Mettre la page en ligne (preprod)

1. **Modèles → Modèles enregistrés → Importer des modèles** → choisir `devenir-franchise.json` → Importer.
2. **Pages → Ajouter** : titre « Devenir franchisé », slug `devenir-franchise`, modèle de page **Elementor pleine largeur**.
3. **Modifier avec Elementor** → icône dossier « Ajouter un modèle » → **Mes modèles** → « Big M — Devenir franchisé » → **Insérer** → **Publier**.
4. À l'import, Elementor copie les trois photos dans la médiathèque. Les copies n'ont **pas de texte alternatif** :
   remplir le texte alternatif des copies, ou resélectionner les originaux (n° 45, 46, 50) dans les widgets Image.
5. **Tester le formulaire** (envoi d'une candidature d'essai) et vérifier la réception de l'e-mail.
6. Vider le cache du CDN Hostinger.

### Formulaire de candidature

- Champs : prénom, nom, e-mail, téléphone, zone géographique, adresse du local (facultatif), situation actuelle,
  apport personnel (130–150 k€ / 150–200 k€ / plus de 200 k€), délai (3, 6, 12, plus de 12 mois),
  questions (facultatif), consentement.
- Actions : **e-mail** à `contact@bigm-burger.fr` (réponse directe au candidat) et **enregistrement** dans
  Elementor → Soumissions.
- Sujet de l'e-mail : `Candidature franchise : [prénom] [nom] ([zone])`.

### À vérifier avant la mise en production

- **Destinataire des candidatures** : `contact@bigm-burger.fr` par défaut (e-mail de contact des Réglages Big M).
  L'ancien formulaire envoyait peut-être ailleurs : à corriger dans le widget Formulaire → Actions après envoi → E-mail.
- **Chiffres du contrat** : écrits dans la page, et aussi dans Réglages Big M → Franchise (utilisés par la carte de l'accueil).
  Les modifier aux deux endroits.
- **« Plus de 70 restaurants »** et **« M & Y, fondateurs de Big M »** (l'ancienne page disait « Owner ») : à confirmer.
- **Consentement** : ajouter un lien vers la politique de confidentialité quand la page existera.

### Regénérer le modèle

Le modèle est produit par `franchise_page.py` (Python 3, sans dépendance) :

```bash
python3 franchise_page.py /tmp/elements.json devenir-franchise.json
```

Le premier fichier contient le seul contenu Elementor (`_elementor_data`), le second le modèle à importer.
