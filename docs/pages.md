# Franchise, recrutement, actualités

Étape 6 du brief, hors restaurants (voir `docs/restaurants.md`). Ces pages n'ont pas d'équivalent dans le prototype : elles en reprennent les composants (en-têtes, cartes blanches arrondies, boutons, cartes d'articles).

## Pages de la démo

Les pages à formulaire ne sont plus créées d'office : sur la préprod, les pages **Devenir franchisé** (`/devenir-franchise/`) et **Nous rejoindre** (`/nous-rejoindre/`) existent déjà, avec des formulaires WPForms. Pour les pages Elementor Pro décrites ici, il faut le demander : **Outils → Démo Big M → Créer les pages à formulaire**. Une page qui existe déjà à la même adresse n'est jamais remplacée. Choisir l'une des deux solutions et supprimer l'autre, pour ne garder qu'une extension de formulaires.

| Page | Contenu |
|---|---|
| `/devenir-franchise/` | En-tête, **Chiffres de la franchise** (Réglages Big M), « Pourquoi Big M », formulaire de candidature |
| `/recrutement/` | En-tête, « Travailler chez Big M », formulaire avec envoi de CV. Sur la préprod, la page de recrutement est `/nous-rejoindre/` (WPForms) : les liens par défaut y mènent, et `/recrutement/` y est redirigé. |
| `/actualites/` | Page des articles : en-tête, catégories (Tout, News, Presse), cartes, pages de 9 articles. Créée avec l'accueil. |

Les pages créées par la démo sont gardées quand on la supprime. Les textes entre crochets sont à remplacer par ceux de l'ancien site, dans Elementor (widget **Big M — Section texte**).

## Pages légales et jeu concours

La démo (0.5) les crée une fois, au premier passage d'un administrateur, avec Elementor (widgets **En-tête de page** et **Section texte** sur carte blanche). Elles sont gardées quand on supprime la démo. Une page déjà publiée à la même adresse n'est pas remplacée ; le brouillon « Politique de confidentialité » créé par WordPress l'est.

| Page | Contenu |
|---|---|
| `/mentions-legales/` | Repris de l'ancienne page (`/privacy-policy/`, « Mention légales ») : éditeur Big M cie, RCS, responsable de la publication, contact. Ajouts : hébergeur (resté vide sur l'ancienne page), propriété intellectuelle, renvoi vers les données personnelles. |
| `/politique-de-confidentialite/` | L'ancienne page était le modèle par défaut de WordPress (commentaires, Gravatar). Gardé : sa structure et le paragraphe « Contenu embarqué ». Mis à jour : candidatures franchise et recrutement, avis en restaurant, géolocalisation, statistiques, durées de conservation, prestataires, droits, CNIL. Déclarée comme page de confidentialité de WordPress. |
| `/cookies/` | Nouvelle (pas de page sur l'ancien site) : pas de publicité ni de mesure d'audience, donc pas de bandeau ; restaurant choisi gardé dans le navigateur, YouTube en confidentialité renforcée, cookies de l'administration |
| `/jeu-concours/` | « Pas de jeu concours en ce moment », renvoi vers les réseaux sociaux et bouton vers la carte. À remplacer par le règlement du prochain jeu. |

**À faire valider par le client :** hébergeur, durées de conservation (3 ans pour la franchise, 2 ans pour le recrutement, recommandations de la CNIL), responsable de la publication. Si un outil de mesure d'audience ou un reCAPTCHA est ajouté, mettre à jour les pages Confidentialité et Cookies (et prévoir un bandeau de consentement pour la mesure d'audience).

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
3. **Envoi des e-mails.** Vérifier qu'ils arrivent : l'expéditeur est une adresse du domaine. Un SMTP authentifié (boîte e-mail Hostinger du domaine) est conseillé : `docs/seo.md`, « Avant la mise en ligne ».
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

bigm-core règle la base des catégories sur `actualites` à son installation (**Réglages → Permaliens**), si elle était vide. La catégorie par défaut (« Non classé ») n'apparaît ni dans les pastilles ni sur les cartes.

Pastilles Tout / News / Presse dans un modèle du Theme Builder : widget **Code court** avec `[bigm_categories_articles]` (la page affichée est marquée). C'est le cas du modèle « Big M — Actualités (blog) » de la préprod.

Widget Articles d'Elementor Pro dans ce modèle :
- le lien « Lire l'article » porte un nom caché anglais (« Read more about … »), qu'Elementor Pro ne traduit pas. bigm-core le traduit en « Lire l'article : titre » (filtre `gettext_elementor-pro`). Si le texte du lien change dans le widget, ce nom caché doit commencer par le même texte : adapter le filtre de `inc/pages.php` ;
- les vignettes sont des liens : chaque image à la une doit avoir un texte de remplacement (le titre de l'article fait l'affaire) ;
- Elementor Pro pose `role="listitem"` sur les `<article>` : axe le signale (mineur, bonne pratique), sans réglage pour le retirer.

Les gabarits sont dans le thème : `home.php`, `category.php`, `single-post.php`. Ils appellent `bigm_page_actualites()` et `bigm_page_article()` de bigm-core. Un modèle du Theme Builder d'Elementor Pro, s'il vise ces pages, les remplace. Les cartes d'articles de l'accueil et de la page Actualités partagent le même balisage et `assets/css/articles.css`.

Les articles de l'ancien site se reprennent avec leurs images par **Outils → Reprise des articles** (`docs/seo.md`).

## Tester

```bash
wp eval-file tests/wp/test-bigm-contenus.php --user=IDENTIFIANT_ADMIN
```

Il fait 77 vérifications :

- sections de texte, chiffres, en-tête de page ;
- cartes, pastilles et articles des actualités ;
- chargement de l'habillage des formulaires et réglages des formulaires de la démo ;
- restaurants et fenêtre Commander, si le plugin est actif.
