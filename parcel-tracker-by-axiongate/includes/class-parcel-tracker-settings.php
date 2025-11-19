<?php
/**
 * Settings management class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ParcelTracker_Settings {
    
    private static $instance = null;
    private $settings = array();
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_settings();
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $this->settings = array(
            'api_key' => get_option( 'parcel_tracker_api_key', '' ),
            'api_code' => get_option( 'parcel_tracker_api_code', '' ),
            'auto_awb' => get_option( 'parcel_tracker_auto_awb', false ),
            'remote_api' => get_option( 'parcel_tracker_remote_api', '' ),
        );
    }
    
    /**
     * Get setting value
     */
    public function get( $key, $default = '' ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        return $this->settings;
    }
    
    /**
     * Update setting
     */
    public function update( $key, $value ) {
        $this->settings[ $key ] = $value;
        update_option( 'parcel_tracker_' . $key, $value );
    }
    
    /**
     * Refresh settings
     */
    public function refresh() {
        $this->load_settings();
    }
}

