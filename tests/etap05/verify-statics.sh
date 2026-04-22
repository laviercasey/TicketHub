#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PASS_COUNT=0
FAIL_COUNT=0

report_pass() {
    PASS_COUNT=$(( PASS_COUNT + 1 ))
    printf "PASS %s\n" "$1"
}

report_fail() {
    FAIL_COUNT=$(( FAIL_COUNT + 1 ))
    printf "FAIL %s: %s\n" "$1" "$2"
}

run_php_lint_in_docker() {
    local file_path="$1"
    local container_path="/var/www/html/${file_path}"
    local output exit_code
    output=$(docker run --rm --entrypoint=sh tickethub-etap05-test -c "php -l ${container_path} 1>/tmp/out.txt 2>/tmp/err.txt; cat /tmp/out.txt; cat /tmp/err.txt" 2>&1) || true
    exit_code=$(docker run --rm --entrypoint=sh tickethub-etap05-test -c "php -l ${container_path} >/dev/null 2>&1; echo \$?" 2>&1 | tail -1) || exit_code=255
    printf "  %s -> %s\n" "$file_path" "$output"
    if [ "$exit_code" = "0" ]; then
        return 0
    else
        return 1
    fi
}

check_a1_php_syntax_new_files() {
    local all_passed=true
    for rel_path in bin/db-bootstrap.php bin/db-seed.php bin/first-admin.php db/seeds/dev_demo_data.php healthz.php; do
        container_path="/var/www/html/${rel_path}"
        rc=$(docker run --rm --entrypoint=sh tickethub-etap05-test -c "php -l ${container_path} 1>/dev/null 2>/dev/null; echo \$?" 2>&1 | tail -1)
        if [ "$rc" != "0" ]; then
            all_passed=false
            printf "  SYNTAX ERROR in %s (exit %s)\n" "$rel_path" "$rc"
        else
            printf "  OK: %s\n" "$rel_path"
        fi
    done
    if $all_passed; then
        report_pass "A-1 PHP syntax new files"
    else
        report_fail "A-1 PHP syntax new files" "one or more files failed php -l"
    fi
}

check_a2_php_syntax_modified_files() {
    local all_passed=true
    for rel_path in setup/install/migration.php setup/install/migrations/20260421000001_baseline.php main.inc.php; do
        container_path="/var/www/html/${rel_path}"
        rc=$(docker run --rm --entrypoint=sh tickethub-etap05-test -c "php -l ${container_path} 1>/dev/null 2>/dev/null; echo \$?" 2>&1 | tail -1)
        if [ "$rc" != "0" ]; then
            all_passed=false
            printf "  SYNTAX ERROR in %s (exit %s)\n" "$rel_path" "$rc"
        fi
    done
    if $all_passed; then
        report_pass "A-2 PHP syntax modified files"
    else
        report_fail "A-2 PHP syntax modified files" "one or more modified files failed php -l"
    fi
}

check_a3_thinstalled_removed() {
    local hits
    hits=$(grep -rn "THINSTALLED" "${REPO_ROOT}" --include="*.php" | grep -v ".orchestra/" | grep -v "docs/" | grep -v "CHANGELOG" | grep -v "//.*THINSTALLED\|/\*.*THINSTALLED\|\*.*THINSTALLED" 2>/dev/null || true)
    if [ -z "$hits" ]; then
        report_pass "A-3 THINSTALLED removed from PHP runtime code"
    else
        report_fail "A-3 THINSTALLED found in PHP code" "$hits"
    fi
}

check_a4_php4_constructor_removed() {
    local hits
    hits=$(grep -rn "function MigrationManager()" "${REPO_ROOT}" --include="*.php" 2>/dev/null || true)
    if [ -z "$hits" ]; then
        report_pass "A-4 PHP4-style constructor removed"
    else
        report_fail "A-4 PHP4-style constructor still present" "$hits"
    fi
}

