# Étape 1 — Préprod Hostinger, WordPress neuf, thème enfant

Objectif : un WordPress neuf sur `preprod.bigm-burger.fr`, fermé aux moteurs de recherche, avec Hello Elementor, Elementor et le thème enfant `bigm-child` qui porte la charte.

> **Rien ne vient de l'ancien site.** Pas de thème, pas d'extension, pas de `wp-content` copié. Les médias et les articles seront repris à l'étape 7 du brief, après contrôle.

## Où est la préprod

| | |
|---|---|
| Adresse | `https://preprod.bigm-burger.fr` |
| Compte Hostinger | `u296589459` (offre Business du client, partagée avec l'agence) |
| Dossier | `/home/u296589459/domains/bigm-burger.fr/public_html/preprod` |
| Type | sous-domaine de `bigm-burger.fr` |

Le dossier est placé **sous celui du site actuel**. Hostinger n'accepte pas d'autre emplacement pour un sous-domaine, et l'accès partagé ne permet pas de créer un site à part. Le `.htaccess` de l'ancien site s'applique donc aussi à la préprod, notamment le pare-feu Wordfence qu'il fait exécuter avant chaque page (`auto_prepend_file`). L'extension **Big M — socle** neutralise cette règle dans le `.htaccess` de la préprod (bloc `Big M`), au premier passage d'un administrateur.

## Mise en place sans SSH (connecteur Hostinger + admin WordPress)

C'est la voie utilisée pour la première installation.

1. **Installer WordPress dans hPanel** : Installation automatique → WordPress, sur `preprod.bigm-burger.fr`, en français. L'API d'Hostinger ne le fait pas sur ce compte partagé : elle accepte la demande, puis l'abandonne.
2. **Extensions** : Elementor (et Elementor Pro). Two Factor peut être installée par le connecteur Hostinger, qui sait installer, activer et supprimer des extensions sur ce compte.
3. **Fabriquer les zips** : `ops/build-zips.sh`, ou les télécharger depuis GitHub → Actions → dernier passage du workflow « Préprod » → Artifacts → `bigm-zips`.
4. **Extensions → Ajouter → Téléverser** : `bigm-socle.zip`, puis **Activer**.
5. **Apparence → Thèmes → Ajouter → Téléverser** : `bigm-child.zip`, puis **Activer**.
6. Recharger une page de l'admin. Deux messages confirment ce qui a été fait :
   - **socle** :
     - fuseau Europe/Paris, dates en français, permaliens `/%postname%/` ;
     - commentaires fermés, contenus d'exemple supprimés ;
     - `.htaccess` : XML-RPC bloqué, pare-feu de l'ancien site coupé, fichiers non indexés ;
   - **thème** : charte appliquée à Elementor (couleurs et polices globales, largeur 1240 px, points de rupture 760 / 960 px).

Pour une mise à jour du thème ou du socle, téléverser le nouveau zip au même endroit : WordPress propose de remplacer la version installée.

Sans `wp-config.php` modifié, le socle reconnaît la préprod à son adresse (`preprod.…`, `staging.…`, `….hostingersite.com`). Il coupe aussi l'éditeur de fichiers de l'admin (`DISALLOW_FILE_EDIT`).

## État au 4 novembre 2026

| Élément | État |
|---|---|
| WordPress | installé par hPanel, en français ; administrateur `TFM` (anis.b@thefollowmovement.com) |
| Thème | Big M (`bigm-child` 0.1.0) actif, parent Hello Elementor 3.5.2 |
| Extensions actives | Big M — socle 0.2.0, Elementor 4.3.6, Elementor Pro 4.3.2, Two Factor 0.17.0, Hostinger Tools |
| Charte dans Elementor | appliquée : 11 couleurs, Fredoka et Figtree, 1240 px, points de rupture 960 / 760 px |
| `.htaccess` | blocs `Big M` et `Big M preprod` en place |

## À faire à la main dans l'admin

- **Elementor Pro** : télécharger le zip depuis le compte Elementor de l'agence, Extensions → Ajouter → Téléverser, puis Elementor → Licence. Le zip ne va **jamais** dans Git.
- **Double authentification** : chaque administrateur l'active dans son profil (Comptes → Profil → Two-Factor Options).
- **Un compte par personne**, en rôle Administrateur seulement si nécessaire.
- **Extensions ajoutées par Hostinger** (« Hostinger AI », « Hostinger Hub », « Hostinger Reach », doublons inactifs) : à supprimer. On garde Elementor, Elementor Pro, Hostinger Tools, Two Factor et Big M — socle. LiteSpeed Cache sera installé à l'étape 7 (performance), pour ne pas gêner le développement.
- **Sauvegardes** : vérifier que les sauvegardes quotidiennes d'Hostinger sont actives. Une copie externe sera ajoutée à l'étape 7 du brief.

## Vérifier

- Code source de la page d'accueil : `<meta name='robots' content='noindex, nofollow' />` et `width=device-width, initial-scale=1, viewport-fit=cover` (zoom autorisé).
- En-têtes HTTP : `X-Robots-Tag: noindex, nofollow`.
- `https://preprod.bigm-burger.fr/xmlrpc.php` répond 403.
- Barre d'admin : badge orange « STAGING · non indexé ».
- Elementor → Réglages du site → Couleurs globales : Titres, Texte secondaire, Texte, Boutons, puis Fond, Vignettes, Orange…
- Elementor → Réglages du site → Typographie globale : Fredoka et Figtree, groupe « Big M (auto-hébergées) ».
- Onglet Réseau du navigateur : aucune requête vers `fonts.googleapis.com` ni `fonts.gstatic.com`.
- Le `.htaccess` de la préprod contient les blocs `Big M` et `Big M preprod`.

## Plus tard : déploiement par SSH (recommandé pour les étapes suivantes)

Le téléversement de zips suffit pour démarrer. Pour déployer à chaque push, il faut l'accès SSH du compte :

1. hPanel → Avancé → **Accès SSH** : l'activer (le propriétaire du compte peut devoir le faire) et ajouter une clé publique.
2. `cp ops/.env.example ops/.env.preprod`, puis remplir hôte, port (65002), utilisateur, `WP_PATH` (le dossier ci-dessus) et `WP_ADMIN` (`TFM`).
3. `ops/deploy.sh` envoie le thème et le socle (en mu-plugin ; s'il est aussi installé en extension, il ne se charge qu'une fois). `ops/provision.sh` refait les réglages en ligne de commande. Il peut être relancé sans risque.
4. Pour GitHub Actions, créer une clé réservée :

   ```bash
   ssh-keygen -t ed25519 -N "" -C "github-actions bigm preprod" -f bigm-preprod-deploy
   ```

   Ajouter `bigm-preprod-deploy.pub` dans hPanel, puis ces secrets dans GitHub → Settings → Secrets and variables → Actions :

| Secret | Valeur |
|---|---|
| `PREPROD_SSH_HOST` | hôte SSH |
| `PREPROD_SSH_PORT` | port (65002) |
| `PREPROD_SSH_USER` | utilisateur SSH |
| `PREPROD_WP_PATH` | `/home/u296589459/domains/bigm-burger.fr/public_html/preprod` |
| `PREPROD_SSH_KEY` | contenu du fichier `bigm-preprod-deploy` (clé privée) |
| `PREPROD_SSH_KNOWN_HOSTS` | sortie de `ssh-keyscan -p 65002 HOTE_SSH` |

Supprimer ensuite la clé privée de l'ordinateur. Chaque push sur `main` déploiera alors la préprod.

## Point d'attention sur le site actuel

Le dossier `public_html` de `bigm-burger.fr` contient encore les restes de la migration Duplicator du 9 avril 2024. Parmi eux, un export complet de la base de données (`20240409_bigm_…_database.sql`, 41 Mo), les listes de fichiers et l'installateur. Rien dans le `.htaccess` n'en bloque le téléchargement. Il faut les sortir du dossier public, ou les supprimer, depuis le gestionnaire de fichiers d'hPanel.

## Bascule en production

Procédure complète, avec sauvegardes, commandes et retour arrière : [`bascule.md`](bascule.md). En résumé, le socle repasse de lui-même en production (plus de `noindex`, bloc `Big M preprod` du `.htaccess` vidé au premier passage d'un administrateur) ; il reste à décocher « Demander aux moteurs de ne pas indexer » dans Réglages → Lecture et à passer `WP_ENVIRONMENT_TYPE` à `production` s'il est défini.
