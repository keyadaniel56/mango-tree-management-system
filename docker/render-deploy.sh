#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Docker/Render deployment helper for the Mango Tree School Management System.
#
# A normal Render deploy needs no local Docker at all: Render builds
# ./Dockerfile from the repository and runs docker/start.sh.
#
#   render blueprints validate ./render.yaml
#   render deploys create <srv-xxxxxxxx> --wait
#
# This script covers the other workflows with Render-compatible settings
# (PORT=10000, MySQL 5.7, health check /healthz):
#
#   ./docker/render-deploy.sh verify                     build + run exactly like Render
#   ./docker/render-deploy.sh build                      docker build only
#   ./docker/render-deploy.sh push <registry/image:tag>  build + push a prebuilt image
#   ./docker/render-deploy.sh deploy <srv-xxxxxxxx>      trigger a Render deploy
#   ./docker/render-deploy.sh blueprint                  validate render.yaml
# ---------------------------------------------------------------------------
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

IMAGE="${IMAGE:-mango-tree-management-system:render}"
PORT="${PORT:-10000}"
NETWORK="${NETWORK:-mango-tree-verify}"
DB_CONTAINER="${DB_CONTAINER:-mango-tree-db-verify}"
APP_CONTAINER="${APP_CONTAINER:-mango-tree-app-verify}"
DB_NAME="${DB_NAME:-schoolapi}"
DB_USER="${DB_USER:-school}"
DB_PASS="${DB_PASS:-school}"

usage() {
    cat <<'EOF'
Usage: docker/render-deploy.sh <command> [args]

  verify                     Build the image and run it against MySQL 5.7 with the
                             same settings Render uses (PORT=10000, /healthz).
  build                      Build the image only.
  push <registry/image:tag>  Build, tag and push a prebuilt image for
                             "Deploy an existing image" on Render.
  deploy <srv-xxxxxxxx>      Run `render deploys create <id> --confirm --wait`.
  blueprint                  Run `render blueprints validate ./render.yaml`.

Environment overrides: IMAGE, PORT, NETWORK, DB_CONTAINER, APP_CONTAINER, DB_NAME,
                      DB_USER, DB_PASS, DOCKER_BUILD_ARGS (e.g. "--network=host")
EOF
}

require_render_cli() {
    if ! command -v render >/dev/null 2>&1; then
        echo "The Render CLI is not installed: https://render.com/docs/cli" >&2
        exit 1
    fi
}

require_curl() {
    if ! command -v curl >/dev/null 2>&1; then
        echo "curl is required for the health check" >&2
        exit 1
    fi
}

build() {
    echo ">> docker build ${DOCKER_BUILD_ARGS:-} -t ${IMAGE} ."
    # shellcheck disable=SC2086 # DOCKER_BUILD_ARGS is intentionally word-split
    docker build ${DOCKER_BUILD_ARGS:-} -t "$IMAGE" .
}

# Laravel needs the "base64:" prefix; random_bytes(32) matches what Laravel's
# own `key:generate --show` produces for the AES-256-CBC cipher. artisan is not
# used because this app's console kernel requires a reachable database.
app_key() {
    docker run --rm "$IMAGE" \
        php -r 'echo "base64:".base64_encode(random_bytes(32));'
}

verify() {
    require_curl
    build

    docker network inspect "$NETWORK" >/dev/null 2>&1 || docker network create "$NETWORK" >/dev/null
    docker rm -f "$DB_CONTAINER" "$APP_CONTAINER" >/dev/null 2>&1 || true

    echo ">> starting MySQL 5.7 (${DB_CONTAINER})"
    docker run -d --name "$DB_CONTAINER" --network "$NETWORK" \
        -e MYSQL_DATABASE="$DB_NAME" \
        -e MYSQL_USER="$DB_USER" \
        -e MYSQL_PASSWORD="$DB_PASS" \
        -e MYSQL_ROOT_PASSWORD=root \
        mysql:5.7 >/dev/null

    echo ">> starting the application the way Render does (PORT=${PORT})"
    docker run -d --name "$APP_CONTAINER" --network "$NETWORK" \
        -p "127.0.0.1:${PORT}:${PORT}" \
        -e PORT="$PORT" \
        -e APP_ENV=production \
        -e APP_DEBUG=false \
        -e APP_KEY="$(app_key)" \
        -e DB_CONNECTION=mysql \
        -e DB_HOST="$DB_CONTAINER" \
        -e DB_PORT=3306 \
        -e DB_DATABASE="$DB_NAME" \
        -e DB_USERNAME="$DB_USER" \
        -e DB_PASSWORD="$DB_PASS" \
        -e SEED_DATABASE=auto \
        "$IMAGE" >/dev/null

    echo ">> waiting for http://127.0.0.1:${PORT}/healthz"
    for _ in $(seq 1 90); do
        if curl -fsS "http://127.0.0.1:${PORT}/healthz" >/dev/null 2>&1; then
            echo
            echo "OK - the container is healthy."
            echo "   App:  http://127.0.0.1:${PORT}"
            echo "   Logs: docker logs -f ${APP_CONTAINER}"
            echo "   Stop: docker rm -f ${APP_CONTAINER} ${DB_CONTAINER}"
            return 0
        fi
        sleep 2
    done

    echo "!! the health check never passed. Application logs:" >&2
    docker logs --tail 100 "$APP_CONTAINER" >&2 || true
    return 1
}

push() {
    local target="${1:-}"
    if [ -z "$target" ]; then
        echo "usage: docker/render-deploy.sh push <registry/image:tag>" >&2
        exit 2
    fi
    build
    docker tag "$IMAGE" "$target"
    docker push "$target"
    cat <<EOF

Image pushed. Deploy it with either:
  * Render Dashboard -> web service -> Settings -> Deploy an existing image
  * render deploys create <srv-xxxxxxxx> --image ${target} --wait
EOF
}

deploy() {
    local service="${1:-}"
    if [ -z "$service" ]; then
        echo "usage: docker/render-deploy.sh deploy <srv-xxxxxxxx>" >&2
        exit 2
    fi
    require_render_cli
    render deploys create "$service" --confirm --wait
}

blueprint() {
    require_render_cli
    render blueprints validate ./render.yaml
}

command="${1:-verify}"
shift || true

case "$command" in
    build) build ;;
    verify|run) verify ;;
    push) push "$@" ;;
    deploy) deploy "$@" ;;
    blueprint) blueprint ;;
    -h|--help|help) usage ;;
    *) usage; exit 2 ;;
esac