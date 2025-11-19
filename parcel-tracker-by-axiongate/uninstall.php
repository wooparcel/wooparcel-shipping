<?php
/**
 * Uninstall Parcel Tracker Plugin
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option( 'parcel_tracker_api_key' );
delete_option( 'parcel_tracker_api_code' );
delete_option( 'parcel_tracker_auto_awb' );
delete_option( 'parcel_tracker_remote_api' );

// Delete transients
delete_transient( 'parcel_tracker_last_order_data' );

// Optional: Delete meta keys added to orders
// Uncomment the following code if you want to remove AWB data from orders
/*
global $wpdb;
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_parcel_tracker_awb_generated' )
);
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_parcel_tracker_awb_date' )
);
$wpdb->delete(
    $wpdb->postmeta,
    array( 'meta_key' => '_parcel_tracker_awb_number' )
);
*/

