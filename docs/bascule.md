# Bascule : la préprod devient bigm-burger.fr

Étape 8 du brief, seconde partie. La nouvelle installation (`public_html/preprod`, base de données de la préprod) est copiée à la racine du domaine, avec sa propre base. L'ancien site est sorti du dossier web, gardé quelques semaines, puis supprimé. Le domaine et le serveur ne changent pas : pas de DNS à modifier.

Cette procédure demande un accès **SSH** avec WP-CLI (offres Hostinger Premium et Business). Le remplacement des adresses doit passer par WP-CLI, qui sait corriger les données sérialisées de WordPress et d'Elementor : jamais de rechercher-remplacer à la main dans phpMyAdmin.

Compter une heure, à un moment calme (le site affiche l'ancienne version jusqu'à l'étape 3, puis la nouvelle).

## Avant le jour J

- [ ] Recette terminée (`docs/recette.md`), corrections faites dans l'admin, essais sur téléphone.
- [ ] Contenus définitifs en place ; produits et restaurants de démonstration supprimés (**Outils → Démo Big M → Supprimer**, les pages et les médias encore utilisés sont gardés), puis extension `bigm-demo` désactivée et supprimée.
- [ ] Plus aucun texte entre crochets `[…]` (rechercher « [ » dans les pages et produits).
- [ ] **Reprise des articles** relancée sur la préprod, pour les articles publiés depuis la première reprise (les articles déjà repris ne sont pas recréés).
- [ ] LiteSpeed Cache installé et réglé (`docs/seo.md`).
- [ ] Liens de commande de chaque restaurant saisis (**Restaurants → Liens de commande**), lien Commander par défaut définitif, adresses e-mail de réception des formulaires confirmées, SMTP configuré.
- [ ] Accès prêts : SSH, Google Search Console du domaine, comptes administrateurs nominatifs avec double authentification.
- [ ] Préprod gardée après la bascule : **Réglages Big M → Google → plafond à 0** (sinon elle interroge Google chaque nuit avec la même clé que la production et consomme le même quota gratuit). Sur la production, vérifier la section Google (plafond 900, prochain passage) et ajouter une tâche cron Hostinger sur `wp-cron.php` (toutes les heures).
- [ ] Accès MCP coupés :
  - supprimer les mots de passe d'application créés pour Claude (Utilisateurs → Profil → Mots de passe d'application) ; désactiver le MCP dans Elementor ne suffit pas ;
  - désactiver **Elementor → Elementor MCP** ;
  - retirer `big-m-elementor` et `big-m-wordpress` de la configuration de Claude Code (`claude mcp remove`).

## Jour J

Les commandes se lancent en SSH, depuis `~/domains/bigm-burger.fr`. Remplacer `AAAAMMJJ` par la date du jour.

### 1. Sauvegarder

1. hPanel → **Sauvegardes** : créer une sauvegarde complète (fichiers et bases).
2. Copies locales des deux bases :

   ```bash
   wp --path=public_html db export ~/ancien-site-AAAAMMJJ.sql
   ```

   ```bash
   wp --path=public_html/preprod db export ~/preprod-AAAAMMJJ.sql
   ```

### 2. Créer la base de production

hPanel → **Bases de données → MySQL** : créer une base et un utilisateur, noter le mot de passe dans le gestionnaire de mots de passe de l'agence (jamais dans Git ni par e-mail). La préprod garde sa base : elle reste un site d'essai indépendant.

### 3. Sortir l'ancien site, mettre le nouveau

1. Déplacer l'ancien site hors du dossier web. Tout part, sauf le dossier `preprod`, y compris les fichiers cachés (`.htaccess`, `.user.ini` de Wordfence) :

   ```bash
   mkdir ~/ancien-site-AAAAMMJJ && find public_html -mindepth 1 -maxdepth 1 ! -name preprod -exec mv {} ~/ancien-site-AAAAMMJJ/ \;
   ```

2. Copier la nouvelle installation à la racine, sans les caches :

   ```bash
   rsync -a --exclude=wp-content/cache --exclude=wp-content/litespeed public_html/preprod/ public_html/
   ```

3. Dans `public_html/wp-config.php` :
   - nom, utilisateur et mot de passe de la **nouvelle base** ;
   - si `WP_ENVIRONMENT_TYPE` est défini, le mettre à `production` ;
   - nouvelles clés de sécurité : `wp --path=public_html config shuffle-salts`.

### 4. Remplir la base de production

```bash
wp --path=public_html db import ~/preprod-AAAAMMJJ.sql
```

```bash
wp --path=public_html search-replace 'https://preprod.bigm-burger.fr' 'https://bigm-burger.fr' --all-tables-with-prefix --precise --report-changed-only
```

```bash
wp --path=public_html elementor replace_urls https://preprod.bigm-burger.fr https://bigm-burger.fr
```

```bash
wp --path=public_html option update blog_public 1
```

```bash
wp --path=public_html rewrite flush --hard && wp --path=public_html elementor flush_css && wp --path=public_html cache flush
```

### 5. Premier passage dans l'admin

Se connecter sur `https://bigm-burger.fr/wp-admin/` (mêmes comptes que la préprod). Le socle reconnaît la production :
- il réécrit le `.htaccess` : XML-RPC bloqué, plus de bloc « Big M preprod » ;
- son compte rendu s'affiche en haut de l'admin.

Puis :
- **LiteSpeed Cache → Purger tout**, et vider le CDN Hostinger (hPanel) ;
- **Réglages → Lecture** : vérifier que « Demander aux moteurs de recherche de ne pas indexer ce site » est **décoché**.

### 6. Vérifier

- [ ] Plus de `noindex`.

  ```bash
  curl -sI https://bigm-burger.fr/ | grep -i robots
  ```

  Rien ne doit s'afficher. Vérifier aussi que la source de la page d'accueil ne contient pas `noindex`.
- [ ] Anciennes adresses : `/nos-restaurants/`, `/news/`, `/presse/`, `/contact/`, `/app/`, `/concours/`, `/privacy-policy/` et un ancien article renvoient vers la bonne page (`docs/seo.md`).
- [ ] Accueil, carte, une catégorie, une fiche produit, restaurants (recherche, carte, une fiche), actualités, filtres News / Presse, pages légales.
- [ ] Une candidature franchise et une candidature recrutement (avec CV) : e-mails reçus, entrées dans WPForms.
- [ ] Certificat SSL valide sur `bigm-burger.fr` et `www.bigm-burger.fr` ; `www` renvoie vers l'adresse sans `www` (ou l'inverse, mais une seule).
- [ ] Lighthouse mobile sur l'accueil et la carte (LCP < 2,5 s, CLS < 0,1).

