#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-/opt/supportshelf}"
ENV_FILE="${ENV_FILE:-.env.production}"
COMPOSE_FILE="${COMPOSE_FILE:-compose.prod.yaml}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-http://127.0.0.1:18080/up}"

cd "$APP_DIR"

echo "Deploying SupportShelf from $(pwd)"
echo "Using compose file: $COMPOSE_FILE"

docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" up -d --build --remove-orphans

echo "Waiting for application health check..."
for attempt in {1..20}; do
    if curl -fsS "$HEALTHCHECK_URL" >/dev/null; then
        echo "Health check passed."
        docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" ps
        docker image prune -f >/dev/null 2>&1 || true
        exit 0
    fi

    sleep 3
done

echo "Health check failed after deployment." >&2
docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE" ps >&2
exit 1
