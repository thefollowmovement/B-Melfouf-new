# Fonctions communes aux scripts ops/ (à sourcer, pas à lancer).
# Lit ops/.env.preprod si présent ; sinon les variables viennent de l'environnement
# (cas de GitHub Actions).

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$ROOT/ops/.env.preprod}"

if [ -f "$ENV_FILE" ]; then
	# shellcheck disable=SC1090
	. "$ENV_FILE"
fi

: "${SSH_HOST:?SSH_HOST manquant : copier ops/.env.example en ops/.env.preprod et le remplir}"
: "${SSH_USER:?SSH_USER manquant (ops/.env.preprod)}"
: "${WP_PATH:?WP_PATH manquant (ops/.env.preprod)}"
SSH_PORT="${SSH_PORT:-65002}"

SSH=(ssh -p "$SSH_PORT" -o BatchMode=yes -o ConnectTimeout=20 "$SSH_USER@$SSH_HOST")

# Lance une commande dans le dossier WordPress du serveur.
remote() {
	"${SSH[@]}" "cd '$WP_PATH' && $*"
}

# Lance une commande WP-CLI sur le serveur.
wp_remote() {
	remote "wp $*"
}
