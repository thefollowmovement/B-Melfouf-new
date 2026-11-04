#!/usr/bin/env bash
# Fabrique les zips à téléverser dans l'admin WordPress, quand on n'a pas d'accès SSH :
#   dist/bigm-child.zip  → Apparence > Thèmes > Ajouter > Téléverser
#   dist/bigm-socle.zip  → Extensions > Ajouter > Téléverser
#   dist/bigm-core.zip   → Extensions > Ajouter > Téléverser
#   dist/bigm-demo.zip   → Extensions > Ajouter > Téléverser (temporaire : contenu de démonstration)
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

# Le socle est un mu-plugin ; en zip, il s'installe comme une extension classique.
cp "$ROOT/wp-content/mu-plugins/bigm-socle.php" "$DIST/build/bigm-socle/"
(cd "$DIST/build" && zip -qr -X "$DIST/bigm-socle.zip" bigm-socle)
# Démo : le code, plus les visuels du prototype copiés dans medias/ (pas dupliqués dans Git).
mkdir -p "$DIST/build/bigm-demo/medias"
cp "$ROOT/wp-content/plugins/bigm-demo/"*.php "$DIST/build/bigm-demo/"
cp "$ROOT/prototype/assets/img/"* "$ROOT/prototype/assets/video/"* "$DIST/build/bigm-demo/medias/"
(cd "$DIST/build" && zip -qr -X "$DIST/bigm-demo.zip" bigm-demo)

rm -rf "$DIST/build"

ls -l "$DIST"/*.zip
