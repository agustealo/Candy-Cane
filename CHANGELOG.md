# Changelog

Candy Cane follows a preservation-first modernization policy. Compatibility work should improve WordPress, PHP, accessibility, and maintenance behavior without making existing users relearn or rebuild the theme.

## 0.10.2 - 2026-09-22

Preservation-safe navigation semantics.

### Accessibility

- Removed the redundant `role="navigation"` landmark from the shared header wrapper.
- Added distinct accessible names to the existing Main Navigation and Secondary Navigation `<nav>` elements without changing their menu locations, depth, markup classes, layout, or visual treatment.
- Added real WordPress runtime assertions proving both named navigation landmarks render and the old wrapper landmark does not return.

## 0.10.1 - 2026-09-22

Preservation-safe authoring and presentation polish.

### Editor and theme presentation

- Added scoped editor styles so WordPress editor content better matches Candy Cane typography and content treatment without importing the Foundation-era front-end page chrome.
- Promoted the canonical root theme screenshot to a deterministic 1200×900 capture from a clean real WordPress runtime.
- Extended Docs Showcase to generate and byte-verify both public documentation imagery and the WordPress `screenshot.png` theme preview.

### Verification

- Added release-package assertions for editor-style support and the canonical editor stylesheet.
- Added preservation-contract proof for the editor-style integration and 1200×900 PNG theme screenshot.
- Kept optional custom-logo, custom-header, custom-background, wide-alignment, block-pattern, and block-style expansion out of this patch so existing Candy Cane users retain the established product surface.

## 0.10.0 - 2026-09-22

Preservation-first modernization release for current WordPress and PHP runtimes.

### Compatibility foundation

- Centralized WordPress integration in the `Candy_Cane_Theme` bootstrap.
- Preserved the existing menu locations, widget-area IDs, image-size identifiers, classic templates, and Foundation-era layout classes.
- Replaced the retired custom image-processing pipeline with WordPress Core image APIs while retaining historical `bt_*` compatibility adapters.
- Removed external and embedded jQuery 1.7.1 copies and returned dependency ownership to WordPress.
- Replaced `query_posts()` with the canonical main-query lifecycle while preserving Candy Cane's historical home-category exclusions.
- Modernized document-title, body-open, comment, asset-enqueue, responsive-embed, and HTML5 integration.
- Stopped the theme from forcibly hiding WordPress's front-end admin bar.

### Templates and accessibility

- Modernized archive, page, single, 404, sidebar, search, and index templates without changing their established grid structure.
- Corrected single-post adjacent navigation.
- Restored post data after the custom Archives query.
- Added context-aware escaping and normalized the `candy-cane` text domain.
- Added skip navigation, keyboard-visible focus, keyboard access to image overlays, unique search-field IDs, and reduced-motion handling.
- Added narrowly scoped compatibility CSS instead of rewriting the historical visual system.
- Restored the historical 250px compact home-card presentation while retaining the canonical 210×210 WordPress `front` media size and the historical 210px archive/search card behavior.

### Engineering and release maintenance

- Replaced the PHP 5.x / WordPress 4.x Travis configuration with SHA-pinned GitHub Actions.
- Added PHP syntax validation for PHP 7.4 through 8.5.
- Added WordPress Coding Standards and PHPCompatibilityWP gates.
- Added a preservation-contract gate covering release metadata and the historical menu, sidebar, image-size, and stylesheet contracts.
- Added a real WordPress 7.1.1 + PHP 8.3 runtime smoke that performs a fresh install, activates Candy Cane, seeds real content, validates the historical category exclusions and registered contracts, renders home/single/page/archive/search/404 surfaces, checks public stylesheets, and rejects fatal PHP runtime failures.
- Added a Playwright browser-preservation gate against the last untouched pre-modernization Candy Cane commit using the same deterministic WordPress fixture for both themes.
- Added 18 historical/current screenshot comparisons across six routes and desktop, tablet, and compact viewports, with pixel-diff images and JSON evidence retained by CI.
- Tightened visual budgets to measured preservation baselines: 0.5% for ordinary surfaces, route/viewport-specific single-post limits up to 3.5%, compact 404 up to 7.5%, and 2% maximum page-height drift.
- Added browser checks for keyboard access to image-card overlays and current-theme page errors.
- Added canonical `git archive` packaging rules that exclude CI, tests, build scripts, Composer tooling, repository metadata, local dependencies, and other development-only files from the consumer ZIP.
- Added deterministic `Candy-Cane-<version>.zip` and SHA-256 generation from the committed Git tree.
- Kept the friendly mixed-case archive filename while packaging the theme under the canonical WordPress directory slug `candy-cane/`.
- Added a clean release-package runtime that proves Candy Cane is absent before installation, installs the generated ZIP through WP-CLI, activates it, validates packaged menu/sidebar/image-size contracts, renders real content, serves packaged stylesheets, and rejects fatal PHP errors.
- Added the official WordPress Theme Check `20260821` suite against the exact theme installed from the generated consumer ZIP. Release-blocking Theme Check findings are now zero; remaining output is recommendation-only.
- Added `Tested up to: 7.1`, current package metadata, and a 2014-2026 copyright notice.
- Added CI retention of the exact installable ZIP, SHA-256 checksum, and Theme Check JSON report as release evidence.
- Aligned theme, Composer, and documentation licensing on GPLv2.
- Removed obsolete Dreamweaver synchronization metadata and an unused historical `functions-orig.php` backup.
- Preserved the historical stylesheet's rendering declarations and layout behavior in `legacy-style.css`; the only release-compliance edits in that file are non-rendering comment/capitalization cleanup.

## 0.9.7

Historical Candy Cane release prior to the current preservation-first modernization program.