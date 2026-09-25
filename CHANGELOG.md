# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.13] - 2026-09-25

### Changed
- `update-checker.php`: passes plugin icons and banners from the update server to WordPress, so the plugin gets its icon in **Dashboard → Updates** and the details modal.

---

## [1.3.12] - 2026-06-04

### Changed
- Keep the "Free Shipping Bar" name in English in all locales — the admin menu item, settings-page title/heading, and the **Products → Free Shipping Bar** WooCommerce section — per the suite convention that plugin titles, admin menu items, and WooCommerce tabs stay in English. (Was translated to "Μπάρα Δωρεάν Αποστολής".)

### Fixed
- Added missing Greek translations for admin strings that were showing in English: "Save settings", "Settings saved.", and the footer credit ("Free Shipping Bar by", with the product name kept in English). Regenerated the `.pot` and recompiled the `.mo`.

---

## [1.3.11] - 2026-05-27

### Fixed
- Remove UTF-8 BOM from `update-checker.php` — the BOM was causing a stray character to be output before headers on sites that use the update checker's directory-rename feature.

---

## [1.3.10] - 2026-05-27

### Fixed
- Guard `$wp_filesystem` global before calling `move()` in the update checker's `fix_directory_name()` — call `WP_Filesystem()` if not already initialised to prevent a fatal error during plugin updates.

### Changed
- Tested up to WordPress 7.0.

### Added
- Block cart support via `render_block_woocommerce/proceed-to-checkout-block` filter — injects the progress bar above the checkout button on sites using the WooCommerce block cart.
- Block mini cart support via `render_block_woocommerce/mini-cart-footer-block` filter — injects the bar above the footer of the block mini cart drawer. Both locations respect the existing **Show in mini cart** / **Show on cart page** toggles.

### Changed
- Plugin version is now derived once from the file header via `get_file_data()` and stored in the `TTRP_FSB_VERSION` constant. The admin settings page version pill, front-end stylesheet version, and admin stylesheet version all read from this constant — the plugin header is now the single source of truth.

---

## [1.3.8] - 2026-05-14

### Changed
- Admin settings footer logo width increased to 40 px and opacity set to 1 in `assets/ttrp-admin.css`.

---

## [1.3.7] - 2026-05-14

### Fixed
- Load plugin textdomain on `init` instead of `plugins_loaded` to suppress the WordPress 6.7+ `_load_textdomain_just_in_time` notice.

---

## [1.3.6] - 2026-05-13

### Fixed
- Add missing `assets/ttrp.svg` so the settings page header logo renders correctly.

---

## [1.3.5] - 2026-05-13

### Changed
- Admin settings footer now uses `ttrp.svg` instead of `ttrp-logo.svg`.

---

## [1.3.4] - 2026-05-13

### Fixed
- Removed UTF-8 BOM from the main plugin file. The BOM was being output before the HTML document, causing a stray `﻿` character to appear at the start of every page.

---

## [1.3.3] - 2026-05-13

### Fixed
- Declared HPOS (High-Performance Order Storage / Custom Order Tables) compatibility via `FeaturesUtil::declare_compatibility()`. WooCommerce was showing an incompatibility notice for this plugin despite it not using orders at all.

---

## [1.3.2] - 2026-05-13

### Added
- `assets/ttrp-admin.css` — shared TTRP admin design system (badges, notices, order panel, settings header/footer) now bundled in this plugin.
- `ttrp_fsb_enqueue_admin_assets()` — admin CSS is now enqueued on the plugin settings page (previously missing entirely).

### Changed
- Admin settings page now uses `.ttrp-wrap`, `.ttrp-plugin-header` (with version pill), and `.ttrp-settings-footer` (ttrp.gr logo and link) for a consistent look across all ttrp.gr plugins.

---

## [1.3.1] - 2026-05-13

### Changed
- Renamed main plugin file to `woo-tet-free-shipping-progress-bar.php` to align with the naming convention used across ttrp.gr plugins.
- Updated update checker slug accordingly.
- Updated translation source file references in `.pot` and `.po`.

---

## [1.3.0] - 2026-05-12

