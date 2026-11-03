# Étape 1 — Préprod Hostinger, WordPress neuf, thème enfant

Objectif : un WordPress neuf sur `preprod.bigm-burger.fr`, fermé aux moteurs de recherche, avec Hello Elementor, Elementor et le thème enfant `bigm-child` qui porte la charte.

Le code est dans ce dépôt. Les étapes 1 à 6 se font dans hPanel et prennent environ 30 minutes. Les étapes 7 et 8 sont des commandes.

> **Rien ne vient de l'ancien compte.** Pas de thème, pas d'extension, pas de `wp-content` copié depuis le compte infecté. Les médias et les articles seront repris à l'étape 7 du brief, après contrôle.

## 1. Créer le site sur le nouveau compte

hPanel du **nouveau** compte → **Sites web** → **Ajouter un site web** → WordPress.

- Domaine : `preprod.bigm-burger.fr`.
- Si le DNS n'est pas encore prêt, Hostinger propose un domaine temporaire (`…hostingersite.com`). On peut commencer dessus et changer de domaine ensuite.

## 2. Faire pointer `preprod` vers le nouveau compte

Dans la zone DNS de `bigm-burger.fr`, ajouter un enregistrement **A** `preprod` → l'adresse IP du nouvel hébergement (hPanel → Sites web → le site → **Tableau de bord**, encart « Détails du plan » ou « Pointer le domaine »).

La zone DNS se trouve là où pointent les serveurs de noms du domaine : l'ancien compte Hostinger ou le registraire. Ajouter un enregistrement DNS ne touche pas aux fichiers de l'ancien site. Le site en ligne actuel n'est pas affecté.

Une fois le DNS propagé, activer le **SSL** gratuit (hPanel → Sécurité → SSL).

## 3. PHP 8.2 ou plus

hPanel → Avancé → **Configuration PHP** : choisir **8.3** (8.2 au minimum).

## 4. Installer WordPress

Avec l'installateur d'hPanel :

- langue **Français** ;
- identifiant administrateur **nominatif** (ex. `prenom.agence`), jamais `admin` ;
- mot de passe généré, gardé dans le gestionnaire de mots de passe de l'agence ;
- e-mail de l'agence ;
- refuser les extensions et les modèles proposés, sauf **LiteSpeed Cache**.

## 5. Ouvrir l'accès SSH

hPanel → Avancé → **Accès SSH** : activer, puis ajouter la clé publique SSH de la personne qui déploie (ou celle de GitHub Actions, étape 9).

Noter l'hôte, le port (65002 en général) et l'utilisateur. Pour trouver le chemin de WordPress, se connecter en SSH et lancer `pwd` dans le dossier qui contient `wp-config.php`. C'est en général `/home/uXXXXXXXXX/domains/preprod.bigm-burger.fr/public_html`.

## 6. Renseigner les accès en local

```bash
cp ops/.env.example ops/.env.preprod
```

Remplir `SSH_HOST`, `SSH_PORT`, `SSH_USER`, `WP_PATH` et `WP_ADMIN` (l'identifiant de l'étape 4). Ce fichier est ignoré par Git. Il ne contient pas de mot de passe : la connexion se fait par clé.

## 7. Envoyer le thème, puis préparer le site

```bash
ops/deploy.sh
```

```bash
ops/provision.sh
```

`deploy.sh` lance d'abord les vérifications (`tests/lint.sh`), puis envoie `bigm-child` et `mu-plugins/bigm-*.php`, et vide les caches.

`provision.sh` peut être relancé sans risque. Il fait ceci :

| Quoi | Détail |
|---|---|
| `wp-config.php` | `WP_ENVIRONMENT_TYPE` = `staging`, `DISALLOW_FILE_EDIT`, `WP_AUTO_UPDATE_CORE` = `minor` |
| Indexation | « Demander aux moteurs de ne pas indexer » coché |
| Réglages | français, Europe/Paris, permaliens `/%postname%/`, commentaires fermés |
| Thème | Hello Elementor (parent) + Elementor, puis activation de `bigm-child` et de la charte dans le kit Elementor (`wp bigm kit`) |
| Sécurité | extension **Two Factor** ; dans le `.htaccess`, un bloc `Big M` qui bloque `xmlrpc.php` et un bloc `Big M preprod` qui ajoute `X-Robots-Tag: noindex` sur tous les fichiers |
| Nettoyage | suppression de l'article et de la page d'exemple |