check_a5_shell_syntax() {
    if bash -n "${REPO_ROOT}/docker-entrypoint.sh" 2>/dev/null; then
        report_pass "A-5 docker-entrypoint.sh shell syntax valid"
    else
        report_fail "A-5 docker-entrypoint.sh shell syntax" "bash -n failed"
    fi
}

check_a6_yaml_structure() {
    local deploy_yml="${REPO_ROOT}/.github/workflows/deploy.yml"
    local has_ci has_backup has_backup_needs has_deploy_needs
    has_ci=$(grep -c "^  ci:" "$deploy_yml" 2>/dev/null || echo 0)
    has_backup=$(grep -c "^  backup:" "$deploy_yml" 2>/dev/null || echo 0)
    has_backup_needs=$(grep -A3 "^  backup:" "$deploy_yml" | grep -c "needs: ci" || echo 0)
    has_deploy_needs=$(grep -A3 "^  deploy:" "$deploy_yml" | grep -c "needs: \[build-push, backup\]" || echo 0)
    if [ "$has_ci" -ge 1 ] && [ "$has_backup" -ge 1 ] && [ "$has_backup_needs" -ge 1 ] && [ "$has_deploy_needs" -ge 1 ]; then
        report_pass "A-6 deploy.yml structure valid (ci->backup||build-push->deploy)"
    else
        report_fail "A-6 deploy.yml structure" "ci:${has_ci} backup:${has_backup} backup_needs:${has_backup_needs} deploy_needs:${has_deploy_needs}"
    fi
}

check_a7_compose_valid() {
    local result
    result=$(cd "${REPO_ROOT}" && docker compose config 2>&1 | grep -i "error" || true)
    if [ -z "$result" ]; then
        report_pass "A-7 docker-compose.yml valid"
    else
        report_fail "A-7 docker-compose.yml" "$result"
    fi
}

check_a8_wizard_deleted() {
    if [ ! -f "${REPO_ROOT}/setup/install.php" ]; then
        report_pass "A-8 setup/install.php deleted from repo"
    else
        report_fail "A-8 setup/install.php still present in repo" "file must not exist after wizard removal"
    fi
}

check_a9_sql_injection_scan() {
    local hits
    hits=$(grep -rn "INSERT\|UPDATE\|DELETE" "${REPO_ROOT}/bin/" 2>/dev/null | grep -v "mysqli_real_escape_string\|db_input\|db_real_escape\|safe[A-Z]\|TABLE_PREFIX\|MIGRATIONS_TABLE\|prefix\}\|prefix\." | grep -v "^[^:]*:[0-9]*:\s*//" || true)
    if [ -z "$hits" ]; then
        report_pass "A-9 SQL injection scan: no raw user input concatenation in bin/"
    else
        report_fail "A-9 SQL injection scan" "potential unsafe queries: $hits"
    fi
}

check_a10_env_validation() {
    if grep -q "DB_PASS:?" "${REPO_ROOT}/docker-entrypoint.sh" 2>/dev/null; then
        report_pass "A-10 docker-entrypoint.sh validates DB_PASS"
    else
        report_fail "A-10 env validation missing DB_PASS:?" "pattern not found"
    fi
}

printf "=== Phase A: Static checks ===\n"
printf "Repo: %s\n\n" "$REPO_ROOT"

check_a1_php_syntax_new_files
check_a2_php_syntax_modified_files
check_a3_thinstalled_removed
check_a4_php4_constructor_removed
check_a5_shell_syntax
check_a6_yaml_structure
check_a7_compose_valid
check_a8_wizard_deleted
check_a9_sql_injection_scan
check_a10_env_validation

printf "\n=== Phase A Summary: PASS=%d FAIL=%d ===\n" "$PASS_COUNT" "$FAIL_COUNT"

if [ "$FAIL_COUNT" -gt 0 ]; then
    exit 1
fi
exit 0