### Changed
- Settings page moved from **WooCommerce > Settings > Products > Free Shipping Bar** to the new **ttrp.gr Plugins > Free Shipping Bar** top-level admin menu, shared across all ttrp.gr plugins.

---

## [1.2.4] - 2026-05-08

### Fixed
- Corrected author name order in README (Michalis Tetteris).

---

## [1.2.3] - 2026-05-08

### Fixed
- Updated Greek (`.po`/`.mo`) translation files to reflect all strings added since v1.1.0: new color settings, visibility toggles, and the renamed plugin title. Removed obsolete color strings from the v1.0 settings.

---

## [1.2.2] - 2026-05-08

### Changed
- Plugin renamed to **Free Shipping Progress Bar for WooCommerce**.

---

## [1.2.1] - 2026-05-08

### Fixed
- Progress bar now uses `get_displayed_subtotal()` instead of `get_subtotal()` when calculating the remaining amount. On tax-inclusive stores, `get_subtotal()` returned the ex-tax cart total, causing the remaining amount shown to be higher than the actual gap to the threshold.

---

## [1.2.0] - 2026-05-08

### Added
- Automatic update checker (`update-checker.php`) — the plugin now checks for new versions against the update server at `plugins.ttrp.gr` and integrates with the standard WordPress updates UI.

---

## [1.1.0] - 2026-04-23

### Added
- **Show in mini cart** and **Show on cart page** toggles — each location can now be independently enabled or disabled from **WooCommerce > Settings > Products > Free Shipping Bar**.

### Changed
- Color settings simplified from six pickers to two: **Main color** (controls border, text, and bar gradient start) and **Remaining amount color**. Background and bar gradient end are now derived automatically — the background is the main color at 15% opacity and the bar gradient end is a lighter HSL shade of the same hue.
- **Before checkout button** cart position now uses the `woocommerce_cart_totals_after_order_total` hook, which reliably places the bar above the checkout button regardless of theme template overrides. Previously used `woocommerce_proceed_to_checkout`, which rendered after the button on some themes.

### Upgrade notes
- Existing custom color settings (`color_border`, `color_bg`, `color_text`, `color_bar_start`, `color_bar_end`) are no longer read. After upgrading, set **Main color** to your preferred brand color — the plugin will derive the rest automatically.

---

## [1.0.1] - 2026-04-20

### Added
- Internationalization (i18n) support with `load_plugin_textdomain()`. Ships with English (default) and Greek (`el`) translations.
- Language files: `free-shipping-progress-bar.pot` template and `free-shipping-progress-bar-el.po` Greek translation under `languages/`. WordPress 6.5+ loads `.po` files directly; older installs can compile with `msgfmt`.
- **Cart page position** setting under **WooCommerce > Settings > Products > Free Shipping Bar**: choose where the bar appears on the cart page — before/after the cart table, before/after cart totals, or before the checkout button. Defaults to "Before cart totals" (previous behaviour).

### Changed
- Default front-end messages (`msg_progress`, `msg_qualified`) are now wrapped in `__()` so they are translatable even before the user saves custom values.
- Mini cart hooks and cart page hook are now registered separately: mini cart positions are always fixed; the cart page position is driven by the new setting.

---

## [1.0.0] - 2026-04-20

### Added
- Initial release.
- Automatic free shipping threshold detection from WooCommerce shipping zones (all zones including "Rest of the World").
- Progress bar rendered in Elementor Pro Menu Cart / Side Cart, default WooCommerce mini cart widget, and the cart page.
- Shortcode `[free_shipping_bar]` for manual placement in pages, posts, and widget areas.
- Admin settings panel under **WooCommerce > Settings > Products > Free Shipping Bar**:
  - Configurable progress message with `{amount}` placeholder.
  - Configurable qualified (free shipping reached) message.
  - Six color pickers: border, background, text, remaining amount highlight, progress bar gradient start and end.
- Colors injected as CSS custom properties (`--ttrp-border`, `--ttrp-bg`, etc.) for clean separation between logic and presentation.
- `ttrp_free_shipping_thresholds` filter for programmatic threshold overrides.
- Compatible with Elementor AJAX cart fragment updates — no JavaScript required.
