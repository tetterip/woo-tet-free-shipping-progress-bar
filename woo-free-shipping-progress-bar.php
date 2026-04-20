<?php
/**
 * Plugin Name: Free Shipping Progress Bar
 * Plugin URI:  https://github.com/tetterip/woo-free-shipping-progress-bar
 * Description: Displays a progress bar showing how much more a customer needs to spend to qualify for free shipping. Compatible with Elementor Pro Menu Cart, WooCommerce mini cart, and the cart page.
 * Version:     1.0.1
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

    // Mini cart — always at fixed positions
    add_action( 'woocommerce_after_template_part',                 'ttrp_maybe_render_after_template', 10, 1 );
    add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'ttrp_render_shipping_progress_bar', 5 );

    // Cart page — position driven by setting
    $cart_hooks = array(
        'before_cart'            => array( 'woocommerce_before_cart',        10 ),
        'after_cart_table'       => array( 'woocommerce_after_cart_table',   10 ),
        'before_cart_totals'     => array( 'woocommerce_before_cart_totals', 10 ),
        'after_cart_totals'      => array( 'woocommerce_after_cart_totals',  10 ),
        'before_checkout_button' => array( 'woocommerce_proceed_to_checkout', 10 ),
    );
    $position = ttrp_setting( 'cart_position' );
    if ( ! array_key_exists( $position, $cart_hooks ) ) {
        $position = 'before_cart_totals';
    }
    list( $hook, $priority ) = $cart_hooks[ $position ];
    add_action( $hook, 'ttrp_render_shipping_progress_bar', $priority );

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
        'msg_progress'    => __( 'Add {amount} more for free shipping!', 'free-shipping-progress-bar' ),
        'msg_qualified'   => __( '🎉 You have free shipping!', 'free-shipping-progress-bar' ),
        'color_border'    => '#008481',
        'color_bg'        => '#ebf7ee',
        'color_text'      => '#008481',
        'color_remaining' => '#c32e2e',
        'color_bar_start' => '#008481',
        'color_bar_end'   => '#2ecc71',
        'cart_position'   => 'before_cart_totals',
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
            'title'   => __( 'Border color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_border]',
            'type'    => 'color',
            'default' => $d['color_border'],
        ),
        array(
            'title'   => __( 'Background color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_bg]',
            'type'    => 'color',
            'default' => $d['color_bg'],
        ),
        array(
            'title'   => __( 'Text color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_text]',
            'type'    => 'color',
            'default' => $d['color_text'],
        ),
        array(
            'title'   => __( 'Remaining amount color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_remaining]',
            'type'    => 'color',
            'default' => $d['color_remaining'],
        ),
        array(
            'title'   => __( 'Progress bar — start color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_bar_start]',
            'type'    => 'color',
            'default' => $d['color_bar_start'],
        ),
        array(
            'title'   => __( 'Progress bar — end color', 'free-shipping-progress-bar' ),
            'id'      => 'ttrp_settings[color_bar_end]',
            'type'    => 'color',
            'default' => $d['color_bar_end'],
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
        '1.0.1'
    );

    $css = sprintf(
        ':root{--ttrp-border:%s;--ttrp-bg:%s;--ttrp-text:%s;--ttrp-remaining:%s;--ttrp-bar-start:%s;--ttrp-bar-end:%s;}',
        esc_attr( ttrp_setting( 'color_border' ) ),
        esc_attr( ttrp_setting( 'color_bg' ) ),
        esc_attr( ttrp_setting( 'color_text' ) ),
        esc_attr( ttrp_setting( 'color_remaining' ) ),
        esc_attr( ttrp_setting( 'color_bar_start' ) ),
        esc_attr( ttrp_setting( 'color_bar_end' ) )
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
