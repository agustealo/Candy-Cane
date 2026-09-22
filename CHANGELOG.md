# Changelog

Candy Cane follows a preservation-first modernization policy. Compatibility work should improve WordPress, PHP, accessibility, and maintenance behavior without making existing users relearn or rebuild the theme.

## 0.10.0

Modernization release in progress.

### Compatibility foundation

- Centralized WordPress integration in the `Candy_Cane_Theme` bootstrap.
- Preserved the existing menu locations, widget-area IDs, image-size identifiers, classic templates, and Foundation-era layout classes.
- Replaced the retired custom image-processing pipeline with WordPress Core image APIs while retaining historical `bt_*` compatibility adapters.
- Removed external and embedded jQuery 1.7.1 copies and returned dependency ownership to WordPress.
- Replaced `query_posts()` with the canonical main-query lifecycle while preserving Candy Cane's historical home-category exclusions.
- Modernized document-title, body-open, comment, asset-enqueue, responsive-embed, and HTML5 integration.

### Templates and accessibility

- Modernized archive, page, single, 404, sidebar, search, and index templates without changing their established grid structure.
- Corrected single-post adjacent navigation.
- Restored post data after the custom Archives query.
- Added context-aware escaping and normalized the `candy-cane` text domain.
- Added skip navigation, keyboard-visible focus, keyboard access to image overlays, unique search-field IDs, and reduced-motion handling.
- Added narrowly scoped compatibility CSS instead of rewriting the historical visual system.

### Engineering and release maintenance

- Replaced the PHP 5.x / WordPress 4.x Travis configuration with SHA-pinned GitHub Actions.
- Added PHP syntax validation for PHP 7.4 through 8.5.
- Added WordPress Coding Standards and PHPCompatibilityWP gates.
- Aligned theme, Composer, and documentation licensing on GPLv2.
- Removed obsolete Dreamweaver synchronization metadata and an unused historical `functions-orig.php` backup.
- Preserved the pre-modernization stylesheet byte-for-byte as `legacy-style.css` while making `style.css` the current WordPress metadata entry point.

## 0.9.7

Historical Candy Cane release prior to the current preservation-first modernization program.
