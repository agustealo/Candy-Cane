#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${ROOT_DIR}/tests/docker-compose.release.yml"
DIST_DIR="${ROOT_DIR}/dist"
RELEASE_PORT="${CANDY_CANE_RELEASE_PORT:-8081}"
SITE_URL="http://127.0.0.1:${RELEASE_PORT}"
THEME_CHECK_VERSION="20260821"
export CANDY_CANE_RELEASE_PORT="${RELEASE_PORT}"
export COMPOSE_PROJECT_NAME="candy-cane-release-${GITHUB_RUN_ID:-local}"

cleanup() {
	docker compose -f "${COMPOSE_FILE}" down -v --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

wp_cli() {
	docker compose -f "${COMPOSE_FILE}" run --rm --no-deps cli "$@"
}

fail() {
	printf 'Release package smoke failed: %s\n' "$1" >&2
	docker compose -f "${COMPOSE_FILE}" logs --no-color wordpress >&2 || true
	exit 1
}

assert_contains() {
	local haystack="$1"
	local needle="$2"
	local context="$3"

	if [[ "${haystack}" != *"${needle}"* ]]; then
		fail "${context} did not contain '${needle}'"
	fi
}

version="$(sed -n 's/^Version:[[:space:]]*//p' "${ROOT_DIR}/style.css" | head -n 1 | tr -d '\r')"
[[ -n "${version}" ]] || fail 'style.css does not declare a theme version'
archive="${DIST_DIR}/Candy-Cane-${version}.zip"
checksum="${archive}.sha256"

printf 'Building deterministic Candy Cane %s consumer ZIP...\n' "${version}"
bash "${ROOT_DIR}/scripts/build-release.sh" "${DIST_DIR}"
first_sha="$(cut -d ' ' -f 1 "${checksum}")"
bash "${ROOT_DIR}/scripts/build-release.sh" "${DIST_DIR}"
second_sha="$(cut -d ' ' -f 1 "${checksum}")"
[[ "${first_sha}" == "${second_sha}" ]] || fail "release archive is not deterministic: ${first_sha} != ${second_sha}"

(
	cd "${DIST_DIR}"
	sha256sum --check "$(basename "${checksum}")" >/dev/null
)
unzip -tqq "${archive}"

printf 'Validating release archive manifest...\n'
mapfile -t archive_entries < <(unzip -Z1 "${archive}")
[[ "${#archive_entries[@]}" -gt 0 ]] || fail 'release archive is empty'

for entry in "${archive_entries[@]}"; do
	[[ "${entry}" == Candy-Cane/* || "${entry}" == 'Candy-Cane/' ]] || fail "archive entry escaped the Candy-Cane root: ${entry}"
done

for required in \
	'Candy-Cane/style.css' \
	'Candy-Cane/legacy-style.css' \
	'Candy-Cane/functions.php' \
	'Candy-Cane/inc/class-candy-cane-theme.php' \
	'Candy-Cane/header.php' \
	'Candy-Cane/footer.php' \
	'Candy-Cane/index.php' \
	'Candy-Cane/loop-index.php' \
	'Candy-Cane/page.php' \
	'Candy-Cane/single.php' \
	'Candy-Cane/sidebar.php' \
	'Candy-Cane/searchform.php' \
	'Candy-Cane/404.php' \
	'Candy-Cane/stylesheets/app.css' \
	'Candy-Cane/stylesheets/modern.css' \
	'Candy-Cane/javascripts/foundation.js' \
	'Candy-Cane/javascripts/app.js' \
	'Candy-Cane/screenshot.png' \
	'Candy-Cane/readme.txt'; do
	printf '%s\n' "${archive_entries[@]}" | grep -Fxq "${required}" || fail "release archive is missing required file ${required}"
done

if printf '%s\n' "${archive_entries[@]}" | grep -Eq '^Candy-Cane/(\.github(/|$)|tests(/|$)|scripts(/|$)|vendor(/|$)|node_modules(/|$)|\.dockerignore$|\.gitattributes$|\.gitignore$|README\.md$|composer\.json$|phpcs\.xml\.dist$)'; then
	fail 'release archive contains repository-only development files'
fi

if printf '%s\n' "${archive_entries[@]}" | grep -Eq '(^|/)_notes(/|$)|(^|/)dwsync\.xml$'; then
	fail 'release archive contains obsolete Dreamweaver metadata'
fi

archive_style="$(unzip -p "${archive}" 'Candy-Cane/style.css')"
assert_contains "${archive_style}" 'Theme Name: Candy Cane' 'packaged style.css'
assert_contains "${archive_style}" "Version: ${version}" 'packaged style.css'
assert_contains "${archive_style}" 'Text Domain: candy-cane' 'packaged style.css'
assert_contains "${archive_style}" 'License: GNU General Public License v2.0' 'packaged style.css'

printf 'Starting clean WordPress runtime with no repository-mounted Candy Cane...\n'
docker compose -f "${COMPOSE_FILE}" up -d db wordpress

ready=0
for attempt in $(seq 1 60); do
	if curl --silent --show-error --fail --location "${SITE_URL}/wp-admin/install.php" >/dev/null 2>&1; then
		ready=1
		break
	fi
	sleep 2
done
[[ "${ready}" -eq 1 ]] || fail 'WordPress did not become ready for release-package installation'

wp_cli core install \
	--url="${SITE_URL}" \
	--title='Candy Cane Release Package' \
	--admin_user='candy-release-admin' \
	--admin_password='release-package-test-password' \
	--admin_email='release@example.test' \
	--skip-email >/dev/null

if wp_cli theme is-installed Candy-Cane >/dev/null 2>&1; then
	fail 'Candy Cane was already installed before the consumer ZIP was installed'
fi

printf 'Installing and activating the generated consumer ZIP...\n'
wp_cli theme install "/workspace/dist/$(basename "${archive}")" --activate --force >/dev/null

active_stylesheet="$(wp_cli option get stylesheet)"
[[ "${active_stylesheet}" == 'Candy-Cane' ]] || fail "expected active stylesheet Candy-Cane, got ${active_stylesheet}"

installed_version="$(wp_cli theme get "${active_stylesheet}" --field=version)"
[[ "${installed_version}" == "${version}" ]] || fail "expected packaged theme ${version}, got ${installed_version}"

core_version="$(wp_cli core version)"
[[ "${core_version}" == '7.1.1' ]] || fail "expected WordPress 7.1.1, got ${core_version}"

printf 'Running official WordPress Theme Check %s against the installed consumer ZIP...\n' "${THEME_CHECK_VERSION}"
wp_cli plugin install theme-check --version="${THEME_CHECK_VERSION}" --activate --force >/dev/null
theme_check_report="${DIST_DIR}/Candy-Cane-${version}-theme-check.json"
if ! wp_cli theme-check run "${active_stylesheet}" --format=json > "${theme_check_report}"; then
	cat "${theme_check_report}" >&2 || true
	fail 'official WordPress Theme Check reported release-blocking errors for the consumer ZIP'
fi

printf 'Verifying packaged WordPress contracts...\n'
wp_cli eval '
$menus = get_registered_nav_menus();
if ( ! isset( $menus["header-menu1"], $menus["header-menu2"] ) ) {
	throw new RuntimeException( "Candy Cane menu locations are not registered from the ZIP install." );
}

global $wp_registered_sidebars, $_wp_additional_image_sizes;
foreach ( array( "right_sidebar", "footer_1", "footer_2", "footer_3", "footer_4" ) as $sidebar_id ) {
	if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
		throw new RuntimeException( "Missing packaged sidebar: " . $sidebar_id );
	}
}

$front = $_wp_additional_image_sizes["front"] ?? null;
$name  = $_wp_additional_image_sizes["name_size"] ?? null;
if ( ! $front || 210 !== $front["width"] || 210 !== $front["height"] || array( "center", "top" ) !== $front["crop"] ) {
	throw new RuntimeException( "Packaged front image-size contract changed." );
}
if ( ! $name || 460 !== $name["width"] || 345 !== $name["height"] || array( "center", "top" ) !== $name["crop"] ) {
	throw new RuntimeException( "Packaged name_size image-size contract changed." );
}
' >/dev/null

printf 'Seeding packaged-theme content and probing rendered routes...\n'
visible_category_id="$(wp_cli term create category 'Packaged Visible' --slug=packaged-visible --porcelain)"
post_id="$(wp_cli post create \
	--post_type=post \
	--post_status=publish \
	--post_title='Packaged Candy Cane Post' \
	--post_content='This content is rendered from the generated consumer ZIP.' \
	--porcelain)"
wp_cli post term set "${post_id}" category "${visible_category_id}" --by=id >/dev/null

page_id="$(wp_cli post create \
	--post_type=page \
	--post_status=publish \
	--post_title='Packaged Candy Cane Page' \
	--post_content='This page is rendered from the generated consumer ZIP.' \
	--porcelain)"

wp_cli menu create 'Release Main Navigation' >/dev/null
wp_cli menu item add-custom 'Release Main Navigation' 'Home' "${SITE_URL}/" >/dev/null
wp_cli menu item add-post 'Release Main Navigation' "${page_id}" >/dev/null
wp_cli menu location assign 'Release Main Navigation' header-menu1 >/dev/null

home_html="$(curl --silent --show-error --fail --location "${SITE_URL}/")"
assert_contains "${home_html}" 'Packaged Candy Cane Post' 'packaged home page'
assert_contains "${home_html}" 'href="#primary-content"' 'packaged accessibility markup'

single_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?p=${post_id}")"
assert_contains "${single_html}" 'Packaged Candy Cane Post' 'packaged single post'
assert_contains "${single_html}" 'This content is rendered from the generated consumer ZIP.' 'packaged single content'

page_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?page_id=${page_id}")"
assert_contains "${page_html}" 'Packaged Candy Cane Page' 'packaged page'
assert_contains "${page_html}" 'This page is rendered from the generated consumer ZIP.' 'packaged page content'

style_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/Candy-Cane/style.css")"
legacy_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/Candy-Cane/legacy-style.css")"
modern_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/Candy-Cane/stylesheets/modern.css")"
assert_contains "${style_css}" "Version: ${version}" 'packaged public style.css'
assert_contains "${legacy_css}" 'Foundation v2.1.3' 'packaged public legacy stylesheet'
assert_contains "${modern_css}" 'prefers-reduced-motion' 'packaged public modern stylesheet'

printf 'Checking packaged runtime logs for fatal PHP failures...\n'
wordpress_logs="$(docker compose -f "${COMPOSE_FILE}" logs --no-color wordpress 2>&1 || true)"
if grep -Eiq 'PHP (Fatal error|Parse error)|Uncaught (Error|Exception)' <<<"${wordpress_logs}"; then
	printf '%s\n' "${wordpress_logs}" >&2
	fail 'fatal PHP error found while running the consumer ZIP'
fi

debug_log="$(docker compose -f "${COMPOSE_FILE}" exec -T wordpress sh -lc 'if [ -f /var/www/html/wp-content/debug.log ]; then cat /var/www/html/wp-content/debug.log; fi' 2>/dev/null || true)"
if grep -Eiq 'PHP (Fatal error|Parse error)|Uncaught (Error|Exception)' <<<"${debug_log}"; then
	printf '%s\n' "${debug_log}" >&2
	fail 'fatal PHP error found in packaged wp-content/debug.log'
fi

printf 'Candy Cane consumer ZIP passed: %s, WordPress %s, Theme Check %s, SHA-256 %s.\n' "${version}" "${core_version}" "${THEME_CHECK_VERSION}" "${second_sha}"
