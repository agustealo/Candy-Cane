#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
COMPOSE_FILE="${SCRIPT_DIR}/docker-compose.runtime.yml"
RUNTIME_PORT="${CANDY_CANE_RUNTIME_PORT:-8080}"
SITE_URL="http://127.0.0.1:${RUNTIME_PORT}"
export CANDY_CANE_RUNTIME_PORT="${RUNTIME_PORT}"
export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-candy-cane-showcase-${GITHUB_RUN_ID:-local}}"
export CANDY_CANE_SITE_URL="${SITE_URL}"

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
	printf 'Docs showcase failed: %s\n' "$1" >&2
	docker compose -f "${COMPOSE_FILE}" logs --no-color wordpress >&2 || true
	exit 1
}

printf 'Starting clean WordPress showcase runtime...\n'
docker compose -f "${COMPOSE_FILE}" up -d --build db wordpress

ready=0
for attempt in $(seq 1 60); do
	if curl --silent --show-error --fail --location "${SITE_URL}/wp-admin/install.php" >/dev/null 2>&1; then
		ready=1
		break
	fi
	sleep 2
done
[[ "${ready}" -eq 1 ]] || fail 'WordPress did not become ready'

wp_cli core install \
	--url="${SITE_URL}" \
	--title='Candy Cane Journal' \
	--admin_user='candy-admin' \
	--admin_password='showcase-runtime-password' \
	--admin_email='showcase@example.test' \
	--skip-email >/dev/null
wp_cli theme activate candy-cane >/dev/null
wp_cli option update blogdescription 'Design, culture, and creative practice.' >/dev/null
wp_cli option update posts_per_page 8 >/dev/null
wp_cli post delete 1 --force >/dev/null 2>&1 || true
wp_cli post delete 2 --force >/dev/null 2>&1 || true

printf 'Generating deterministic editorial artwork...\n'
docker compose -f "${COMPOSE_FILE}" exec -T wordpress php <<'PHP'
<?php
$dir = '/var/www/html/wp-content/showcase-assets';
if ( ! is_dir( $dir ) && ! mkdir( $dir, 0775, true ) && ! is_dir( $dir ) ) {
    fwrite( STDERR, "Could not create showcase asset directory.\n" );
    exit( 1 );
}

$palettes = array(
    array( '#ff0054', '#00c2d7', '#111111', '#fff7fb' ),
    array( '#00c2d7', '#111111', '#ff0054', '#f7fdff' ),
    array( '#111111', '#ff0054', '#00c2d7', '#fff7fb' ),
    array( '#ff7a00', '#ff0054', '#111111', '#fff8ef' ),
    array( '#5b3df5', '#00c2d7', '#111111', '#f8f7ff' ),
    array( '#00a86b', '#ffcc00', '#111111', '#f4fff9' ),
    array( '#ff0054', '#5b3df5', '#111111', '#fff7fb' ),
    array( '#00c2d7', '#ffcc00', '#111111', '#f4fdff' ),
);

function rgb( string $hex ): array {
    $hex = ltrim( $hex, '#' );
    return array_map( 'hexdec', str_split( $hex, 2 ) );
}

foreach ( $palettes as $index => $palette ) {
    $width = 1200;
    $height = 800;
    $image = imagecreatetruecolor( $width, $height );
    imageantialias( $image, true );

    $colors = array();
    foreach ( $palette as $hex ) {
        [ $r, $g, $b ] = rgb( $hex );
        $colors[] = imagecolorallocate( $image, $r, $g, $b );
    }

    imagefill( $image, 0, 0, $colors[3] );
    imagefilledpolygon( $image, array( 0, 0, 780, 0, 420, 800, 0, 800 ), 4, $colors[0] );
    imagefilledpolygon( $image, array( 690, 0, 1200, 0, 1200, 800, 930, 800 ), 4, $colors[1] );
    imagefilledrectangle( $image, 510, 0, 650, 800, $colors[2] );
    imagefilledellipse( $image, 760 + ( $index % 3 ) * 45, 260 + ( $index % 2 ) * 70, 300, 300, $colors[3] );
    imagefilledellipse( $image, 230 + ( $index % 2 ) * 80, 590, 220, 220, $colors[2] );

    $path = sprintf( '%s/editorial-%02d.png', $dir, $index + 1 );
    imagepng( $image, $path, 7 );
    imagedestroy( $image );
}
PHP

printf 'Seeding showcase categories and posts...\n'
design_id="$(wp_cli term create category 'Design' --slug=design --porcelain)"
culture_id="$(wp_cli term create category 'Culture' --slug=culture --porcelain)"
process_id="$(wp_cli term create category 'Process' --slug=process --porcelain)"
hidden_id="$(wp_cli term create category 'Hidden Showcase' --slug=hidden-showcase --porcelain)"
journal_id="$(wp_cli term create category 'Journal' --slug=journal --porcelain)"
[[ "${hidden_id}" == '5' ]] || fail "expected reserved hidden category ID 5, got ${hidden_id}"

