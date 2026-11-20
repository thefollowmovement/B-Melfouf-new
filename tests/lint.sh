#!/usr/bin/env bash
# Vérifications rapides, à lancer avant chaque déploiement (ops/deploy.sh le fait) :
# syntaxe PHP et JS, cohérence de la charte, aucun appel à Google Fonts.
set -uo pipefail
cd "$(dirname "$0")/.."

status=0

while IFS= read -r -d '' file; do
	php -l "$file" > /dev/null || status=1
done < <(find wp-content tests -name '*.php' -print0)

if command -v node > /dev/null; then
	while IFS= read -r -d '' file; do
		node --check "$file" || status=1
	done < <(find wp-content -name '*.js' -print0)
fi

php tests/check-tokens.php || status=1

if grep -rIl 'fonts\.googleapis\.com\|fonts\.gstatic\.com' wp-content; then
	echo "Appel à Google Fonts trouvé : les polices doivent rester auto-hébergées (RGPD)." >&2
	status=1
fi

[ "$status" -eq 0 ] && echo "Vérifications OK."
exit "$status"
