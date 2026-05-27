<?php
/**
 * Plugin Name:       Free Shipping Progress Bar for WooCommerce
 * Plugin URI:        https://github.com/tetterip/woo-tet-free-shipping-progress-bar
 * Description:       Displays a progress bar showing how much more a customer needs to spend to qualify for free shipping. Compatible with Elementor Pro Menu Cart, WooCommerce mini cart, cart page, and block cart.
 * Version:           1.3.11
 * Author:            Michalis Tetteris
 * Author URI:        https://ttrp.gr
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       free-shipping-progress-bar
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * WC tested up to:   10.7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'TTRP_FSB_VERSION', get_file_data( __FILE__, array( 'Version' => 'Version' ) )['Version'] );

add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

add_action( 'init', 'ttrp_init' );

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

    // Block cart page — respects show_in_cart setting
    if ( ttrp_setting( 'show_in_cart' ) !== 'no' ) {
        add_filter( 'render_block_woocommerce/proceed-to-checkout-block', 'ttrp_block_inject_bar' );
    }

    // Block mini cart drawer — respects show_in_mini_cart setting
    if ( ttrp_setting( 'show_in_mini_cart' ) !== 'no' ) {
        add_filter( 'render_block_woocommerce/mini-cart-footer-block', 'ttrp_block_inject_bar' );
    }

    // Shortcode: [free_shipping_bar]
    add_shortcode( 'free_shipping_bar', 'ttrp_shortcode' );

    // Admin: ttrp Plugins > Free Shipping Bar
    add_action( 'admin_menu', 'ttrp_fsb_maybe_register_brand_menu', 5 );
    add_action( 'admin_menu', 'ttrp_fsb_admin_menu' );
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
// ttrp Plugins admin menu
// ---------------------------------------------------------------------------

function ttrp_fsb_maybe_register_brand_menu(): void {
    global $menu;
    if ( is_array( $menu ) ) {
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && 'ttrp-plugins' === $item[2] ) {
                return;
            }
        }
    }
    if ( ! function_exists( 'ttrp_brand_dashboard' ) ) {
        function ttrp_brand_dashboard(): void {
            global $submenu;
            $target = null;
            if ( ! empty( $submenu['ttrp-plugins'] ) ) {
                foreach ( $submenu['ttrp-plugins'] as $item ) {
                    if ( $item[2] !== 'ttrp-plugins' ) {
                        $target = $item[2];
                        break;
                    }
                }
            }
            if ( $target ) {
                $url = esc_url( admin_url( 'admin.php?page=' . $target ) );
                echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
                echo '<script>window.location.replace("' . $url . '");</script>';
            }
        }
    }
    $svg_path = plugin_dir_path( __FILE__ ) . 'assets/ttrp-logo.svg';
    $icon     = file_exists( $svg_path )
        ? 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_path ) )
        : 'dashicons-admin-plugins';
    add_menu_page(
        'ttrp.gr Plugins',
        'ttrp.gr Plugins',
        'manage_options',
        'ttrp-plugins',
        'ttrp_brand_dashboard',
        $icon,
        56
    );
    add_action( 'admin_menu', function () {
        remove_submenu_page( 'ttrp-plugins', 'ttrp-plugins' );
    }, 999 );
}

function ttrp_fsb_admin_menu(): void {
    add_submenu_page(
        'ttrp-plugins',
        __( 'Free Shipping Bar', 'free-shipping-progress-bar' ),
        __( 'Free Shipping Bar', 'free-shipping-progress-bar' ),
        'manage_options',
        'ttrp-free-shipping-bar',
        'ttrp_fsb_render_settings'
    );
}

function ttrp_fsb_enqueue_admin_assets(): void {
    if ( ! isset( $_GET['page'] ) || 'ttrp-free-shipping-bar' !== $_GET['page'] ) {
        return;
    }
    wp_enqueue_style( 'woocommerce_admin_styles' );
    wp_enqueue_style( 'ttrp-admin', plugins_url( 'assets/ttrp-admin.css', __FILE__ ), [], TTRP_FSB_VERSION );
}
add_action( 'admin_enqueue_scripts', 'ttrp_fsb_enqueue_admin_assets' );

function ttrp_fsb_render_settings(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_POST['save_ttrp_fsb'] ) && check_admin_referer( 'ttrp_fsb_save' ) ) {
        woocommerce_update_options( ttrp_get_settings( [], 'free_shipping_bar' ) );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'free-shipping-progress-bar' ) . '</p></div>';
    }
    ?>
    <div class="wrap woocommerce ttrp-wrap">
        <div class="ttrp-plugin-header">
            <h1><?php esc_html_e( 'Free Shipping Bar', 'free-shipping-progress-bar' ); ?></h1>
            <span class="ttrp-plugin-version">v<?php echo esc_html( TTRP_FSB_VERSION ); ?></span>
        </div>
        <form method="post">
            <?php
            wp_nonce_field( 'ttrp_fsb_save' );
            woocommerce_admin_fields( ttrp_get_settings( [], 'free_shipping_bar' ) );
            ?>
            <p class="submit">
                <input type="submit" name="save_ttrp_fsb" class="button-primary" value="<?php esc_attr_e( 'Save settings', 'free-shipping-progress-bar' ); ?>" />
            </p>
        </form>
        <div class="ttrp-settings-footer">
            <img src="<?php echo esc_url( plugins_url( 'assets/ttrp.svg', __FILE__ ) ); ?>" alt="" />
            <span><?php esc_html_e( 'Free Shipping Bar by', 'free-shipping-progress-bar' ); ?> <a href="https://ttrp.gr" target="_blank">ttrp.gr</a></span>
        </div>
    </div>
    <?php
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
            'title' => __( 'Free Shipping Progress Bar for WooCommerce', 'free-shipping-progress-bar' ),
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
        TTRP_FSB_VERSION
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

    $cart_total = (float) WC()->cart->get_displayed_subtotal();
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

// Prepend the bar before a block's rendered output (used for block cart + block mini cart).
function ttrp_block_inject_bar( $content ) {
    ob_start();
    ttrp_render_shipping_progress_bar();
    $bar = ob_get_clean();
    return $bar !== '' ? $bar . $content : $content;
}

// ---------------------------------------------------------------------------
// Shortcode: [free_shipping_bar]
// ---------------------------------------------------------------------------

function ttrp_shortcode() {
    ob_start();
    ttrp_render_shipping_progress_bar();
    return ob_get_clean();
}

require_once plugin_dir_path( __FILE__ ) . 'update-checker.php';
new TTRP_Update_Checker( __FILE__, 'woo-tet-free-shipping-progress-bar' );
