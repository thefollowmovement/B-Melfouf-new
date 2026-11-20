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
| `franchise_page.py`, `franchise_wpforms.py` | Générateurs des fichiers ci-dessus (briques communes : `bigm_elementor.py`, `bigm_wpforms.py`). |

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
- **Image grise derrière le formulaire** : venait de la 1re version de `devenir-franchise-candidature.json`
  (Elementor remplaçait l'image de fond vide de WPForms par son image de remplacement). Corrigé dans le modèle ;
  sur une page déjà construite avec l'ancienne version, ajouter au **CSS personnalisé** du widget WPForms :
  `selector { --wpforms-background-url: none; }`.
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

## Nous rejoindre — `nous-rejoindre.json`

Page de recrutement du nouveau site, d'après l'ancienne page « Nous Rejoindre » (<https://bigm-burger.fr/contact/>),
avec la même charte que la page « Devenir franchisé ». Adresse prévue : **`/recrutement/`**, celle des liens
« Recrutez-nous ! » du menu et du bouton « Postuler » de l'accueil (pas de lien à changer, seulement les libellés).

### Sections

1. **Bandeau orange** : « Nous rejoindre », texte d'accueil de l'ancienne page, boutons « Postuler » (→ `#candidature`)
   et « Voir nos restaurants » (→ `/restaurants/`), photo du burger préparé en cuisine (n° 44).
2. **Nos métiers** : équipier ou équipière, formateur ou formatrice, manager, directeur ou directrice (du premier poste
   à la direction du restaurant), deux cartes par ligne. Mêmes postes, dans le même ordre, dans le champ « Poste souhaité » du formulaire.
3. **Vous préparer, nous convaincre** : les astuces pour le CV et « Et après ? » (réponse, annonce, entretien), repris de l'ancienne page.
4. **Ils ont rejoint Big M** (aplat brun) : témoignages de Yacine (formateur) et Mina L. (manager à Bondy), photo de la devanture (n° 206).
5. **Candidature** (ancre `#candidature`) : formulaire **WPForms** « Candidature — Nous rejoindre ».

Les photos de stock des témoignages de l'ancienne page ne sont pas reprises.

### Fichiers

| Fichier | Rôle |
| ------- | ---- |
| `nous-rejoindre.json` | Modèle Elementor de la page (formulaire WPForms inclus). |
| `candidature-recrutement-wpforms.json` | Formulaire WPForms à importer (WPForms → Outils → Importer). |
| `recrutement_page.py`, `recrutement_wpforms.py` | Générateurs des fichiers ci-dessus. |

### Mettre la page en ligne (preprod)

1. **WPForms → Outils → Importer** → `candidature-recrutement-wpforms.json` → **Importer les formulaires**.
2. **Modèles → Modèles enregistrés → Importer des modèles** → `nous-rejoindre.json` → Importer.
3. **Pages → Ajouter** : titre « Nous rejoindre », slug **`recrutement`**, modèle de page **Elementor pleine largeur**.
4. **Modifier avec Elementor** → icône dossier → **Mes modèles** → « Big M — Nous rejoindre » → **Insérer**.
5. Cliquer sur le formulaire (en bas) → **Formulaire** : « Candidature — Nous rejoindre » → **Publier**.
6. Photos : les copies créées à l'import n'ont pas de texte alternatif (comme pour la page franchise).
7. Vider le cache du CDN, puis envoyer une candidature d'essai avec un CV (voir plus bas).

### Renommer « Recrutez-nous ! » en « Nous rejoindre »

- **Apparence → Menus** : menus « Big M — En-tête » et « Big M — Menu latéral », élément « Recrutez-nous ! » →
  **Titre de la navigation** : « Nous rejoindre ». Dans « Big M — Pied de page : L'entreprise », « Recrutement » peut
  aussi devenir « Nous rejoindre ».
- **Accueil** : modifier avec Elementor → widget « Big M — Nous rejoindre » → section **Recrutement** → **Titre** : « Nous rejoindre ».
- Les valeurs par défaut écrites dans le code (thème `bigm-child`, `inc/navigation.php` ; extension `bigm-core`,
  widget `rejoindre.php`) disent encore « Recrutez-nous ! ». Elles ne servent que si les menus sont vides ou si
  le widget n'a jamais été réglé : à changer à la prochaine mise à jour du thème et de l'extension.

### Formulaire de candidature (WPForms)

- Deux champs par ligne (un par ligne sur mobile) : prénom | nom, e-mail | téléphone, poste souhaité | ville(s) où
  travailler ; puis CV et lettre de motivation, message (facultatif), consentement.
- Par rapport à l'ancien formulaire (n° 1006) : **e-mail ajouté** (il n'y en avait pas), **poste souhaité ajouté**,
  **adresse postale retirée** (la ville où l'on veut travailler suffit ; moins de données personnelles à garder).
- **Fichiers** : 3 au plus, 5 Mo chacun, PDF, Word (doc, docx, odt) ou photo (jpg, png). Glisser-déposer.
  Les fichiers sont **joints à l'e-mail** et gardés dans **WPForms → Entrées**. Ils ne vont pas dans la médiathèque,
  et leurs liens ne s'ouvrent que pour un **administrateur connecté** (WPForms → formulaire → champ CV → Avancé → Restrictions).
- E-mail à `contact@bigm-burger.fr`, réponse directe au candidat. Sujet : `Candidature : [prénom] [nom], [poste] ([villes])`.
- Message après envoi : « Merci ! Votre candidature a bien été envoyée… » (reprend « Et après ? »).
- Anti-spam WPForms activé ; envois indésirables gardés dans Entrées → Spam.
- Même style que le formulaire franchise (réglages et CSS personnalisé du widget, sans l'image grise).
- **Tester** : envoyer une candidature avec un petit PDF, vérifier l'e-mail (pièce jointe) et la ligne dans Entrées,
  puis la supprimer.

### À vérifier avant la mise en production

- **Destinataire** : `contact@bigm-burger.fr` par défaut. L'ancien formulaire envoyait peut-être à une adresse de
  recrutement : à corriger dans WPForms → Candidature — Nous rejoindre → Réglages → Notifications.
- **Textes des métiers** (section 2) : écrits pour la page, à relire par l'équipe.
- **Témoignages** : repris de l'ancien site ; vérifier que Yacine et Mina L. sont d'accord pour apparaître sur le nouveau.
- **Ancienne adresse** : l'ancien site utilisait `/contact/`. À la mise en ligne, rediriger `/contact/` vers `/recrutement/`
  (et `/devenir-franchise/` garde son adresse).
- **Durée de conservation des CV** : à indiquer dans la politique de confidentialité ; WPForms peut supprimer les
  entrées anciennes automatiquement si besoin.

## Blog — `blog-actualites.json` et `blog-article.json`

Mise en page « blog » de la page Actualités (`/actualites/`, la page des articles de Réglages → Lecture) et des
articles, avec deux modèles du **Theme Builder d'Elementor Pro**. Les cartes reprennent celles de l'accueil.

- **Actualités** (`blog-actualites.json`, type Archive) : « Le blog Big M », titre « Actualités et presse »
  (ou le nom de la catégorie sur une page de catégorie), pastilles **Tout / catégories** (la catégorie affichée
  est en orange), **article à la une** en grand (1re page), puis cartes : image, catégorie en pastille, titre,
  extrait, « Lire l'article », date. Pagination en pastilles. Trois cartes par ligne, deux sur tablette, une sur mobile.
- **Article** (`blog-article.json`, type Article unique) : « ← Toutes les actualités », catégorie · date, titre,
  image à la une arrondie, texte en colonne de lecture (intertitres Fredoka, citations soulignées d'orange,
  liens orange), boutons de partage (Facebook, X, LinkedIn, WhatsApp, e-mail), puis « À lire aussi » :
  trois articles de la même catégorie (à défaut, les plus récents).

### Mettre en place (preprod)

1. **Modèles → Modèles enregistrés → Importer des modèles** : `blog-actualites.json`, puis `blog-article.json`.
2. **Modèles → Theme Builder** → « Big M — Actualités (blog) » → **Modifier** → **Publier** → **Ajouter une condition** :
   - Inclure → **Archives** → **Archives des articles** ;
   - Inclure → **Archives** → **Catégories** → Toutes.

   Ne **pas** choisir « Toutes les archives » : le modèle remplacerait aussi la carte (`/carte/`) et ses catégories.
3. « Big M — Article » → **Modifier** → **Publier** → condition : Inclure → **Articles** → **Tous**.
4. **Réglages → Lecture** : « Les pages du site doivent afficher au plus » **10** articles (1 à la une + 9 cartes).
5. Vider le cache du CDN.

### Contenu

- Les 3 articles actuels sont des articles de démonstration (« reprendre l'article de l'ancien site »).
- L'ancien site compte **107 articles**, presque tous des retombées presse (catégorie Presse : TV, Radio,
  Print, Web) et quelques News. Pour les reprendre : sur l'ancien site, **Outils → Exporter → Articles** ;
  sur preprod, **Outils → Importer → WordPress** (extension WordPress Importer), en cochant le téléchargement
  des fichiers joints. Supprimer ensuite les articles de démonstration en double.
- Les catégories vides n'apparaissent pas dans les pastilles.

## Regénérer les modèles

Les fichiers sont produits par les scripts Python 3 (sans dépendance) de ce dossier ; `bigm_elementor.py` et
`bigm_wpforms.py` contiennent les briques communes (couleurs, cartes, boutons, widget WPForms, champs).

```bash
python3 franchise_page.py /tmp/elements.json devenir-franchise.json devenir-franchise-candidature.json
```

```bash
python3 franchise_wpforms.py candidature-franchise-wpforms.json
```

```bash
python3 recrutement_page.py /tmp/elements.json nous-rejoindre.json
```

```bash
python3 recrutement_wpforms.py candidature-recrutement-wpforms.json
```

```bash
python3 blog_templates.py .
```

Pour une page : le premier fichier contient le seul contenu Elementor (`_elementor_data`), le deuxième le modèle
à importer, le troisième (facultatif) la seule section Candidature.
