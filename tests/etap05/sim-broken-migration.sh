#!/usr/bin/env bash
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
BASELINE_FILE="${REPO_ROOT}/setup/install/migrations/20260421000001_baseline.php"
BACKUP_FILE="${BASELINE_FILE}.bak"

restore_baseline() {
    if [ -f "${BACKUP_FILE}" ]; then
        mv "${BACKUP_FILE}" "${BASELINE_FILE}"
        printf "Baseline restored from backup\n"
    else
        printf "No backup found at %s\n" "${BACKUP_FILE}"
    fi
}

inject_failure() {
    cp "${BASELINE_FILE}" "${BACKUP_FILE}"
    sed -i '1s/^/<?php throw new \\Exception("test fail sim-broken-migration"); ?>\n/' "${BASELINE_FILE}"
    printf "Injected throw at top of %s\n" "${BASELINE_FILE}"
}

usage() {
    printf "Usage: %s inject|restore|status\n" "$0"
    printf "  inject  - Add throw to baseline migration (simulates broken migration)\n"
    printf "  restore - Restore original baseline migration\n"
    printf "  status  - Show current state\n"
    exit 1
}

ACTION="${1:-}"

case "$ACTION" in
    inject)
        if [ -f "${BACKUP_FILE}" ]; then
            printf "ERROR: backup already exists — run restore first\n"
            exit 1
        fi
        inject_failure
        printf "Done. Now restart web container and check /healthz for 503.\n"
        ;;
    restore)
        restore_baseline
        printf "Done. Restart web container to re-apply baseline.\n"
        ;;
    status)
        if [ -f "${BACKUP_FILE}" ]; then
            printf "State: BROKEN (backup exists at %s)\n" "${BACKUP_FILE}"
        else
            printf "State: NORMAL (no backup)\n"
        fi
        head -n 3 "${BASELINE_FILE}"
        ;;
    *)
        usage
        ;;
esac
