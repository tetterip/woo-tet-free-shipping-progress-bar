<?php
/**
 * Plugin Name: Free Shipping Progress Bar
 * Plugin URI:  https://github.com/tetterip/woo-free-shipping-progress-bar
 * Description: Displays a progress bar showing how much more a customer needs to spend to qualify for free shipping. Compatible with Elementor Pro Menu Cart, WooCommerce mini cart, and the cart page.
 * Version:     1.1.0
 * Author:      Tetteris Michalis
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: free-shipping-progress-bar
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'plugins_loaded', 'ttrp_init' );

function ttrp_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    load_plugin_textdomain( 'free-shipping-progress-bar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Front-end styles
    add_action( 'wp_enqueue_scripts', 'ttrp_enqueue_styles' );

    // Mini cart — conditional on setting
    if ( ttrp_setting( 'show_in_mini_cart' ) !== 'no' ) {
        add_action( 'woocommerce_after_template_part',                 'ttrp_maybe_render_after_template', 10, 1 );
        add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'ttrp_render_shipping_progress_bar', 5 );
    }

    // Cart page — conditional on setting, position driven by setting
    if ( ttrp_setting( 'show_in_cart' ) !== 'no' ) {
        $cart_hooks = array(
            'before_cart'            => array( 'woocommerce_before_cart',                    10 ),
            'after_cart_table'       => array( 'woocommerce_after_cart_table',               10 ),
            'before_cart_totals'     => array( 'woocommerce_before_cart_totals',             10 ),
            'after_cart_totals'      => array( 'woocommerce_after_cart_totals',              10 ),
            'before_checkout_button' => array( 'woocommerce_proceed_to_checkout', 19 ),
        );
        $position = ttrp_setting( 'cart_position' );
        if ( ! array_key_exists( $position, $cart_hooks ) ) {
            $position = 'before_cart_totals';
        }
        list( $hook, $priority ) = $cart_hooks[ $position ];
        add_action( $hook, 'ttrp_render_shipping_progress_bar', $priority );
    }

    // Shortcode: [free_shipping_bar]
    add_shortcode( 'free_shipping_bar', 'ttrp_shortcode' );

    // Admin: WooCommerce > Settings > Products > Free Shipping Bar
    add_filter( 'woocommerce_get_sections_products', 'ttrp_add_settings_section' );
    add_filter( 'woocommerce_get_settings_products', 'ttrp_get_settings', 10, 2 );
}

// ---------------------------------------------------------------------------
// Settings helpers
// ---------------------------------------------------------------------------

function ttrp_defaults() {
    return array(
        'msg_progress'      => __( 'Add {amount} more for free shipping!', 'free-shipping-progress-bar' ),
        'msg_qualified'     => __( '🎉 You have free shipping!', 'free-shipping-progress-bar' ),
        'color_main'        => '#008481',
        'color_remaining'   => '#c32e2e',
        'show_in_mini_cart' => 'yes',
        'show_in_cart'      => 'yes',
        'cart_position'     => 'before_cart_totals',
    );
}

function ttrp_setting( $key ) {
    $defaults = ttrp_defaults();
    $options  = get_option( 'ttrp_settings', array() );
    $value    = isset( $options[ $key ] ) ? $options[ $key ] : '';
    return $value !== '' ? $value : ( $defaults[ $key ] ?? '' );
}

function ttrp_cart_position_options() {
    return array(
        'before_cart'            => __( 'Before the cart table', 'free-shipping-progress-bar' ),
        'after_cart_table'       => __( 'After the cart table', 'free-shipping-progress-bar' ),
        'before_cart_totals'     => __( 'Before cart totals', 'free-shipping-progress-bar' ),
        'after_cart_totals'      => __( 'After cart totals', 'free-shipping-progress-bar' ),
        'before_checkout_button' => __( 'Before checkout button', 'free-shipping-progress-bar' ),
    );
}

// ---------------------------------------------------------------------------
// Color helpers — derive background and bar-end from the main color
// ---------------------------------------------------------------------------

function ttrp_hex_to_rgb( $hex ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen( $hex ) === 3 ) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return array(
        hexdec( substr( $hex, 0, 2 ) ) / 255,
        hexdec( substr( $hex, 2, 2 ) ) / 255,
        hexdec( substr( $hex, 4, 2 ) ) / 255,
    );
}

function ttrp_rgb_to_hsl( $r, $g, $b ) {
    $max = max( $r, $g, $b );
    $min = min( $r, $g, $b );
    $l   = ( $max + $min ) / 2;
    if ( $max === $min ) {
        return array( 0, 0, $l );
    }
    $d = $max - $min;
    $s = $l > 0.5 ? $d / ( 2 - $max - $min ) : $d / ( $max + $min );
    switch ( $max ) {
        case $r:
            $h = ( $g - $b ) / $d + ( $g < $b ? 6 : 0 );
            break;
        case $g:
            $h = ( $b - $r ) / $d + 2;
            break;
        default:
            $h = ( $r - $g ) / $d + 4;
    }
    return array( $h / 6, $s, $l );
}

function ttrp_hue_to_rgb( $p, $q, $t ) {
    if ( $t < 0 ) { $t += 1; }
    if ( $t > 1 ) { $t -= 1; }
    if ( $t < 1 / 6 ) { return $p + ( $q - $p ) * 6 * $t; }
    if ( $t < 1 / 2 ) { return $q; }
    if ( $t < 2 / 3 ) { return $p + ( $q - $p ) * ( 2 / 3 - $t ) * 6; }
    return $p;
}

function ttrp_hsl_to_hex( $h, $s, $l ) {
    if ( $s == 0 ) {
        $val = (int) round( $l * 255 );
        return sprintf( '#%02x%02x%02x', $val, $val, $val );
    }
    $q = $l < 0.5 ? $l * ( 1 + $s ) : $l + $s - $l * $s;
    $p = 2 * $l - $q;
    $r = ttrp_hue_to_rgb( $p, $q, $h + 1 / 3 );
    $g = ttrp_hue_to_rgb( $p, $q, $h );
    $b = ttrp_hue_to_rgb( $p, $q, $h - 1 / 3 );
    return sprintf( '#%02x%02x%02x', (int) round( $r * 255 ), (int) round( $g * 255 ), (int) round( $b * 255 ) );
}

// Main color at 15% opacity — used for the bar background.
function ttrp_derive_bg_color( $hex ) {
    list( $r, $g, $b ) = ttrp_hex_to_rgb( $hex );
    return sprintf( 'rgba(%d,%d,%d,0.15)', (int) round( $r * 255 ), (int) round( $g * 255 ), (int) round( $b * 255 ) );
}

// Lighter, slightly brighter shade of the main color — used as bar gradient end.
function ttrp_derive_bar_end_color( $hex ) {
    list( $r, $g, $b ) = ttrp_hex_to_rgb( $hex );
    list( $h, $s, $l ) = ttrp_rgb_to_hsl( $r, $g, $b );
    return ttrp_hsl_to_hex( $h, min( 1.0, $s * 1.1 ), min( 0.75, $l + 0.25 ) );
}

// ---------------------------------------------------------------------------
// WooCommerce Settings tab (Products > Free Shipping Bar)
// WC handles read/write for array-style IDs (ttrp_settings[key]) natively.
// ---------------------------------------------------------------------------

function ttrp_add_settings_section( $sections ) {
    $sections['free_shipping_bar'] = __( 'Free Shipping Bar', 'free-shipping-progress-bar' );
    return $sections;
}

function ttrp_get_settings( $settings, $current_section ) {
    if ( $current_section !== 'free_shipping_bar' ) {
        return $settings;
    }

    $d = ttrp_defaults();

    return array(
        array(
            'title' => __( 'Free Shipping Progress Bar', 'free-shipping-progress-bar' ),
            'type'  => 'title',
            'id'    => 'ttrp_settings_section',
        ),

        array(
            'title'   => __( 'Progress message', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Use <code>{amount}</code> as a placeholder for the remaining amount.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[msg_progress]',
            'type'    => 'text',
            'default' => $d['msg_progress'],
            'css'     => 'width:100%;max-width:500px;',
        ),
        array(
            'title'   => __( 'Qualified message', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Shown when the cart total has reached the free shipping threshold.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[msg_qualified]',
            'type'    => 'text',
            'default' => $d['msg_qualified'],
            'css'     => 'width:100%;max-width:500px;',
        ),

        array(
            'title'   => __( 'Main color', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Used for the border, text, and progress bar. Background and bar gradient end are derived automatically.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_main]',
            'type'    => 'color',
            'default' => $d['color_main'],
        ),
        array(
            'title'   => __( 'Remaining amount color', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Color of the price shown in the progress message.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_remaining]',
            'type'    => 'color',
            'default' => $d['color_remaining'],
        ),

        array(
            'title'   => __( 'Show in mini cart', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Display the bar in the mini cart widget and side cart.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[show_in_mini_cart]',
            'type'    => 'checkbox',
            'default' => $d['show_in_mini_cart'],
        ),
        array(
            'title'   => __( 'Show on cart page', 'free-shipping-progress-bar' ),
            'desc'    => __( 'Display the bar on the cart page.', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[show_in_cart]',
            'type'    => 'checkbox',
            'default' => $d['show_in_cart'],
        ),

        array(
            'title'    => __( 'Cart page position', 'free-shipping-progress-bar' ),
            'desc'     => __( 'Where to display the bar on the cart page.', 'free-shipping-progress-bar' ),
            'id'       => 'ttrp_settings[cart_position]',
            'type'     => 'select',
            'default'  => $d['cart_position'],
            'options'  => ttrp_cart_position_options(),
            'css'      => 'min-width:200px;',
        ),

        array(
            'type' => 'sectionend',
            'id'   => 'ttrp_settings_section',
        ),

        array(
            'title' => __( 'Shortcode', 'free-shipping-progress-bar' ),
            'type'  => 'title',
            'desc'  => sprintf(
                /* translators: %s shortcode tag */
                __( 'Use %s to embed the progress bar in any page, post, or widget area — wherever the WooCommerce cart is available.', 'free-shipping-progress-bar' ),
                '<code>[free_shipping_bar]</code>'
            ),
            'id'    => 'ttrp_shortcode_info',
        ),
        array(
            'type' => 'sectionend',
            'id'   => 'ttrp_shortcode_info',
        ),
    );
}

