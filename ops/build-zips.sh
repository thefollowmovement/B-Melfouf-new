#!/usr/bin/env bash
# Fabrique les zips à téléverser dans l'admin WordPress, quand on n'a pas d'accès SSH :
#   dist/bigm-child.zip  → Apparence > Thèmes > Ajouter > Téléverser
#   dist/bigm-socle.zip  → Extensions > Ajouter > Téléverser
#   dist/bigm-core.zip   → Extensions > Ajouter > Téléverser
#   dist/bigm-box.zip    → Extensions > Ajouter > Téléverser (widget « Big M — Nos Box »)
#   dist/bigm-cookies.zip → Extensions > Ajouter > Téléverser (bandeau de consentement aux cookies)
#   dist/bigm-demo.zip   → Extensions > Ajouter > Téléverser (temporaire : contenu de démonstration)
#   dist/storelocatore.zip → Extensions > Ajouter > Téléverser (plugin StoreLocatore de l'agence,
#                          si son dépôt est cloné à côté : STORELOCATOR_DIR, par défaut ../storelocatore-wordpress)
#
#   ops/build-zips.sh
#
# Le dossier dist/ est ignoré par Git. GitHub Actions publie aussi ces zips
# à chaque push (onglet Actions > dernier passage > Artifacts).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/dist"

"$ROOT/tests/lint.sh"

rm -rf "$DIST"
mkdir -p "$DIST/build/bigm-socle"

(cd "$ROOT/wp-content/themes" && zip -qr -X "$DIST/bigm-child.zip" bigm-child -x '*/.DS_Store')
(cd "$ROOT/wp-content/plugins" && zip -qr -X "$DIST/bigm-core.zip" bigm-core -x '*/.DS_Store')
(cd "$ROOT/wp-content/plugins" && zip -qr -X "$DIST/bigm-box.zip" bigm-box -x '*/.DS_Store')
(cd "$ROOT/wp-content/plugins" && zip -qr -X "$DIST/bigm-cookies.zip" bigm-cookies -x '*/.DS_Store')

# Le socle est un mu-plugin ; en zip, il s'installe comme une extension classique.
cp "$ROOT/wp-content/mu-plugins/bigm-socle.php" "$DIST/build/bigm-socle/"
(cd "$DIST/build" && zip -qr -X "$DIST/bigm-socle.zip" bigm-socle)
# Démo : le code, plus les visuels du prototype copiés dans medias/ (pas dupliqués dans Git).
mkdir -p "$DIST/build/bigm-demo/medias"
cp "$ROOT/wp-content/plugins/bigm-demo/"*.php "$DIST/build/bigm-demo/"
cp "$ROOT/prototype/assets/img/"* "$ROOT/prototype/assets/video/"* "$DIST/build/bigm-demo/medias/"
(cd "$DIST/build" && zip -qr -X "$DIST/bigm-demo.zip" bigm-demo)

# StoreLocatore : dépôt séparé de l'agence (privé), empaqueté tel quel depuis sa version en cours.
STORELOCATOR_DIR="${STORELOCATOR_DIR:-$ROOT/../storelocatore-wordpress}"
if git -C "$STORELOCATOR_DIR" rev-parse HEAD > /dev/null 2>&1; then
	git -C "$STORELOCATOR_DIR" archive --format=zip --prefix=storelocatore/ -o "$DIST/storelocatore.zip" HEAD -- . ':(exclude)CLAUDE.md' ':(exclude).claude' ':(exclude).github'
	echo "StoreLocatore : $(git -C "$STORELOCATOR_DIR" log -1 --format='%h %s')"
fi

rm -rf "$DIST/build"

ls -l "$DIST"/*.zip
