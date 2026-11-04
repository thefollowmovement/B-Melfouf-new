# Franchise, recrutement, actualités

Étape 6 du brief, hors restaurants (voir `docs/restaurants.md`). Ces pages n'ont pas d'équivalent dans le prototype : elles en reprennent les composants (en-têtes, cartes blanches arrondies, boutons, cartes d'articles).

## Pages créées par la démo

L'extension de démonstration les crée une fois, dès qu'Elementor Pro est actif. Elles sont gardées quand on supprime la démo.

| Page | Contenu |
|---|---|
| `/devenir-franchise/` | En-tête, **Chiffres de la franchise** (Réglages Big M), « Pourquoi Big M », formulaire de candidature |
| `/recrutement/` | En-tête, « Travailler chez Big M », formulaire avec envoi de CV |
| `/actualites/` | Page des articles : en-tête, catégories (Tout, News, Presse), cartes, pages de 9 articles |

Les textes entre crochets sont à remplacer par ceux de l'ancien site. Celui-ci n'était pas accessible pendant le développement : il faut reprendre le texte de la page franchise actuelle. Ils se modifient dans Elementor (widget **Big M — Section texte**).

## Formulaires (Elementor Pro)

Champs du brief (§ 9), dans le widget **Formulaire** d'Elementor Pro :

- **Franchise :**
  - nom, prénom, e-mail, téléphone ;
  - zone géographique, département ou ville, adresse du local si connue ;
  - situation actuelle, métier et expériences ;
  - apport hors emprunt (130–150 k€, 150–200 k€, plus de 200 k€) ;
  - délai (3, 6, 12 mois et plus) ;
  - questions.
- **Recrutement :**
  - prénom, nom ;
  - villes où travailler, adresse, téléphone ;
  - e-mail (facultatif, ajouté pour pouvoir répondre par écrit) ;
  - CV et lettre de motivation : 3 fichiers au plus, PDF ou DOCX, 5 Mo chacun ;
  - message.

Pour les deux formulaires :

| Sujet | Réglage |
|---|---|
| Envoi | E-mail à l'adresse de contact de Réglages Big M (modifiable dans l'onglet Actions du formulaire), réponse directe à l'e-mail saisi |
| Archives | Chaque envoi est enregistré dans **Elementor → Soumissions** |
| CV | Joints à l'e-mail, puis effacés du serveur : ils ne restent pas en ligne |
| Anti-spam | Champ piège (honeypot), plus **reCAPTCHA v3** dès que ses clés sont saisies |
| Consentement | Case obligatoire, avec un lien vers `/politique-de-confidentialite/` |
| Téléphone | Le champ d'Elementor refuse les espaces : ils sont retirés automatiquement quand la personne quitte le champ |
| Style | Couleurs et polices de la charte dans l'onglet Style du formulaire ; carte blanche par la classe `bigm-formulaire` du conteneur (`assets/css/formulaires.css`) |

À faire avant la mise en ligne :

1. **Clés reCAPTCHA v3.** Créer les clés sur Google reCAPTCHA (type v3, domaine du site), puis les saisir dans **Elementor → Réglages → Intégrations**. Tant qu'elles manquent, seuls les administrateurs voient un avertissement dans le formulaire. Cloudflare Turnstile n'existe pas dans Elementor Pro : il faudrait une extension en plus.
2. **Adresses de réception.** Confirmer avec le client qui reçoit les candidatures (franchise et recrutement).
3. **Envoi des e-mails.** Vérifier qu'ils arrivent : l'expéditeur est une adresse du domaine. Un SMTP authentifié (Hostinger) est conseillé à l'étape 7.
4. **Politique de confidentialité.** La compléter : durée de conservation des soumissions, envoi des CV par e-mail.

## Widgets ajoutés

| Widget | Usage |
|---|---|
| Big M — Section texte | Titre, texte mis en forme, bouton, ancre ; option « sur une carte blanche ». Aussi pour les pages légales. |
| Big M — Chiffres de la franchise | Contrat, droit d'entrée, formation, redevances, tirés de Réglages Big M → Franchise |
| Big M — En-tête de page | Renommé (ancien « En-tête de la carte ») : titre, texte, bouton. L'icône de sac n'apparaît que pour le bouton Commander. |

## Actualités

Les articles sont natifs (menu **Articles**), rangés dans les catégories **News** et **Presse**.

| Adresse | Contenu |
|---|---|
| `/actualites/` | Titre de la page Actualités ; son texte (ou son extrait) sert de chapeau |
| `/actualites/presse/`, `/actualites/news/` | Une catégorie : son nom et sa description |
| `/{article}/` | Retour aux actualités, catégorie et date, titre, photo, texte, « À lire aussi » (3 articles, de la même catégorie d'abord) |

La démo règle la base des catégories sur `actualites` (**Réglages → Permaliens**). La catégorie par défaut (« Non classé ») n'apparaît ni dans les pastilles ni sur les cartes.

Les gabarits sont dans le thème : `home.php`, `category.php`, `single-post.php`. Ils appellent `bigm_page_actualites()` et `bigm_page_article()` de bigm-core. Un modèle du Theme Builder d'Elementor Pro, s'il vise ces pages, les remplace. Les cartes d'articles de l'accueil et de la page Actualités partagent le même balisage et `assets/css/articles.css`.

La reprise des articles de l'ancien site, avec leurs images, est prévue avec les redirections (étape 7).

## Tester

```bash
wp eval-file tests/wp/test-bigm-contenus.php --user=IDENTIFIANT_ADMIN
```

Il fait 30 vérifications :

- sections de texte, chiffres, en-tête de page ;
- cartes, pastilles et articles des actualités ;
- chargement de l'habillage des formulaires et réglages des formulaires de la démo ;
- restaurants, si le plugin est actif.