// ---------------------------------------------------------------------------
// Front-end styles + dynamic CSS custom properties
// ---------------------------------------------------------------------------

function ttrp_enqueue_styles() {
    wp_enqueue_style(
        'ttrp-progress-bar',
        plugin_dir_url( __FILE__ ) . 'assets/progress-bar.css',
        array(),
        '1.1.0'
    );

    $main      = ttrp_setting( 'color_main' );
    $remaining = ttrp_setting( 'color_remaining' );
    $bg        = ttrp_derive_bg_color( $main );
    $bar_end   = ttrp_derive_bar_end_color( $main );

    $css = sprintf(
        ':root{--ttrp-border:%s;--ttrp-bg:%s;--ttrp-text:%s;--ttrp-remaining:%s;--ttrp-bar-start:%s;--ttrp-bar-end:%s;}',
        esc_attr( $main ),
        esc_attr( $bg ),
        esc_attr( $main ),
        esc_attr( $remaining ),
        esc_attr( $main ),
        esc_attr( $bar_end )
    );

    wp_add_inline_style( 'ttrp-progress-bar', $css );
}

// ---------------------------------------------------------------------------
// Free shipping threshold detection
// ---------------------------------------------------------------------------

function ttrp_free_shipping_threshold() {
    static $cached_threshold = null;
    if ( $cached_threshold !== null ) {
        return $cached_threshold;
    }

    $thresholds = array();

    $zones   = WC_Shipping_Zones::get_zones();
    $zones[] = array( 'shipping_methods' => ( new WC_Shipping_Zone( 0 ) )->get_shipping_methods() );

    foreach ( $zones as $zone ) {
        if ( empty( $zone['shipping_methods'] ) ) {
            continue;
        }
        foreach ( $zone['shipping_methods'] as $method ) {
            if ( $method->id !== 'free_shipping' || $method->enabled !== 'yes' ) {
                continue;
            }
            $requires   = $method->get_option( 'requires' );
            $min_amount = (float) $method->get_option( 'min_amount' );
            if ( $min_amount > 0 && in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) ) {
                $thresholds[] = $min_amount;
            }
        }
    }

    $thresholds       = apply_filters( 'ttrp_free_shipping_thresholds', $thresholds );
    $cached_threshold = ! empty( $thresholds ) ? min( $thresholds ) : 0;

    return $cached_threshold;
}

