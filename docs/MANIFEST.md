# Candy Cane documentation manifest

## Screenshot authority

Public-facing screenshots must come from the deterministic `tests/docs-showcase.sh` runtime and `tests/browser/docs-showcase.mjs` capture pipeline.

The showcase runtime uses a clean WordPress installation, the current Candy Cane theme, real posts/pages/categories/comments/menus, deterministic editorial artwork, and Chromium at fixed desktop, tablet, and mobile viewports.

## Canonical visual assets

When committed, the following files under `docs/screenshots/` are the documentation authority:

- `desktop-home.png`
- `desktop-single.png`
- `desktop-page.png`
- `tablet-home.png`
- `mobile-home.png`
- `mobile-single.png`
- `desktop-keyboard-overlay.png`

Regression screenshots from `tests/browser-artifacts/` remain test evidence and must not be used as marketing imagery.

## Documentation surfaces

The README should use the desktop home capture as the primary hero and responsive captures for device coverage. Feature documentation may use the single-post, page, and keyboard-overlay captures where those images directly support the described behavior.
