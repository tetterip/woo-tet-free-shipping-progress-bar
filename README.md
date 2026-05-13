# Free Shipping Progress Bar for WooCommerce

A lightweight WooCommerce plugin that displays a progress bar showing customers how much more they need to spend to qualify for free shipping. Compatible with Elementor Pro Menu Cart, the default WooCommerce mini cart, and the cart page.

---

## Features

- Automatically reads the free shipping threshold from your WooCommerce shipping zones — no manual configuration required.
- Progress bar and messages update in real time on cart changes (Elementor AJAX fragments included).
- Customizable messages with a `{amount}` placeholder for the remaining amount.
- Two color settings: a single **main color** (border, text, bar gradient) and a **remaining amount color**. Background is the main color at 15% opacity; bar gradient end is a lighter shade of the same hue — both derived automatically.
- Configurable cart page position: before/after the cart table, before/after cart totals, or before the checkout button.
- Toggle the bar on or off independently for the mini cart and the cart page.
- Shortcode `[free_shipping_bar]` for embedding the bar anywhere on your site.
- Translation-ready — ships with English (default) and Greek (`el`) translations.
- No JavaScript, no observers, no race conditions — pure PHP + CSS.
- Declared compatible with WooCommerce HPOS (High-Performance Order Storage).

---

## Requirements

- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

---

## Installation

1. Download the latest zip from the [Releases](https://github.com/tetterip/woo-free-shipping-progress-bar/releases) page.
2. Go to **WP Admin > Plugins > Add New > Upload Plugin** and upload the zip.
3. Activate the plugin.

The progress bar will appear automatically in the mini cart and on the cart page as soon as a free shipping method with a minimum order amount is configured in WooCommerce.

---

## Configuration

Navigate to **ttrp.gr Plugins > Free Shipping Bar**.

### Messages

| Setting | Default | Notes |
|---|---|---|
| Progress message | `Add {amount} more for free shipping!` | `{amount}` is replaced with the formatted remaining price |
| Qualified message | `🎉 You have free shipping!` | Shown when the threshold is reached |

### Colors

| Setting | Default | Notes |
|---|---|---|
| Main color | `#008481` | Controls border, text, and bar gradient start. Background and bar gradient end are derived automatically. |
| Remaining amount color | `#c32e2e` | Color of the price shown in the progress message. |

### Visibility

| Setting | Default |
|---|---|
| Show in mini cart | Enabled |
| Show on cart page | Enabled |

Each location can be toggled independently. Disabling "Show on cart page" also hides the **Cart page position** setting (though it has no effect either way when the cart bar is off).

### Cart page position

| Option | Hook used |
|---|---|
| Before the cart table | `woocommerce_before_cart` |
| After the cart table | `woocommerce_after_cart_table` |
| **Before cart totals** *(default)* | `woocommerce_before_cart_totals` |
| After cart totals | `woocommerce_after_cart_totals` |
| Before checkout button | `woocommerce_proceed_to_checkout` (priority 19) |

---

## Shortcode

```
[free_shipping_bar]
```

Embeds the progress bar in any page, post, or widget area. The WooCommerce cart must be initialized on the page for the bar to display (it works on any page that loads the WC cart session, including pages with the Elementor menu cart widget).

---

## Translations

The plugin is fully translation-ready. Language files live in the `languages/` directory.

| File | Language |
|---|---|
| `free-shipping-progress-bar.pot` | Template (all source strings) |
| `free-shipping-progress-bar-el.po` | Greek (source) |
| `free-shipping-progress-bar-el.mo` | Greek (compiled) |

Both the `.po` and compiled `.mo` are bundled, so Greek works on all WordPress versions out of the box.

To add a new language, copy the `.pot` file, rename it to `free-shipping-progress-bar-{locale}.po` (e.g. `free-shipping-progress-bar-de_DE.po`), fill in the translations, then compile:

```bash
msgfmt languages/free-shipping-progress-bar-de_DE.po -o languages/free-shipping-progress-bar-de_DE.mo
```

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
| *(varies)* | action (internal) | Cart page hook — determined by the **Cart page position** setting |

---

## License

GPL-2.0-or-later — see [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

## Author

Michalis Tetteris
