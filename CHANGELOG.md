# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
