#!/usr/bin/env bash
set -uo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
PASS_COUNT=0
FAIL_COUNT=0
SKIP_COUNT=0

report_pass() {
    PASS_COUNT=$(( PASS_COUNT + 1 ))
    printf "PASS %s\n" "$1"
}

report_fail() {
    FAIL_COUNT=$(( FAIL_COUNT + 1 ))
    printf "FAIL %s: %s\n" "$1" "$2"
}

report_skip() {
    SKIP_COUNT=$(( SKIP_COUNT + 1 ))
    printf "SKIP %s: %s\n" "$1" "$2"
}

TEST_ENV_FILE="${REPO_ROOT}/.env.test-etap05"

cat > "${TEST_ENV_FILE}" << 'ENVEOF'
MYSQL_ROOT_PASSWORD=testroot
MYSQL_DATABASE=tickethub_test
MYSQL_USER=tickethub
MYSQL_PASSWORD=testpass
DB_HOST=db
DB_NAME=tickethub_test
DB_USER=tickethub
DB_PASS=testpass
DB_PREFIX=th_
SECRET_SALT=test_salt_32_chars_1234567890ab
APP_ENV=testing
APP_DEBUG=1
ADMIN_EMAIL=admin@test.local
IMAGE_TAG=etap05-test
ENVEOF

export_compose() {
    cd "${REPO_ROOT}" && docker compose --env-file "${TEST_ENV_FILE}" "$@"
}

check_c1_fresh_bootstrap() {
    export_compose down -v --remove-orphans 2>/dev/null || true
    export_compose up -d 2>&1
    sleep 45
    local logs
    logs=$(export_compose logs web 2>&1)
    if echo "$logs" | grep -q "bootstrap ok"; then
        report_pass "C-1 Fresh bootstrap: 'bootstrap ok' in logs"
    else
        report_fail "C-1 Fresh bootstrap" "no 'bootstrap ok' found in logs"
        printf "  Logs tail:\n%s\n" "$(echo "$logs" | tail -20)"
    fi
}

check_c2_second_restart_idempotent() {
    export_compose restart web 2>&1
    sleep 15
    local logs
    logs=$(export_compose logs --tail=30 web 2>&1)
    if echo "$logs" | grep -q "applied migration" 2>/dev/null; then
        report_fail "C-2 Second restart idempotency" "unexpected 'applied migration' in logs"
    else
        report_pass "C-2 Second restart: no new migrations applied"
    fi
}

check_c3_third_restart_idempotent() {
    export_compose restart web 2>&1
    sleep 15
    local logs
    logs=$(export_compose logs --tail=30 web 2>&1)
    if echo "$logs" | grep -q "applied migration" 2>/dev/null; then
        report_fail "C-3 Third restart idempotency" "unexpected 'applied migration' in logs"
    else
        report_pass "C-3 Third restart: no new migrations applied"
    fi
}

check_c4_migrations_table_baseline() {
    local result
    result=$(export_compose exec db mysql -uroot -ptestroot -e "SELECT migration, checksum FROM tickethub_test.th_migrations" 2>&1)
    if echo "$result" | grep -q "20260421000001_baseline"; then
        report_pass "C-4 th_migrations contains baseline entry"
    else
        report_fail "C-4 th_migrations" "baseline entry not found: $result"
    fi
}

check_c5_tables_exist() {
    local count
    count=$(export_compose exec db mysql -uroot -ptestroot -e "SHOW TABLES FROM tickethub_test" 2>&1 | wc -l)
    if [ "$count" -gt 50 ]; then
        report_pass "C-5 Schema tables count: $count (> 50)"
    else
        report_fail "C-5 Schema tables" "only $count tables found (expected > 50)"
    fi
}