## 8. À faire à la main dans l'admin

- **Elementor Pro** : télécharger le zip depuis le compte Elementor de l'agence, Extensions → Ajouter → Téléverser, puis Elementor → Licence. Le zip ne va **jamais** dans Git.
- **Double authentification** : chaque administrateur l'active dans son profil (Comptes → Profil → Two-Factor Options).
- **Un compte par personne**, en rôle Administrateur seulement si nécessaire.
- **Extensions ajoutées par Hostinger** (« Hostinger Tools », « Hostinger Easy Onboarding », IA…) : les désactiver puis les supprimer. On garde Elementor, Elementor Pro, LiteSpeed Cache et Two Factor.
- **Sauvegardes** : vérifier que les sauvegardes quotidiennes d'Hostinger sont actives sur le nouveau compte. Une copie externe sera ajoutée à l'étape 7 du brief.

## 9. Déploiement automatique (facultatif, recommandé)

À chaque push sur `main`, GitHub Actions vérifie le code et déploie sur la préprod (`.github/workflows/preprod.yml`). Tant que les secrets ne sont pas renseignés, le déploiement est simplement ignoré.

1. Créer une clé réservée au déploiement :

   ```bash
   ssh-keygen -t ed25519 -N "" -C "github-actions bigm preprod" -f bigm-preprod-deploy
   ```

2. Ajouter `bigm-preprod-deploy.pub` dans hPanel → Accès SSH.
3. Relever l'empreinte du serveur :

   ```bash
   ssh-keyscan -p 65002 HOTE_SSH
   ```

4. Dans GitHub → Settings → Secrets and variables → Actions, ajouter :

| Secret | Valeur |
|---|---|
| `PREPROD_SSH_HOST` | hôte SSH |
| `PREPROD_SSH_PORT` | port (65002) |
| `PREPROD_SSH_USER` | utilisateur SSH |
| `PREPROD_WP_PATH` | chemin de WordPress |
| `PREPROD_SSH_KEY` | contenu du fichier `bigm-preprod-deploy` (clé privée) |
| `PREPROD_SSH_KNOWN_HOSTS` | sortie de `ssh-keyscan` |

Supprimer ensuite la clé privée de l'ordinateur.

## 10. Vérifier

- Code source de la page d'accueil : `<meta name='robots' content='noindex, nofollow' />` et `width=device-width, initial-scale=1, viewport-fit=cover` (zoom autorisé).
- En-têtes HTTP : `X-Robots-Tag: noindex, nofollow` (`curl -I https://preprod.bigm-burger.fr`).
- `https://preprod.bigm-burger.fr/xmlrpc.php` répond 403.
- Barre d'admin : badge orange « STAGING · non indexé ».
- Elementor → Réglages du site → Couleurs globales : Titres, Texte secondaire, Texte, Boutons, puis Fond, Vignettes, Orange…
- Elementor → Réglages du site → Typographie globale : Fredoka et Figtree, groupe « Big M (auto-hébergées) ».
- Onglet Réseau du navigateur : aucune requête vers `fonts.googleapis.com` ni `fonts.gstatic.com`.

## Bascule en production (pour l'étape 8 du brief)

Le jour de la mise en ligne, sur l'installation qui devient le site :

1. `wp config set WP_ENVIRONMENT_TYPE production`.
2. Décocher « Demander aux moteurs de ne pas indexer » (`wp option update blog_public 1`).
3. Retirer du `.htaccess` le bloc `# BEGIN Big M preprod` … `# END Big M preprod`. Le bloc `Big M` (XML-RPC) reste.
4. Vérifier que la balise `noindex` et l'en-tête `X-Robots-Tag` ont disparu.
