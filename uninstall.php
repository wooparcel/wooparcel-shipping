<?php
/**
 * Uninstall WooParcel Plugin
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'wooparcel_api_key' );
delete_option( 'wooparcel_api_code' );
delete_option( 'wooparcel_auto_awb' );

// Delete transients
delete_transient( 'wooparcel_last_order_data' );

// Optional: Delete meta keys added to orders
// Uncomment the following code if you want to remove AWB data from orders
/*
global $wpdb;
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_wooparcel_awb_generated' )
);
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_wooparcel_awb_date' )
);
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_wooparcel_awb_number' )
);
*/

