#!/usr/bin/env bash

set -Eeuo pipefail

cd /var/www/html

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

role="${APP_ROLE:-web}"

if [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction >/dev/null 2>&1 || true
fi

if [ "${APP_SKIP_OPTIMIZE:-false}" != "true" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

case "$role" in
    web)
        php-fpm --daemonize
        exec nginx -g 'daemon off;'
        ;;

    worker)
        exec php artisan queue:work \
            --verbose \
            --no-interaction \
            --sleep="${QUEUE_WORKER_SLEEP:-1}" \
            --tries="${QUEUE_WORKER_TRIES:-3}" \
            --timeout="${QUEUE_WORKER_TIMEOUT:-120}" \
            --max-time="${QUEUE_WORKER_MAX_TIME:-3600}"
        ;;

    reverb)
        reverb_args=(
            artisan
            reverb:start
            --host="${REVERB_SERVER_HOST:-0.0.0.0}"
            --port="${REVERB_SERVER_PORT:-8080}"
        )

        if [ -n "${REVERB_HOST:-}" ]; then
            reverb_args+=(--hostname="${REVERB_HOST}")
        fi

        exec php "${reverb_args[@]}"
        ;;

    scheduler)
        while true; do
            php artisan schedule:run --verbose --no-interaction
            sleep 60
        done
        ;;

    *)
        echo "Unsupported APP_ROLE [$role]. Expected one of: web, worker, reverb, scheduler." >&2
        exit 1
        ;;
esac

