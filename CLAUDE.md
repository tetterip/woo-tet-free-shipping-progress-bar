# Free Shipping Progress Bar — Plugin

A WordPress/WooCommerce plugin that displays a progress bar in the mini cart, side cart, and cart page (and anywhere via shortcode) showing how much more the customer needs to spend to qualify for free shipping.

## File structure

```
woo-free-shipping-progress-bar/
├── woo-free-shipping-progress-bar.php   # All plugin logic
└── assets/
    └── progress-bar.css                 # Static styles using CSS custom properties
```

Single-file plugin — no build step, no composer, no node_modules.

## How it works

**Threshold detection** (`ttrp_free_shipping_threshold`)
Iterates every WooCommerce shipping zone (including zone 0 / Rest of the World), collects `free_shipping` methods that have a `min_amount` requirement, and returns the lowest threshold found. Result is cached per-request via a `static` variable.

**Rendering** (`ttrp_render_shipping_progress_bar`)
Compares `WC()->cart->get_subtotal()` against the threshold, builds a percentage, and outputs the progress bar HTML. Messages and colors come from settings (`ttrp_setting()`), with hardcoded English defaults as fallback.

**Colors** are stored in `wp_options` as `ttrp_settings[color_*]` and injected as CSS custom properties (`--ttrp-border`, `--ttrp-bg`, etc.) via `wp_add_inline_style` on every front-end page load. The CSS sheet only uses `var()` references — no inline styles on elements.

**Hooks registered (front-end)**
| Hook | Purpose |
|---|---|
| `woocommerce_after_template_part` | Elementor Pro Menu Cart / Side Cart (fires after `cart/mini-cart.php`) |
| `woocommerce_widget_shopping_cart_before_buttons` | Default WC mini cart widget |
| `woocommerce_before_cart_totals` | Cart page |

**Shortcode**: `[free_shipping_bar]` — wraps the render function with `ob_start/ob_get_clean` so it can be embedded anywhere.

## Admin settings

**WooCommerce > Settings > Products > Free Shipping Bar**

Settings are stored as a single serialized array under the option key `ttrp_settings`. WooCommerce's Settings API handles save/load natively for array-style field IDs (`ttrp_settings[key]`).

| Setting key | Default |
|---|---|
| `msg_progress` | `Add {amount} more for free shipping!` — `{amount}` is replaced with `wc_price()` output |
| `msg_qualified` | `🎉 You have free shipping!` |
| `color_border` | `#008481` |
| `color_bg` | `#ebf7ee` |
| `color_text` | `#008481` |
| `color_remaining` | `#c32e2e` |
| `color_bar_start` | `#008481` |
| `color_bar_end` | `#2ecc71` |

## Naming conventions

All functions and options use the `ttrp_` prefix (Tetteris). CSS classes use the `ttrp-` BEM prefix.

## Extension point

`apply_filters( 'ttrp_free_shipping_thresholds', $thresholds )` — fired after zone detection, before `min()`. Use this to add, remove, or override thresholds programmatically.
