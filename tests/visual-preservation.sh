#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
COMPOSE_FILE="${SCRIPT_DIR}/docker-compose.runtime.yml"
REFERENCE_THEME="${SCRIPT_DIR}/reference-theme"
export CANDY_CANE_RUNTIME_PORT="${CANDY_CANE_RUNTIME_PORT:-8080}"
export CANDY_CANE_SITE_URL="http://127.0.0.1:${CANDY_CANE_RUNTIME_PORT}"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-candy-cane-runtime-${GITHUB_RUN_ID:-local}}"
export CANDY_CANE_KEEP_RUNTIME=1

cleanup() {
	docker compose -f "${COMPOSE_FILE}" down -v --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

if [[ ! -f "${REFERENCE_THEME}/style.css" ]]; then
	printf 'Historical reference theme is missing at %s\n' "${REFERENCE_THEME}" >&2
	exit 1
fi

printf 'Starting deterministic Candy Cane runtime fixture...\n'
bash "${SCRIPT_DIR}/runtime-smoke.sh"

printf 'Installing historical Candy Cane alongside the current theme...\n'
docker compose -f "${COMPOSE_FILE}" exec -T wordpress mkdir -p /var/www/html/wp-content/themes/candy-cane-legacy
docker compose -f "${COMPOSE_FILE}" cp "${REFERENCE_THEME}/." wordpress:/var/www/html/wp-content/themes/candy-cane-legacy/
docker compose -f "${COMPOSE_FILE}" exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content/themes/candy-cane-legacy

legacy_status="$(docker compose -f "${COMPOSE_FILE}" run --rm --no-deps cli theme status candy-cane-legacy 2>&1 || true)"
if [[ "${legacy_status}" != *'Status:'* ]]; then
	printf 'Historical Candy Cane did not register as a WordPress theme:\n%s\n' "${legacy_status}" >&2
	exit 1
fi

printf 'Running historical-vs-current Chromium comparison...\n'
node "${SCRIPT_DIR}/browser/visual-preservation.mjs"

printf 'Browser visual preservation proof completed successfully.\n'
