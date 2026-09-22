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
- SHA-pinned GitHub Actions

Browser-level visual regression and full WordPress runtime validation remain release gates before a modernization release is considered consumer-ready.

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

GitHub Actions also runs the PHP 7.4–8.5 syntax matrix on pushes and pull requests.

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
