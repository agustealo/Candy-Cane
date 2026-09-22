# Candy Cane for WordPress

Candy Cane is a responsive classic WordPress theme for portfolios, creative work, blogs, and magazine-style sites. It keeps the original Candy Cane layout and visual character while its WordPress integration is being modernized for current WordPress and PHP releases.

This project follows a preservation-first policy: existing sites should continue to feel like Candy Cane after an update. The theme is not being converted into a block theme and does not require users to rebuild menus, widgets, posts, pages, or theme content.

## Compatibility contract

Candy Cane intentionally preserves its long-standing public theme contracts:

- Classic PHP template hierarchy
- `header-menu1` main navigation location
- `header-menu2` secondary navigation location
- `right_sidebar` widget area
- `footer_1`, `footer_2`, `footer_3`, and `footer_4` widget areas
- `front` image size at 210 × 210 with center/top cropping
- `name_size` image size at 460 × 345 with center/top cropping
- Existing Foundation-era grid classes and the familiar Candy Cane front-end structure

Modernization work is performed underneath those contracts rather than replacing them.

## Current modernization baseline

The maintained code line now uses WordPress Core for theme setup, asset loading, image editing, comments, query handling, document titles, responsive embeds, and other framework responsibilities that older Candy Cane releases implemented themselves.

The repository quality gate currently validates:

- PHP syntax on PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, and 8.5
- WordPress Coding Standards
- PHPCompatibilityWP
- Candy Cane's preserved menu, sidebar, image-size, release-metadata, and legacy-style contracts
- A fresh WordPress 7.1.1 installation with Candy Cane activated against real seeded posts, pages, categories, menus, a featured image, rendered theme routes, public assets, and PHP runtime logs
- Browser-level visual continuity against the last untouched pre-modernization Candy Cane commit across home, single, page, category, search, and 404 routes at desktop, tablet, and compact widths
- Keyboard-focus behavior for the historical image-card overlay and rejection of current-theme browser page errors
- Pixel-difference and page-height budgets derived from the verified preservation baseline, with screenshot and diff evidence uploaded for every browser run
- A deterministic consumer ZIP built from Git's committed tree, audited for release-only contents, installed into a clean WordPress runtime through WP-CLI, activated, rendered, and checked for packaged runtime failures
- SHA-pinned GitHub Actions and pinned runtime container images

The visual gate compares the historical and current themes against the same deterministic WordPress database and content fixture. It is designed to expose product drift without treating intentional compatibility and accessibility corrections as a license for broad visual changes.

The package gate separately proves what a customer installs. It starts from an official WordPress image with no repository-mounted Candy Cane theme, installs the generated ZIP, and validates the installed package rather than the source checkout.

## Installation

1. Install the `Candy-Cane` directory in `wp-content/themes/`, or install the theme ZIP through WordPress.
2. Activate **Candy Cane** in Appearance → Themes.
3. Assign the existing menu locations under Appearance → Menus.
4. Configure the right sidebar and footer widget areas as needed.
5. Continue publishing posts and pages normally. Featured images are used by the existing Candy Cane post-grid presentation.

No content migration is required when updating from an earlier Candy Cane installation.

## Development

Candy Cane remains a deliberately small classic theme. New framework layers should not be introduced unless they solve a concrete compatibility or maintenance problem without changing the established product experience.

Install the development quality tooling with:

```bash
composer install
```

Run the WordPress and PHP compatibility standards gate with:

```bash
composer standards
```

Run the real WordPress runtime smoke locally with Docker Compose:

```bash
bash tests/runtime-smoke.sh
```

The runtime smoke creates an isolated WordPress installation, validates the theme against real content, and removes its Docker volumes when the test finishes.

The browser-preservation harness lives under `tests/browser/`. CI checks out the historical reference theme at the pinned pre-modernization commit, runs both themes against the same runtime fixture, and produces historical, current, and diff screenshots plus `visual-report.json`.

## Release packaging

The consumer package is built from Git's committed tree using the export rules in `.gitattributes`. Development-only files such as CI workflows, tests, build scripts, Composer tooling, local dependencies, and repository metadata are excluded from the archive.

Build the installable ZIP and SHA-256 checksum with:

```bash
bash scripts/build-release.sh
```

The output is written to `dist/Candy-Cane-<version>.zip` and `dist/Candy-Cane-<version>.zip.sha256`. CI builds the same ref twice and requires identical checksums, audits the ZIP manifest, then installs that ZIP into a clean WordPress 7.1.1 runtime before accepting the release package.

## Architecture

The canonical theme bootstrap lives in `inc/class-candy-cane-theme.php`. It owns WordPress setup, menus, widget areas, image sizes, asset registration, and main-query compatibility behavior.

Legacy public helper names that may be used by child themes are retained as compatibility adapters where practical instead of being removed abruptly.

`stylesheets/modern.css` contains narrowly scoped compatibility and accessibility refinements layered on top of the historical Candy Cane styles. The original grid and visual system are intentionally not being rewritten wholesale.

## Accessibility

Modern Candy Cane includes keyboard-visible focus states, a skip-to-content link, keyboard access to post-image overlays, unique search-field IDs, and reduced-motion handling while preserving the original visual presentation.

## Historical foundation

Candy Cane originated in the Foundation-era WordPress ecosystem and includes historical CSS patterns derived from ZURB Foundation and common HTML5 Boilerplate-era practices. Those origins are retained where they remain part of Candy Cane's layout contract, while obsolete runtime behavior is progressively replaced with native WordPress functionality.

## License

Candy Cane is licensed under the **GNU General Public License v2.0**. See the license declaration in `style.css`.

## Maintainer

Candy Cane is maintained by Agustealo Johnson.

Repository: https://github.com/agustealo/Candy-Cane