### 7. Services extérieurs

- **Google Search Console** :
  - propriété `bigm-burger.fr` ;
  - déclarer `https://bigm-burger.fr/wp-sitemap.xml` ;
  - inspecter l'accueil et demander l'indexation.
- **Clés liées au domaine** (reCAPTCHA si utilisé, cartes, applis) : ajouter `bigm-burger.fr`.
- **HSTS** : l'activer dans hPanel une fois le certificat vérifié.

## Variante sans SSH : Duplicator Pro

Même résultat que les étapes 1 à 5, avec l'extension Duplicator Pro (licence de l'agence), le gestionnaire de fichiers et les bases de données de hPanel. Duplicator remplace les adresses en tenant compte des données sérialisées ; Elementor repasse derrière avec son propre outil.

**La veille, sur la préprod :**

1. Faire la liste « Avant le jour J » ci-dessus.
2. Installer et activer Duplicator Pro.
3. **Duplicator Pro → Sauvegardes → Ajouter** :
   - nom : `bigm-AAAAMMJJ` ;
   - filtres de fichiers : exclure `wp-content/cache`, `wp-content/litespeed` et `wp-content/updraft` ;
   - installeur : un **mot de passe** (le fichier d'installation sera public quelques minutes) ;
   - construire. L'archive et l'installeur sont rangés dans `public_html/preprod/wp-content/backups-dup-pro/`.

**Le jour J :**

1. hPanel → **Sauvegardes** : sauvegarde complète (fichiers et bases).
2. hPanel → **Bases de données → MySQL** : nouvelle base et nouvel utilisateur, mot de passe dans le gestionnaire de l'agence. La préprod garde sa base.
3. **Gestionnaire de fichiers** : copier l'archive et l'installeur de `public_html/preprod/wp-content/backups-dup-pro/` vers `public_html/`. Pas de téléchargement : tout reste sur le serveur.
4. **Sortir l'ancien site** (le site est coupé à partir d'ici) :
   - créer `ancien-site-AAAAMMJJ` **à côté** de `public_html` (dans `domains/bigm-burger.fr/`), jamais dedans ;
   - afficher les fichiers cachés ;
   - déplacer d'abord `.user.ini` seul, puis attendre 5 minutes : LiteSpeed garde la configuration PHP en mémoire, et le pare-feu Wordfence qu'elle charge serait introuvable ;
   - déplacer ensuite tout le reste de `public_html` **sauf** `preprod`, l'archive et l'installeur (`.htaccess` compris).