post_titles=(
	'Candy Colors, Reframed'
	'Inside a Small Creative Studio'
	'Type, Rhythm, and White Space'
	'Building a Visual System That Lasts'
	'Notes from the Editorial Desk'
	'The Geometry of Everyday Objects'
	'When Old Interfaces Still Feel Fresh'
	'Keeping Personality in a Modern Theme'
)
post_slugs=(
	'candy-colors-reframed'
	'inside-small-creative-studio'
	'type-rhythm-white-space'
	'building-visual-system-lasts'
	'notes-editorial-desk'
	'geometry-everyday-objects'
	'old-interfaces-still-feel-fresh'
	'keeping-personality-modern-theme'
)
post_categories=("${design_id}" "${journal_id}" "${design_id}" "${process_id}" "${journal_id}" "${culture_id}" "${culture_id}" "${process_id}")
post_dates=(
	'2026-09-18 09:00:00'
	'2026-09-15 10:30:00'
	'2026-09-12 08:45:00'
	'2026-09-08 11:15:00'
	'2026-09-03 14:10:00'
	'2026-08-29 16:40:00'
	'2026-08-24 12:20:00'
	'2026-08-18 09:50:00'
)

featured_post_id=''
for index in "${!post_titles[@]}"; do
	asset_index=$(printf '%02d' "$((index + 1))")
	asset="/var/www/html/wp-content/showcase-assets/editorial-${asset_index}.png"
	attachment_id="$(wp_cli media import "${asset}" --title="${post_titles[$index]} artwork" --porcelain)"
	content='<p>Candy Cane pairs a compact editorial grid with strong color, generous white space, and classic WordPress publishing.</p><p>This showcase uses real posts, categories, menus, featured images, comments, and responsive theme templates inside a clean WordPress runtime.</p><blockquote><p>Modernized underneath, unmistakably Candy Cane on the surface.</p></blockquote><p>The theme remains intentionally lightweight while supporting current WordPress and PHP releases.</p>'
	post_id="$(wp_cli post create \
		--post_type=post \
		--post_status=publish \
		--post_title="${post_titles[$index]}" \
		--post_name="${post_slugs[$index]}" \
		--post_excerpt='A compact Candy Cane editorial story for the documentation showcase.' \
		--post_content="${content}" \
		--post_date="${post_dates[$index]}" \
		--porcelain)"
	wp_cli post term set "${post_id}" category "${post_categories[$index]}" --by=id >/dev/null
	wp_cli post meta update "${post_id}" _thumbnail_id "${attachment_id}" >/dev/null
	if [[ "${index}" -eq 0 ]]; then
		featured_post_id="${post_id}"
	fi
done

about_page_id="$(wp_cli post create \
	--post_type=page \
	--post_status=publish \
	--post_title='About Candy Cane' \
	--post_name='about-candy-cane' \
	--post_content='<p>Candy Cane is a preservation-first classic WordPress theme for portfolios, editorial sites, blogs, and creative studios.</p><h2>Classic structure, current runtime</h2><p>Its established menus, widget areas, image sizes, and visual grammar are preserved while modern WordPress APIs carry the runtime underneath.</p><h2>Designed to stay recognizable</h2><p>Updates focus on compatibility, accessibility, packaging, and maintainability rather than replacing the personality that made Candy Cane distinct.</p>' \
	--porcelain)"

wp_cli menu create 'Main Navigation' >/dev/null
wp_cli menu item add-custom 'Main Navigation' 'Home' "${SITE_URL}/" >/dev/null
wp_cli menu item add-custom 'Main Navigation' 'Design' "${SITE_URL}/?cat=${design_id}" >/dev/null
wp_cli menu item add-custom 'Main Navigation' 'Journal' "${SITE_URL}/?cat=${journal_id}" >/dev/null
wp_cli menu location assign 'Main Navigation' header-menu1 >/dev/null

wp_cli menu create 'Secondary Navigation' >/dev/null
wp_cli menu item add-post 'Secondary Navigation' "${about_page_id}" >/dev/null
wp_cli menu item add-custom 'Secondary Navigation' 'Search' "${SITE_URL}/?s=Candy" >/dev/null
wp_cli menu location assign 'Secondary Navigation' header-menu2 >/dev/null

wp_cli comment create --comment_post_ID="${featured_post_id}" --comment_author='Studio Reader' --comment_author_email='reader@example.test' --comment_content='The color and spacing feel unmistakably Candy Cane.' --comment_approved=1 >/dev/null

printf 'Capturing docs-grade screenshots...\n'
node "${REPO_ROOT}/tests/browser/docs-showcase.mjs"

printf 'Checking showcase runtime logs for fatal PHP failures...\n'
wordpress_logs="$(docker compose -f "${COMPOSE_FILE}" logs --no-color wordpress 2>&1 || true)"
if grep -Eiq 'PHP (Fatal error|Parse error)|Uncaught (Error|Exception)' <<<"${wordpress_logs}"; then
	printf '%s\n' "${wordpress_logs}" >&2
	fail 'fatal PHP error found in showcase runtime logs'
fi

printf 'Candy Cane docs showcase captured successfully.\n'