check_c6_healthz_no_admin() {
    local http_code body
    body=$(curl -s -o /tmp/healthz_c6.json -w "%{http_code}" http://localhost:8080/healthz 2>/dev/null || echo "000")
    http_code="$body"
    body=$(cat /tmp/healthz_c6.json 2>/dev/null || echo "")
    if [ "$http_code" = "503" ] && echo "$body" | grep -q "no_admin"; then
        report_pass "C-6 /healthz returns 503 no_admin before first admin"
    else
        report_fail "C-6 /healthz no_admin" "got http=${http_code} body=${body}"
    fi
}

check_c7_first_admin_cli() {
    local result exit_code
    result=$(export_compose exec -T web php bin/first-admin.php --username=tester --email=t@e.st --password=TestPass123 2>&1) || true
    exit_code=$?
    if [ "$exit_code" -eq 0 ] && (echo "$result" | grep -qi "created\|success\|admin"); then
        report_pass "C-7 first-admin.php creates admin"
    else
        report_fail "C-7 first-admin.php" "exit=${exit_code} output=${result}"
    fi
}

check_c8_healthz_ok() {
    sleep 3
    local body
    body=$(curl -s http://localhost:8080/healthz 2>/dev/null || echo "")
    if echo "$body" | grep -q '"status":"ok"'; then
        report_pass "C-8 /healthz returns 200 ok after admin created"
    else
        report_fail "C-8 /healthz ok" "body=${body}"
    fi
}

check_c9_first_admin_duplicate() {
    local result exit_code
    result=$(export_compose exec -T web php bin/first-admin.php --username=x --email=x@x.x --password=testpassword1 2>&1) || true
    exit_code=$?
    if [ "$exit_code" -ne 0 ] && echo "$result" | grep -qi "already exists\|admin already"; then
        report_pass "C-9 Duplicate first-admin rejected"
    else
        report_fail "C-9 Duplicate first-admin" "exit=${exit_code} output=${result}"
    fi
}

check_c11_drift_detection() {
    export_compose exec db mysql -uroot -ptestroot -e "UPDATE tickethub_test.th_config SET thversion='0.0.0' WHERE id=1" 2>&1
    sleep 3
    local body http_code
    body=$(curl -s -o /tmp/healthz_c11.json -w "%{http_code}" http://localhost:8080/healthz 2>/dev/null || echo "000")
    http_code="$body"
    body=$(cat /tmp/healthz_c11.json 2>/dev/null || echo "")
    if [ "$http_code" = "503" ] && echo "$body" | grep -q "drift"; then
        report_pass "C-11 Drift detection: 503 drift when thversion mismatch"
        export_compose exec db mysql -uroot -ptestroot -e "UPDATE tickethub_test.th_config SET thversion='1.0' WHERE id=1" 2>&1
    else
        report_fail "C-11 Drift detection" "got http=${http_code} body=${body}"
    fi
}

check_c14_seed_cli() {
    local result exit_code
    result=$(export_compose exec -T web php bin/db-seed.php dev_demo_data 2>&1) || true
    exit_code=$?
    if [ "$exit_code" -eq 0 ]; then
        report_pass "C-14 Seed CLI: dev_demo_data applied successfully"
    else
        report_fail "C-14 Seed CLI" "exit=${exit_code} output=${result}"
    fi
}

check_c15_seed_duplicate() {
    local result exit_code
    result=$(export_compose exec -T web php bin/db-seed.php dev_demo_data 2>&1) || true
    exit_code=$?
    if [ "$exit_code" -ne 0 ] && echo "$result" | grep -qi "already applied\|already"; then
        report_pass "C-15 Duplicate seed rejected"
    else
        report_fail "C-15 Duplicate seed" "exit=${exit_code} output=${result}"
    fi
}

printf "=== Phase C: Integration checks ===\n"
printf "Repo: %s\n\n" "$REPO_ROOT"

check_c1_fresh_bootstrap
check_c2_second_restart_idempotent
check_c3_third_restart_idempotent
check_c4_migrations_table_baseline
check_c5_tables_exist
check_c6_healthz_no_admin
check_c7_first_admin_cli
check_c8_healthz_ok
check_c9_first_admin_duplicate
report_skip "C-10 Env-driven admin seed" "requires fresh volume + ADMIN_PASSWORD_HASH bcrypt env — run manually"
check_c11_drift_detection
report_skip "C-12 Baseline idempotent on existing schema" "requires manual DELETE FROM th_migrations + restart"
report_skip "C-13 Checksum mismatch detection" "requires manual file modification — see sim-broken-migration.sh"
check_c14_seed_cli
check_c15_seed_duplicate

printf "\n=== Phase C Summary: PASS=%d FAIL=%d SKIP=%d ===\n" "$PASS_COUNT" "$FAIL_COUNT" "$SKIP_COUNT"

if [ "$FAIL_COUNT" -gt 0 ]; then
    exit 1
fi
exit 0