// ---------------------------------------------------------------------------
// Render
// ---------------------------------------------------------------------------

function ttrp_render_shipping_progress_bar() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $threshold = ttrp_free_shipping_threshold();
    if ( $threshold <= 0 ) {
        return;
    }

    $cart_total = (float) WC()->cart->get_subtotal();
    $remaining  = max( 0, $threshold - $cart_total );
    $percentage = min( 100, ( $cart_total / $threshold ) * 100 );
    $qualified  = $remaining <= 0;

    if ( $qualified ) {
        $message = wp_kses_post( ttrp_setting( 'msg_qualified' ) );
    } else {
        $raw     = ttrp_setting( 'msg_progress' );
        $message = wp_kses_post( str_replace( '{amount}', wc_price( $remaining ), $raw ) );
    }
    ?>
    <div class="ttrp-shipping-progress<?php echo $qualified ? ' is-qualified' : ''; ?>">
        <p class="ttrp-shipping-progress__message">
            <?php if ( $qualified ) : ?>
                <strong><?php echo $message; ?></strong>
            <?php else : ?>
                <?php echo $message; ?>
            <?php endif; ?>
        </p>
        <div class="ttrp-shipping-progress__bar">
            <div class="ttrp-shipping-progress__fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
        </div>
    </div>
    <?php
}

function ttrp_maybe_render_after_template( $template_name ) {
    if ( $template_name === 'cart/mini-cart.php' ) {
        ttrp_render_shipping_progress_bar();
    }
}

// ---------------------------------------------------------------------------
// Shortcode: [free_shipping_bar]
// ---------------------------------------------------------------------------

function ttrp_shortcode() {
    ob_start();
    ttrp_render_shipping_progress_bar();
    return ob_get_clean();
}
