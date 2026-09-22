#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSE_FILE="${SCRIPT_DIR}/docker-compose.runtime.yml"
RUNTIME_PORT="${CANDY_CANE_RUNTIME_PORT:-8080}"
SITE_URL="http://127.0.0.1:${RUNTIME_PORT}"
export CANDY_CANE_RUNTIME_PORT="${RUNTIME_PORT}"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-candy-cane-runtime-${GITHUB_RUN_ID:-local}}"

cleanup() {
	if [[ "${CANDY_CANE_KEEP_RUNTIME:-0}" == '1' ]]; then
		return
	fi

	docker compose -f "${COMPOSE_FILE}" down -v --remove-orphans >/dev/null 2>&1 || true
}
trap cleanup EXIT

wp_cli() {
	docker compose -f "${COMPOSE_FILE}" run --rm --no-deps cli "$@"
}

fail() {
	printf 'Runtime smoke failed: %s\n' "$1" >&2
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

assert_not_contains() {
	local haystack="$1"
	local needle="$2"
	local context="$3"

	if [[ "${haystack}" == *"${needle}"* ]]; then
		fail "${context} unexpectedly contained '${needle}'"
	fi
}

printf 'Building and starting WordPress 7.1.1 runtime...\n'
docker compose -f "${COMPOSE_FILE}" up -d --build db wordpress

ready=0
for attempt in $(seq 1 60); do
	if curl --silent --show-error --fail --location "${SITE_URL}/wp-admin/install.php" >/dev/null 2>&1; then
		ready=1
		break
	fi
	sleep 2
done

if [[ "${ready}" -ne 1 ]]; then
	fail 'WordPress did not become ready for installation'
fi

printf 'Installing WordPress and activating Candy Cane...\n'
wp_cli core install \
	--url="${SITE_URL}" \
	--title='Candy Cane Runtime' \
	--admin_user='candy-admin' \
	--admin_password='runtime-test-password' \
	--admin_email='candy@example.test' \
	--skip-email >/dev/null

wp_cli theme activate candy-cane >/dev/null

core_version="$(wp_cli core version)"
[[ "${core_version}" == '7.1.1' ]] || fail "expected WordPress 7.1.1, got ${core_version}"

theme_version="$(wp_cli theme get candy-cane --field=version)"
[[ "${theme_version}" == '0.10.0' ]] || fail "expected Candy Cane 0.10.0, got ${theme_version}"

printf 'Seeding deterministic WordPress content...\n'
wp_cli term create category 'Runtime Two' --slug=runtime-two --porcelain >/dev/null
wp_cli term create category 'Runtime Three' --slug=runtime-three --porcelain >/dev/null
wp_cli term create category 'Runtime Four' --slug=runtime-four --porcelain >/dev/null
hidden_five_id="$(wp_cli term create category 'Hidden Five' --slug=hidden-five --porcelain)"
visible_category_id="$(wp_cli term create category 'Visible Runtime' --slug=visible-runtime --porcelain)"

[[ "${hidden_five_id}" == '5' ]] || fail "fresh-install category ID contract changed; expected Hidden Five to be ID 5, got ${hidden_five_id}"

hidden_one_post="$(wp_cli post create \
	--post_type=post \
	--post_status=publish \
	--post_title='Hidden Category One' \
	--post_content='This post belongs to category one and must stay off the Candy Cane home index.' \
	--porcelain)"

hidden_five_post="$(wp_cli post create \
	--post_type=post \
	--post_status=publish \
	--post_title='Hidden Category Five' \
	--post_content='This post belongs to category five and must stay off the Candy Cane home index.' \
	--porcelain)"
wp_cli post term set "${hidden_five_post}" category "${hidden_five_id}" --by=id >/dev/null

visible_post="$(wp_cli post create \
	--post_type=post \
	--post_status=publish \
	--post_title='Visible Runtime Post' \
	--post_content='Candy Cane real WordPress runtime content.' \
	--porcelain)"
wp_cli post term set "${visible_post}" category "${visible_category_id}" --by=id >/dev/null

page_id="$(wp_cli post create \
	--post_type=page \
	--post_status=publish \
	--post_title='Candy Cane Runtime Page' \
	--post_content='Candy Cane page-template runtime content.' \
	--porcelain)"

attachment_id="$(wp_cli media import \
	/var/www/html/wp-content/themes/candy-cane/screenshot.png \
	--title='Candy Cane Runtime Featured Image' \
	--porcelain)"
wp_cli post meta update "${visible_post}" _thumbnail_id "${attachment_id}" >/dev/null

wp_cli menu create 'Main Navigation' >/dev/null
wp_cli menu item add-custom 'Main Navigation' 'Home' "${SITE_URL}/" >/dev/null
wp_cli menu item add-post 'Main Navigation' "${page_id}" >/dev/null
wp_cli menu location assign 'Main Navigation' header-menu1 >/dev/null

wp_cli menu create 'Secondary Navigation' >/dev/null
wp_cli menu item add-custom 'Secondary Navigation' 'Search' "${SITE_URL}/?s=Visible" >/dev/null
wp_cli menu location assign 'Secondary Navigation' header-menu2 >/dev/null

printf 'Verifying registered WordPress contracts...\n'
wp_cli eval '
$menus = get_registered_nav_menus();
if ( ! isset( $menus["header-menu1"], $menus["header-menu2"] ) ) {
	throw new RuntimeException( "Candy Cane menu locations are not registered." );
}

global $wp_registered_sidebars, $_wp_additional_image_sizes;
foreach ( array( "right_sidebar", "footer_1", "footer_2", "footer_3", "footer_4" ) as $sidebar_id ) {
	if ( ! isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
		throw new RuntimeException( "Missing sidebar: " . $sidebar_id );
	}
}

$front = $_wp_additional_image_sizes["front"] ?? null;
$name  = $_wp_additional_image_sizes["name_size"] ?? null;
if ( ! $front || 210 !== $front["width"] || 210 !== $front["height"] || array( "center", "top" ) !== $front["crop"] ) {
	throw new RuntimeException( "front image-size contract changed." );
}
if ( ! $name || 460 !== $name["width"] || 345 !== $name["height"] || array( "center", "top" ) !== $name["crop"] ) {
	throw new RuntimeException( "name_size image-size contract changed." );
}

echo "Candy Cane WordPress contracts registered.\n";
' >/dev/null

printf 'Probing rendered theme routes...\n'
home_html="$(curl --silent --show-error --fail --location "${SITE_URL}/")"
assert_contains "${home_html}" 'Visible Runtime Post' 'home page'
assert_not_contains "${home_html}" 'Hidden Category One' 'home page'
assert_not_contains "${home_html}" 'Hidden Category Five' 'home page'
assert_contains "${home_html}" 'href="#primary-content"' 'home page accessibility markup'

single_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?p=${visible_post}")"
assert_contains "${single_html}" 'Visible Runtime Post' 'single post'
assert_contains "${single_html}" 'Candy Cane real WordPress runtime content.' 'single post content'

page_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?page_id=${page_id}")"
assert_contains "${page_html}" 'Candy Cane Runtime Page' 'page template'
assert_contains "${page_html}" 'Candy Cane page-template runtime content.' 'page template content'

category_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?cat=${visible_category_id}")"
assert_contains "${category_html}" 'Visible Runtime Post' 'category archive'

search_html="$(curl --silent --show-error --fail --location "${SITE_URL}/?s=Visible+Runtime")"
assert_contains "${search_html}" 'Visible Runtime Post' 'search results'

not_found_file="$(mktemp)"
not_found_status="$(curl --silent --show-error --output "${not_found_file}" --write-out '%{http_code}' "${SITE_URL}/?p=999999")"
[[ "${not_found_status}" == '404' ]] || fail "expected a 404 response, got ${not_found_status}"
not_found_html="$(cat "${not_found_file}")"
rm -f "${not_found_file}"
assert_contains "${not_found_html}" 'Not Found' '404 template'

printf 'Verifying public theme assets...\n'
style_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/candy-cane/style.css")"
legacy_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/candy-cane/legacy-style.css")"
modern_css="$(curl --silent --show-error --fail "${SITE_URL}/wp-content/themes/candy-cane/stylesheets/modern.css")"
assert_contains "${style_css}" 'Version: 0.10.0' 'style.css'
assert_contains "${style_css}" '@import url("legacy-style.css")' 'style.css legacy import'
assert_contains "${legacy_css}" 'Foundation v2.1.3' 'legacy stylesheet'
assert_contains "${modern_css}" 'prefers-reduced-motion' 'modern accessibility stylesheet'

printf 'Checking runtime logs for fatal PHP failures...\n'
wordpress_logs="$(docker compose -f "${COMPOSE_FILE}" logs --no-color wordpress 2>&1 || true)"
if grep -Eiq 'PHP (Fatal error|Parse error)|Uncaught (Error|Exception)' <<<"${wordpress_logs}"; then
	printf '%s\n' "${wordpress_logs}" >&2
	fail 'fatal PHP error found in WordPress container logs'
fi

debug_log="$(docker compose -f "${COMPOSE_FILE}" exec -T wordpress sh -lc 'if [ -f /var/www/html/wp-content/debug.log ]; then cat /var/www/html/wp-content/debug.log; fi' 2>/dev/null || true)"
if grep -Eiq 'PHP (Fatal error|Parse error)|Uncaught (Error|Exception)' <<<"${debug_log}"; then
	printf '%s\n' "${debug_log}" >&2
	fail 'fatal PHP error found in wp-content/debug.log'
fi

printf 'Candy Cane runtime smoke passed on WordPress %s with theme %s.\n' "${core_version}" "${theme_version}"
