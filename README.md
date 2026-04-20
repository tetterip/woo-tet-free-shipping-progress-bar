# Free Shipping Progress Bar

A lightweight WooCommerce plugin that displays a progress bar showing customers how much more they need to spend to qualify for free shipping. Compatible with Elementor Pro Menu Cart, the default WooCommerce mini cart, and the cart page.

---

## Features

- Automatically reads the free shipping threshold from your WooCommerce shipping zones — no manual configuration required.
- Progress bar and messages update in real time on cart changes (Elementor AJAX fragments included).
- Customizable messages with a `{amount}` placeholder for the remaining amount.
- Six color settings (border, background, text, remaining amount highlight, bar gradient) configurable from the WordPress admin.
- Shortcode `[free_shipping_bar]` for embedding the bar anywhere on your site.
- No JavaScript, no observers, no race conditions — pure PHP + CSS.

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

---

## Installation

1. Download or clone this repository.
2. Zip the `woo-free-shipping-progress-bar/` folder.
3. Go to **WP Admin > Plugins > Add New > Upload Plugin** and upload the zip.
4. Activate the plugin.

The progress bar will appear automatically in the mini cart and on the cart page as soon as a free shipping method with a minimum order amount is configured in WooCommerce.

---

## Configuration

Navigate to **WooCommerce > Settings > Products > Free Shipping Bar**.

### Messages

| Setting | Default | Notes |
|---|---|---|
| Progress message | `Add {amount} more for free shipping!` | `{amount}` is replaced with the formatted remaining price |
| Qualified message | `🎉 You have free shipping!` | Shown when the threshold is reached |

### Colors

| Setting | Default |
|---|---|
| Border color | `#008481` |
| Background color | `#ebf7ee` |
| Text color | `#008481` |
| Remaining amount color | `#c32e2e` |
| Progress bar start color | `#008481` |
| Progress bar end color | `#2ecc71` |

---

## Shortcode

```
[free_shipping_bar]
```

Embeds the progress bar in any page, post, or widget area. The WooCommerce cart must be initialized on the page for the bar to display (it works on any page that loads the WC cart session, including pages with the Elementor menu cart widget).

---

## How the threshold is detected

The plugin scans all WooCommerce shipping zones (including the "Rest of the World" fallback zone) and collects every active **Free Shipping** method that requires a minimum order amount. It uses the **lowest** threshold found, which is the one closest to the customer. The detected thresholds can be overridden via the `ttrp_free_shipping_thresholds` filter:

```php
add_filter( 'ttrp_free_shipping_thresholds', function( $thresholds ) {
    // Force a specific threshold regardless of zone configuration.
    return array( 50 );
} );
```

---

## Hooks reference

| Hook | Type | Description |
|---|---|---|
| `ttrp_free_shipping_thresholds` | filter | Modify the array of detected thresholds before the minimum is selected |
| `woocommerce_after_template_part` | action (internal) | Used to inject the bar after the Elementor mini cart template |
| `woocommerce_widget_shopping_cart_before_buttons` | action (internal) | Used to inject the bar in the default WC mini cart widget |
| `woocommerce_before_cart_totals` | action (internal) | Used to inject the bar on the cart page |

---

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

Tetteris Michalis
