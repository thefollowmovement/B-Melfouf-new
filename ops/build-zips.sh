#!/usr/bin/env bash
# Fabrique les zips à téléverser dans l'admin WordPress, quand on n'a pas d'accès SSH :
#   dist/bigm-child.zip  → Apparence > Thèmes > Ajouter > Téléverser
#   dist/bigm-socle.zip  → Extensions > Ajouter > Téléverser
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

# Le socle est un mu-plugin ; en zip, il s'installe comme une extension classique.
cp "$ROOT/wp-content/mu-plugins/bigm-socle.php" "$DIST/build/bigm-socle/"
(cd "$DIST/build" && zip -qr -X "$DIST/bigm-socle.zip" bigm-socle)
rm -rf "$DIST/build"

ls -l "$DIST"/*.zip
