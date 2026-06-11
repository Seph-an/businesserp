#!/bin/bash
set -e

APP_DIR="/var/www/aureuserp"
cd "$APP_DIR"

log() { echo "[aureus-entrypoint] $(date '+%Y-%m-%d %H:%M:%S') $*"; }

DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
if [ "$DB_CONNECTION" == "pgsql" ] && [ "$DB_PORT" == "3306" ]; then
    DB_PORT="5432"
fi
DB_DATABASE="${DB_DATABASE:-aureus}"
DB_USERNAME="${DB_USERNAME:-aureus}"
DB_PASSWORD="${DB_PASSWORD:-aureus}"

sed_escape() { printf '%s' "$1" | sed -e 's/[\\&|]/\\&/g'; }

set_env() {
    local key="$1" val
    val=$(sed_escape "$2")
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}

log "Applying runtime environment overrides..."
set_env DB_CONNECTION "$DB_CONNECTION"
set_env DB_HOST       "$DB_HOST"
set_env DB_PORT       "$DB_PORT"
set_env DB_DATABASE   "$DB_DATABASE"
set_env DB_USERNAME   "$DB_USERNAME"
set_env DB_PASSWORD   "$DB_PASSWORD"

set_env APP_ENV "${APP_ENV:-production}"
set_env APP_DEBUG "${APP_DEBUG:-false}"

[ -n "$APP_URL" ]      && set_env APP_URL      "$APP_URL"
[ -n "$APP_KEY" ]      && set_env APP_KEY      "$APP_KEY"
[ -n "$APP_NAME" ]     && set_env APP_NAME     "\"${APP_NAME}\""
[ -n "$APP_LOCALE" ]   && set_env APP_LOCALE   "$APP_LOCALE"
[ -n "$APP_CURRENCY" ] && set_env APP_CURRENCY "$APP_CURRENCY"
[ -n "$APP_TIMEZONE" ] && set_env APP_TIMEZONE "$APP_TIMEZONE"

log "Waiting for database connection ($DB_CONNECTION) at ${DB_HOST}:${DB_PORT}..."
for i in $(seq 1 60); do
    if [ "$DB_CONNECTION" == "mysql" ]; then
        PDO_DSN="mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}"
    elif [ "$DB_CONNECTION" == "pgsql" ]; then
        PDO_DSN="pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}"
    else
        log "Skipping connection check for unknown connection type: $DB_CONNECTION"
        break
    fi

    if php -r "try { new PDO('$PDO_DSN', '${DB_USERNAME}', '${DB_PASSWORD}'); } catch (Throwable \$e) { exit(1); }" 2>/dev/null; then
        log "Database is reachable."
        break
    fi

    if [ "$i" -eq 60 ]; then
        log "ERROR: cannot reach database at ${DB_HOST}:${DB_PORT} after 60s."
        exit 1
    fi
    sleep 1
done

log "Checking system installation status..."
if [ ! -f "storage/installed" ]; then
    log "System not installed. Running erp:install..."
    
    # Ensure APP_KEY exists before running install if not provided via env
    if ! grep -q "^APP_KEY=base64:" .env && [ -z "$APP_KEY" ]; then
        log "Generating application key..."
        if ! grep -q "^APP_KEY=" .env; then
            echo "APP_KEY=" >> .env
        fi
        php artisan key:generate --force --no-interaction
    fi

    php artisan erp:install --force --no-interaction \
        --admin-name="${ADMIN_NAME:-Administrator}" \
        --admin-email="${ADMIN_EMAIL:-admin@example.com}" \
        --admin-password="${ADMIN_PASSWORD:-password}"
else
    log "System already installed. Running migrations..."
    php artisan migrate --force --no-interaction
fi

log "Refreshing cached configuration..."

php artisan optimize:clear --no-interaction 2>/dev/null || true

log "Starting services via Supervisor..."

exec "$@"