5. Ouvrir `https://bigm-burger.fr/` suivi du nom de l'installeur (`…installer.php`) :
   - mot de passe de l'installeur ;
   - base : hôte `localhost`, la **nouvelle base**, son utilisateur et son mot de passe ;
   - mode d'extraction : **extraire par-dessus les fichiers actuels**. Jamais « supprimer tous les fichiers » : le dossier `preprod` est dans `public_html` et partirait avec ;
   - adresse : nouvelle `https://bigm-burger.fr`, ancienne `https://preprod.bigm-burger.fr` (détectées) ;
   - cocher la création de nouvelles clés d'authentification (`wp-config.php`) si l'installeur le propose ;
   - à la fin, se connecter (mêmes comptes que la préprod) et accepter la suppression des fichiers d'installation.
6. Dans l'admin de `bigm-burger.fr` :
   - **Elementor → Outils → Remplacer l'URL** : `https://preprod.bigm-burger.fr` → `https://bigm-burger.fr`, puis **Régénérer les fichiers et les données** ;
   - **Réglages → Lecture** : « Demander aux moteurs de recherche de ne pas indexer ce site » **décoché** (le réglage vient de la base de la préprod) ;
   - **Réglages → Permaliens** : Enregistrer ;
   - le compte rendu du socle s'affiche en haut de l'admin (`.htaccess` réécrit pour la production) ;
   - vider le CDN Hostinger (hPanel).
7. Vérifications de l'étape 6 ci-dessus, puis **Réglages Big M → Google → plafond à 0** sur la préprod.

**Retour arrière** : dans le gestionnaire de fichiers, sortir le nouveau site de `public_html` (sauf `preprod`) vers un dossier `nouveau-site-AAAAMMJJ`, et remettre le contenu de `ancien-site-AAAAMMJJ`. L'ancien site retrouve sa base, qui n'a pas été touchée.

## Si quelque chose ne va pas

Revenir à l'ancien site prend une minute, puisque rien n'a été supprimé :

```bash
mkdir ~/nouveau-site-AAAAMMJJ && find public_html -mindepth 1 -maxdepth 1 ! -name preprod -exec mv {} ~/nouveau-site-AAAAMMJJ/ \; && mv ~/ancien-site-AAAAMMJJ/* ~/ancien-site-AAAAMMJJ/.[!.]* public_html/
```

Puis vider le cache LiteSpeed et le CDN.

## Après la bascule

- **Les premières semaines** : surveiller le journal des adresses introuvables (Réglages Big M, en bas) et ajouter des redirections ; suivre la couverture dans Search Console.
- **Ancien site** : le dossier `~/ancien-site-AAAAMMJJ` ne doit plus jamais être servi (l'ancien compte avait été infecté). Le garder 30 jours au plus, hors du dossier web, puis le supprimer avec son ancienne base.
- **Préprod** : la garder comme site d'essai (toujours en `noindex` grâce au socle), avec sa propre base. Pour y tester une mise à jour, copier d'abord la base de production vers elle. Sinon, la supprimer (dossier, sous-domaine et base).
- **Mises à jour** : les zips (`ops/build-zips.sh`) ou `ops/deploy.sh` visent la préprod. Pour la production, téléverser les zips dans l'admin de `bigm-burger.fr`, après essai sur la préprod.
