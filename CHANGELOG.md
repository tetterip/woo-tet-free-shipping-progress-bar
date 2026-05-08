# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
