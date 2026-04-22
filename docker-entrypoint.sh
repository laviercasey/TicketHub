#!/bin/bash
set -euo pipefail

log() { printf '[entrypoint] %s %s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$*"; }

: "${DB_HOST:?DB_HOST required}"
: "${DB_NAME:?DB_NAME required}"
: "${DB_USER:?DB_USER required}"
: "${DB_PASS:?DB_PASS required}"
: "${SECRET_SALT:?SECRET_SALT required}"

log "env ok, bootstrapping db"

if ! php /var/www/html/bin/db-bootstrap.php; then
    log "ERROR: bootstrap failed"
    exit 1
fi

log "db bootstrap ok, starting apache"

exec apache2-foreground
