#!/usr/bin/env bash
# Envoie le thème enfant, le plugin bigm-core et les mu-plugins Big M sur la préprod, par SSH.
#
#   ops/deploy.sh
#
# Réglages : ops/.env.preprod (voir ops/.env.example).
# Lancé aussi par GitHub Actions à chaque push sur main (.github/workflows/preprod.yml).
set -euo pipefail
. "$(dirname "$0")/lib.sh"

"$ROOT/tests/lint.sh"

echo "→ Thème bigm-child"
# Copié dans un dossier à côté, puis échangé d'un coup : le site ne sert jamais
# un thème à moitié copié, et les fichiers supprimés du dépôt disparaissent du serveur.
tar -C "$ROOT/wp-content/themes" -czf - bigm-child | "${SSH[@]}" "set -e
	cd '$WP_PATH/wp-content/themes'
	rm -rf bigm-child.new bigm-child.old
	mkdir bigm-child.new
	tar -xzf - -C bigm-child.new --strip-components=1
	if [ -d bigm-child ]; then mv bigm-child bigm-child.old; fi
	mv bigm-child.new bigm-child
	rm -rf bigm-child.old"

echo "→ Plugin bigm-core"
tar -C "$ROOT/wp-content/plugins" -czf - bigm-core | "${SSH[@]}" "set -e
	cd '$WP_PATH/wp-content/plugins'
	rm -rf bigm-core.new bigm-core.old
	mkdir bigm-core.new
	tar -xzf - -C bigm-core.new --strip-components=1
	if [ -d bigm-core ]; then mv bigm-core bigm-core.old; fi
	mv bigm-core.new bigm-core
	rm -rf bigm-core.old"

echo "→ mu-plugins"
# Seulement nos fichiers bigm-*.php : ceux d'Hostinger restent en place.
(cd "$ROOT/wp-content/mu-plugins" && tar -czf - bigm-*.php) | "${SSH[@]}" "set -e
	mkdir -p '$WP_PATH/wp-content/mu-plugins'
	tar -xzf - -C '$WP_PATH/wp-content/mu-plugins'"

echo "→ Vidage des caches (Elementor, LiteSpeed)"
wp_remote "elementor flush-css" > /dev/null 2>&1 || true
wp_remote "litespeed-purge all" > /dev/null 2>&1 || true

echo "Déployé sur $SSH_HOST:$WP_PATH"
