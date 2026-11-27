#!/usr/bin/env bash
# Prépare la préprod Big M sur une installation WordPress neuve.
# À lancer après l'installation de WordPress dans hPanel et un premier ops/deploy.sh.
# Peut être relancé sans risque : chaque étape vérifie ce qui est déjà fait.
#
#   ops/provision.sh
#
# Réglages : ops/.env.preprod (voir ops/.env.example). Pas à la bascule en production :
# voir la section « Bascule » de docs/preprod-hostinger.md.
set -euo pipefail
. "$(dirname "$0")/lib.sh"
: "${WP_ADMIN:?WP_ADMIN manquant : identifiant WordPress d’un administrateur (ops/.env.preprod)}"

step() { printf '\n→ %s\n' "$1"; }

step "Vérification de l'installation"
wp_remote "core is-installed" || { echo "WordPress n'est pas installé dans $WP_PATH." >&2; exit 1; }
wp_remote "core version --extra"
remote "php -r 'exit(version_compare(PHP_VERSION, \"8.2\", \">=\") ? 0 : 1);'" \
	|| { echo "PHP 8.2 ou plus est requis : hPanel > Avancé > Configuration PHP." >&2; exit 1; }
remote "test -d wp-content/themes/bigm-child" \
	|| { echo "Thème bigm-child absent : lancer d'abord ops/deploy.sh." >&2; exit 1; }

step "wp-config.php : préprod, édition de fichiers coupée, mises à jour mineures automatiques"
wp_remote "config set WP_ENVIRONMENT_TYPE staging --type=constant"
wp_remote "config set DISALLOW_FILE_EDIT true --raw --type=constant"
wp_remote "config set WP_AUTO_UPDATE_CORE minor --type=constant"
wp_remote "option update blog_public 0"

step "Langue, fuseau horaire, formats, permaliens"
wp_remote "language core install fr_FR --activate"
wp_remote "option update timezone_string Europe/Paris"
wp_remote "option update date_format 'j F Y'"
wp_remote "option update time_format 'H:i'"
wp_remote "option update start_of_week 1"
wp_remote "rewrite structure '/%postname%/'"

step "Commentaires et pings fermés par défaut"
wp_remote "option update default_comment_status closed"
wp_remote "option update default_ping_status closed"

step "Hello Elementor, Elementor et le thème Big M"
wp_remote "theme is-installed hello-elementor" || wp_remote "theme install hello-elementor"
wp_remote "plugin is-installed elementor" || wp_remote "plugin install elementor"
wp_remote "plugin activate elementor"
wp_remote "theme activate bigm-child"
wp_remote "language theme install hello-elementor fr_FR" || true
wp_remote "language plugin install elementor fr_FR" || true
wp_remote "bigm kit --user='$WP_ADMIN'"

step "Double authentification des comptes (extension Two Factor)"
wp_remote "plugin is-installed two-factor" || wp_remote "plugin install two-factor"
wp_remote "plugin activate two-factor"

step ".htaccess : permaliens, XML-RPC bloqué avant WordPress, fichiers de la préprod non indexés"
remote "wp eval-file -" <<'PHP'
<?php
require_once ABSPATH . 'wp-admin/includes/misc.php';
// En ligne de commande, WordPress ne sait pas que le serveur (LiteSpeed) lit le .htaccess.
add_filter( 'got_rewrite', '__return_true' );
flush_rewrite_rules( true );
$file = ABSPATH . '.htaccess';
// Permanent : XML-RPC bloqué avant même de charger WordPress.
$done = insert_with_markers(
	$file,
	'Big M',
	array(
		'<Files xmlrpc.php>',
		'  Order allow,deny',
		'  Deny from all',
		'</Files>',
	)
);
// Préprod seulement : images et PDF non indexés non plus. Bloc à retirer à la bascule.
$done = insert_with_markers(
	$file,
	'Big M preprod',
	array(
		'<IfModule mod_headers.c>',
		'  Header set X-Robots-Tag "noindex, nofollow"',
		'</IfModule>',
	)
) && $done;
WP_CLI::log( $done ? '.htaccess mis à jour.' : '.htaccess non modifiable : ajouter les blocs à la main (docs/preprod-hostinger.md).' );
PHP

step "Contenus d'exemple de WordPress supprimés"
for slug in hello-world bonjour-tout-le-monde sample-page page-d-exemple; do
	ids="$(wp_remote "post list --post_type=post,page --name=$slug --post_status=any --field=ID" || true)"
	if [ -n "$ids" ]; then
		wp_remote "post delete $(echo "$ids" | tr '\n' ' ') --force" > /dev/null && echo "  supprimé : $slug"
	fi
done

step "Extensions installées"
wp_remote "plugin list --fields=name,status,version"
cat <<'TXT'

Garder le moins d'extensions possible : Elementor, Elementor Pro, LiteSpeed Cache et Two Factor.
Les extensions ajoutées par l'installateur d'Hostinger peuvent être retirées (docs/preprod-hostinger.md).
Reste à faire à la main : téléverser Elementor Pro et activer sa licence, activer la double
authentification dans le profil de chaque administrateur.
TXT
