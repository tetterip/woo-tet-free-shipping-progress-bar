# Free Shipping Progress Bar for WooCommerce — Plugin

A WordPress/WooCommerce plugin that displays a progress bar in the mini cart, side cart, and cart page (and anywhere via shortcode) showing how much more the customer needs to spend to qualify for free shipping.

## File structure

```
woo-tet-free-shipping-progress-bar/
├── woo-tet-free-shipping-progress-bar.php   # All plugin logic
├── ttrp-common/                         # Shared ttrp.gr library: updates + "All Plugins" page (synced from the update server repo; never edit)
├── assets/
│   ├── progress-bar.css                 # Static styles using CSS custom properties
│   └── ttrp-admin.css                   # Shared TTRP admin design system (enqueued on settings page only)
└── languages/
    ├── free-shipping-progress-bar.pot   # Translation template
    ├── free-shipping-progress-bar-el.po # Greek (source)
    └── free-shipping-progress-bar-el.mo # Greek (compiled)
```

No build step, no composer, no node_modules.

## How it works

**Threshold detection** (`ttrp_free_shipping_threshold`)
Iterates every WooCommerce shipping zone (including zone 0 / Rest of the World), collects `free_shipping` methods that have a `min_amount` requirement, and returns the lowest threshold found. Result is cached per-request via a `static` variable.

**Rendering** (`ttrp_render_shipping_progress_bar`)
Compares `WC()->cart->get_displayed_subtotal()` against the threshold, builds a percentage, and outputs the progress bar HTML. `get_displayed_subtotal()` is used (not `get_subtotal()`) so the value matches the tax-inclusive total the customer sees on tax-inclusive stores. Messages and colors come from settings (`ttrp_setting()`), with hardcoded English defaults as fallback.

**Colors** are stored in `wp_options` as `ttrp_settings[color_*]` and injected as CSS custom properties (`--ttrp-border`, `--ttrp-bg`, etc.) via `wp_add_inline_style` on every front-end page load. The CSS sheet only uses `var()` references — no inline styles on elements.

**Version constant**
`TTRP_FSB_VERSION` is defined at plugin load via `get_file_data( __FILE__, ... )['Version']`, making the plugin header `Version:` line the single source of truth. Used for the admin settings version pill, front-end stylesheet version, and admin stylesheet version.

**HPOS compatibility**
Declared via `FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true )` on the `before_woocommerce_init` action. The plugin does not use orders, so compatibility is unconditional. Without this declaration WooCommerce shows an incompatibility notice.

**Hooks registered (front-end)**
| Hook | Purpose |
|---|---|
| `woocommerce_after_template_part` | Elementor Pro Menu Cart / Side Cart (fires after `cart/mini-cart.php`) — skipped when `show_in_mini_cart` is `no` |
| `woocommerce_widget_shopping_cart_before_buttons` | Default WC mini cart widget — skipped when `show_in_mini_cart` is `no` |
| *(varies by `cart_position` setting)* | Classic cart page — skipped entirely when `show_in_cart` is `no` |
| `render_block_woocommerce/proceed-to-checkout-block` | Block cart page — prepends bar HTML before the block's rendered output; skipped when `show_in_cart` is `no` |
| `render_block_woocommerce/mini-cart-footer-block` | Block mini cart drawer — prepends bar HTML before the footer block; skipped when `show_in_mini_cart` is `no` |

The `before_checkout_button` cart position uses `woocommerce_proceed_to_checkout` at priority **19**. WooCommerce registers the button at priority 20, so we slot in just before it. `woocommerce_cart_totals_after_order_total` was tried but fires inside the `<table>` element, landing visually under the "Cart totals" heading.

**Shortcode**: `[free_shipping_bar]` — wraps the render function with `ob_start/ob_get_clean` so it can be embedded anywhere.

## Admin settings

**ttrp.gr Plugins > Free Shipping Bar**

Settings are stored as a single serialized array under the option key `ttrp_settings`. WooCommerce's Settings API handles save/load natively for array-style field IDs (`ttrp_settings[key]`). The page is rendered via `ttrp_fsb_render_settings()` using `woocommerce_admin_fields()` and `woocommerce_update_options()`. Admin CSS (`ttrp-admin.css` + `woocommerce_admin_styles`) is enqueued by `ttrp_fsb_enqueue_admin_assets()` only when `?page=ttrp-free-shipping-bar`. The page uses `.ttrp-plugin-header` / `.ttrp-settings-footer` from the shared design system.

| Setting key | Default |
|---|---|
| `msg_progress` | `Add {amount} more for free shipping!` — `{amount}` is replaced with `wc_price()` output |
| `msg_qualified` | `🎉 You have free shipping!` |
| `color_main` | `#008481` — drives border, text, `--ttrp-bar-start`; bg and bar-end are derived via HSL |
| `color_remaining` | `#c32e2e` |
| `show_in_mini_cart` | `yes` |
| `show_in_cart` | `yes` |
| `cart_position` | `before_cart_totals` — one of `before_cart`, `after_cart_table`, `before_cart_totals`, `after_cart_totals`, `before_checkout_button` |

**Derived colors** (not stored, computed on each page load by `ttrp_derive_bg_color()` and `ttrp_derive_bar_end_color()`):
- Background: `color_main` at 15% opacity (`rgba`)
- Bar end: `color_main` hue, saturation ×1.1, lightness +25% (capped at 75%)

## Naming conventions

All functions and options use the `ttrp_` prefix (Tetteris). CSS classes use the `ttrp-` BEM prefix.

## Extension point

`apply_filters( 'ttrp_free_shipping_thresholds', $thresholds )` — fired after zone detection, before `min()`. Use this to add, remove, or override thresholds programmatically.
