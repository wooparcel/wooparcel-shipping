<?php
/**
 * Plugin Name: WooParcel by AxionGate
 * Plugin URI: https://wooparcel.com
 * Description: Manage shop details, configure API settings, and collect order data when orders are completed.
 * Version: 1.0.0
 * Author: wooparcel
 * Author URI: https://wooparcel.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wooparcel-by-axiongate
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WOOPARCEL_VERSION', '1.0.2' );
define( 'WOOPARCEL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WOOPARCEL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WOOPARCEL_PLUGIN_FILE', __FILE__ );

// HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

// Require WooCommerce
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'WooParcel requires WooCommerce to be installed and active.', 'wooparcel-by-axiongate' ) . '</p></div>';
    });
    return;
}

/**
 * Main WooParcel class
 */
class WooParcel {

    private static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        // Load includes
        require_once WOOPARCEL_PLUGIN_DIR . 'includes/class-wooparcel-admin.php';
        require_once WOOPARCEL_PLUGIN_DIR . 'includes/class-wooparcel-settings.php';
        require_once WOOPARCEL_PLUGIN_DIR . 'includes/class-wooparcel-order-handler.php';

        WooParcel_Admin::get_instance();
        WooParcel_Settings::get_instance();
        WooParcel_Order_Handler::get_instance();

        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_head', [ $this, 'output_admin_menu_icon_css' ] );

        // Setup debug log
        //add_action( 'admin_init', [ $this, 'bootstrap_debug_log' ] );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'wooparcel' ) === false ) {
            return;
        }

        wp_enqueue_style( 'wooparcel-admin', WOOPARCEL_PLUGIN_URL . 'assets/css/admin.css', [], WOOPARCEL_VERSION );
        wp_enqueue_script( 'wooparcel-admin', WOOPARCEL_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], WOOPARCEL_VERSION, true );
        wp_localize_script( 'wooparcel-admin', 'WooParcelAjax', [
            'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wooparcel_save_settings' ),
            'redirect' => admin_url( 'admin.php?page=wooparcel&tab=setup&status=saved' ),
        ]);
    }

    /**
     * Ensure custom menu icon renders at 20x20 like default WP icons.
     */
    public function output_admin_menu_icon_css() {
        echo '<style type="text/css">.toplevel_page_wooparcel .wp-menu-image img{width:20px;height:20px;object-fit:contain;}</style>';
    }

    /**
     * Setup debug log
     */

    // public function bootstrap_debug_log() {
    //     if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
    //         return;
    //     }
    //     if ( get_option( 'wooparcel_debug_bootstrapped', false ) ) {
    //         return;
    //     }
    //     $log_path = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log';
    //     $dir = dirname( $log_path );
    //     if ( ! file_exists( $dir ) ) {
    //         @wp_mkdir_p( $dir );
    //     }
    //     if ( ! file_exists( $log_path ) ) {
    //         @fclose( @fopen( $log_path, 'a' ) );
    //     }
    //     if ( is_string( $log_path ) ) {
    //         @ini_set( 'error_log', $log_path );
    //     }
    //     @error_log( 'WooParcel: debug bootstrap OK at ' . gmdate( 'c' ) );
    //     update_option( 'wooparcel_debug_bootstrapped', true );
    // }
}

// Initialize WooParcel
WooParcel::get_instance();
