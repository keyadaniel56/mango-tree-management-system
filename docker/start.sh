#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Container entrypoint for the Mango Tree School Management System.
#
# Works unchanged in both supported environments:
#   * local development: `docker compose up --build` (see docker-compose.yml)
#   * Render: the platform injects PORT and forwards HTTP traffic to it
#     (https://render.com/docs/docker)
# ---------------------------------------------------------------------------
set -euo pipefail

APP_ROOT=/var/www/html
cd "$APP_ROOT"

log() { printf '[start] %s\n' "$*"; }

# ---------------------------------------------------------------------------
# 1. Render (and every other container platform) gives the container a port to
#    listen on. Apache defaults to 80, so rewrite its configuration.
# ---------------------------------------------------------------------------
PORT="${PORT:-10000}"
log "Apache will listen on port ${PORT}"

sed -ri "s/^Listen[[:space:]]+[0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#" \
    /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------------
# 2. Laravel application key.
#    Prefer the existing .env (local development), otherwise fall back to the
#    APP_KEY environment variable (Render). Render's `generateValue: true`
#    produces a raw base64 value, which Laravel only accepts for AES-256-CBC
#    when it carries the `base64:` prefix.
# ---------------------------------------------------------------------------
if [ -z "${APP_KEY:-}" ] && [ -f .env ]; then
    APP_KEY="$(sed -n 's/^APP_KEY=//p' .env | tail -n 1 | tr -d '"' | tr -d "'")"
    if [ "$APP_KEY" = "null" ]; then
        APP_KEY=""
    fi
    export APP_KEY
fi

if [ -z "${APP_KEY:-}" ]; then
    log "APP_KEY is not set - generating one for this boot (set APP_KEY to keep sessions stable)"
    # Deliberately not `php artisan key:generate`: this application's console
    # kernel queries the database while booting (App\Console\Kernel::schedule()),
    # and MySQL may still be starting. random_bytes(32) + base64 is exactly what
    # Laravel's own key generator produces for AES-256-CBC.
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
elif [ "${APP_KEY#base64:}" = "${APP_KEY}" ] && [ "${#APP_KEY}" -eq 44 ]; then
    log "APP_KEY has no base64: prefix - adding it"
    APP_KEY="base64:${APP_KEY}"
    export APP_KEY
fi

# ---------------------------------------------------------------------------
# 3. APP_URL on Render: the platform injects RENDER_EXTERNAL_URL with the live
#    onrender.com address (including the per-service random slug). Use it so
#    Laravel generates absolute links on the real host even if a stale APP_URL
#    was baked into the environment.
# ---------------------------------------------------------------------------
if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
    log "Using APP_URL=${APP_URL}"
fi

# ---------------------------------------------------------------------------
# 4. Composer dependencies. The image installs them at build time; this is a
#    safety net for the case where an empty vendor/ volume covers the image.
# ---------------------------------------------------------------------------
if [ ! -f vendor/autoload.php ]; then
    log "vendor/ is missing - installing Composer dependencies (rebuild the image to avoid this)"
    composer install --no-interaction --no-scripts --no-dev --prefer-dist --optimize-autoloader
fi

# Laravel 5.5 cannot parse the Composer 2 installed.json format.
php /usr/local/bin/patch-package-manifest.php "$APP_ROOT"

# ---------------------------------------------------------------------------
# 5. Render starts all services of a Blueprint in parallel, so MySQL may still
#    be booting when this container starts.
# ---------------------------------------------------------------------------
if [ -n "${DB_HOST:-}" ]; then
    log "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}"
    for attempt in $(seq 1 60); do
        if php docker/db-ready.php >/dev/null 2>&1; then
            log "MySQL is accepting connections"
            break
        fi
        if [ "$attempt" -eq 60 ]; then
            log "MySQL is still unreachable - continuing anyway, migrations may fail"
        fi
        sleep 2
    done
fi

# ---------------------------------------------------------------------------
# 6. Runtime directories. A Render disk can be mounted over storage/, which
#    hides the directories created at build time.
# ---------------------------------------------------------------------------
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/framework/testing storage/logs bootstrap/cache

if [ ! -e public/storage ]; then
    php artisan storage:link
fi

chown -R www-data:www-data storage bootstrap/cache

# ---------------------------------------------------------------------------
# 7. Migrations are idempotent, so they run on every boot.
# ---------------------------------------------------------------------------
log "Running database migrations"
php artisan migrate --force

# ---------------------------------------------------------------------------
# 8. Seeding happens exactly once. The `users` table is the marker:
#    SEED_DATABASE=auto (default) seeds only an empty database,
#    SEED_DATABASE=true seeds on every boot, SEED_DATABASE=false never seeds.
# ---------------------------------------------------------------------------
SEED_DATABASE="${SEED_DATABASE:-auto}"
if [ "$SEED_DATABASE" != "false" ]; then
    user_rows="$(php docker/db-table-count.php users 2>/dev/null || echo unknown)"
    if [ "$user_rows" = "0" ] || [ "$SEED_DATABASE" = "true" ]; then
        log "Seeding the database (users=${user_rows})"
        php artisan db:seed --force
    else
        log "Database already seeded (users=${user_rows}) - skipping db:seed"
    fi
fi

# ---------------------------------------------------------------------------
# 9. Passport OAuth keys and clients. Keys live in storage/, so they have to be
#    regenerated whenever the filesystem is recreated. Client rows are kept.
# ---------------------------------------------------------------------------
if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    client_rows="$(php docker/db-table-count.php oauth_clients 2>/dev/null || echo unknown)"
    if [ "$client_rows" = "0" ] || [ "$client_rows" = "unknown" ]; then
        log "Generating Passport keys and OAuth clients"
        php artisan passport:install --force
    else
        log "Re-generating Passport keys (OAuth clients already exist)"
        php artisan passport:keys --force
    fi
fi

# ---------------------------------------------------------------------------
# 10. Refresh Composer's cached package manifest, then hand over to Apache.
#
# `config:cache`/`route:cache` are deliberately not used: the application calls
# env() outside config/ (e.g. app/Http/Controllers/SiteController.php uses
# MAX_RECORD_PER_PAGE), which returns null once the config is cached.
# ---------------------------------------------------------------------------
php artisan package:discover >/dev/null \
    || log "package:discover failed - Laravel will rebuild the manifest on demand"

log "Starting Apache on port ${PORT}"
exec apache2-foreground