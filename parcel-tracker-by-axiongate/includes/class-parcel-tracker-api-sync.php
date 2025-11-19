<?php
/**
 * API Sync handler class for scheduled synchronization
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ParcelTracker_API_Sync {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = ParcelTracker_Settings::get_instance();
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {

        register_rest_route( 'parcel-tracker/v1', '/sync/schedule', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_sync_schedule' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'sinceDate' => array(
                    'required' => true,
                    'type'     => 'string',
                    'validate_callback' => array( $this, 'validate_date' ),
                ),
                'untilDate' => array(
                    'required' => true,
                    'type'     => 'string',
                    'validate_callback' => array( $this, 'validate_date' ),
                ),
                'syncType' => array(
                    'required' => true,
                    'type'     => 'string',
                    'enum'     => array( 'both', 'products', 'orders' ),
                ),
            ),
        ) );
    }
    
    /**
     * Check permission via X-Sync-Secret header
     * Uses parcel_tracker_api_key for authentication
     */
    public function check_permission( $request ) {
        $secret = $request->get_header( 'X-Sync-Secret' );
        $api_key = $this->settings->get( 'api_key', '' );
        
        if ( empty( $api_key ) ) {
            return new WP_Error(
                'api_key_not_configured',
                'API key is not configured. Please set it in Parcel Tracker settings.',
                array( 'status' => 500 )
            );
        }
        
        if ( empty( $secret ) || $secret !== $api_key ) {
            return new WP_Error(
                'invalid_sync_secret',
                'Invalid or missing X-Sync-Secret header.',
                array( 'status' => 401 )
            );
        }
        
        return true;
    }
    
    /**
     * Validate date format
     */
    public function validate_date( $date, $request, $param ) {
        if ( ! is_string( $date ) ) {
            return false;
        }
        
        // Try to parse ISO 8601 date
        $timestamp = strtotime( $date );
        if ( $timestamp === false ) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Handle sync schedule request
     */
    public function handle_sync_schedule( $request ) {
        
        $since_date = $request->get_param( 'sinceDate' );
        $until_date = $request->get_param( 'untilDate' );
        $sync_type  = $request->get_param( 'syncType' );
                
        // Extract date part (YYYY-MM-DD) from ISO 8601 format
        $since_date_only = $this->extract_date_part( $since_date );
        $until_date_only = $this->extract_date_part( $until_date );
        
        if ( ! $since_date_only || ! $until_date_only ) {
            return new WP_Error(
                'invalid_date_format',
                'Invalid date format. Please use ISO 8601 format (e.g., 2025-01-01T00:00:00Z or 2025-01-01).',
                array( 'status' => 400 )
            );
        }
        
        if ( $since_date_only > $until_date_only ) {
            return new WP_Error(
                'invalid_date_range',
                'sinceDate must be before or equal to untilDate.',
                array( 'status' => 400 )
            );
        }
        
        $response = array(
            'success' => true,
            'sinceDate' => $since_date,
            'untilDate' => $until_date,
            'syncType' => $sync_type,
        );
        
        // Fetch products/inventory if needed
        if ( $sync_type === 'both' || $sync_type === 'products' ) {
            $response['products'] = $this->fetch_products( $since_date_only, $until_date_only );
            
            // If products were found, send them to the collector endpoint
            if ( ! empty( $response['products'] ) ) {
                $this->send_products_to_collector( $response['products'] );
            } else {
            }
        }
        
        // Fetch orders if needed
        if ( $sync_type === 'both' || $sync_type === 'orders' ) {
            $response['orders'] = $this->fetch_orders( $since_date_only, $until_date_only );
           
            // If orders were found, send them to the orders collector endpoint
            if ( ! empty( $response['orders'] ) ) {
                $this->send_orders_to_collector( $response['orders'] );
            }
        }
        
        return rest_ensure_response( $response );
    }
    
    /**
     * Extract date part (YYYY-MM-DD) from ISO 8601 date string
     */
    private function extract_date_part( $date_string ) {
        if ( empty( $date_string ) ) {
            return false;
        }
        
        // Try to parse the date
        $timestamp = strtotime( $date_string );
        if ( $timestamp === false ) {
            return false;
        }
        
        // Return just the date part in YYYY-MM-DD format
        return gmdate( 'Y-m-d', $timestamp );
    }
    
    /**
     * Fetch all products from inventory
     * For inventory sync, we fetch all products regardless of date range
     */
    private function fetch_products( $since_date, $until_date ) {
        $products = array();
        
        // Get all products (including variations)
        // For inventory sync, we want all products, not just those in date range
        $args = array(
            'status' => 'publish',
            'limit'  => -1, // Get all products
            'type'   => array( 'simple', 'variable', 'variation' ),
        );
        
        $wc_products = wc_get_products( $args );
        
        foreach ( $wc_products as $product ) {
            $product_data = $this->format_product_data( $product );
            if ( $product_data ) {
                $products[] = $product_data;
            }
        }
        
        return $products;
    }
    
    /**
     * Format product data for API response
     */
    private function format_product_data( $product ) {
        if ( ! $product ) {
            return null;
        }
        
        $product_data = array(
            'id'                => $product->get_id(),
            'name'              => $product->get_name(),
            'sku'               => $product->get_sku(),
            'type'              => $product->get_type(),
            'status'            => $product->get_status(),
            'price'             => $product->get_price(),
            'regular_price'     => $product->get_regular_price(),
            'sale_price'        => $product->get_sale_price(),
            'weight'            => $product->get_weight(),
            'length'            => $product->get_length(),
            'width'             => $product->get_width(),
            'height'            => $product->get_height(),
            'date_created'      => $product->get_date_created() ? $product->get_date_created()->format( 'Y-m-d' ) : null,
            'date_modified'    => $product->get_date_modified() ? $product->get_date_modified()->format( 'Y-m-d' ) : null,
        );
        
        // Add parent ID for variations
        if ( $product->is_type( 'variation' ) ) {
            $product_data['parent_id'] = $product->get_parent_id();
        }
        
        // Add categories
        $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
        $product_data['categories'] = is_array( $categories ) ? $categories : array();
        
        // Add tags
        $tags = wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) );
        $product_data['tags'] = is_array( $tags ) ? $tags : array();
        
        return $product_data;
    }
    
    /**
     * Fetch orders from history within date range
     * Compares dates in YYYY-MM-DD format
     */
    private function fetch_orders( $since_date, $until_date ) {
        $orders = array();
        
        // Build query arguments with date filtering if dates are set
        $args = array(
            'limit'   => -1, // Get all orders
            'status'  => 'any', // Get all order statuses
            'orderby' => 'date',
            'order'   => 'ASC',
        );
        
        // Add date filtering to query if dates are provided
        if ( ! empty( $since_date ) && ! empty( $until_date ) ) {
            $args['date_after']  = $since_date . ' 00:00:00';
            $args['date_before'] = $until_date . ' 23:59:59';
        }
        
        $wc_orders = wc_get_orders( $args );
        
        foreach ( $wc_orders as $order ) {
            $order_data = $this->format_order_data( $order );
            if ( $order_data ) {
                $orders[] = $order_data;
            }
        }
        
        return $orders;
    }
    
    /**
     * Format order data for API response
     */
    private function format_order_data( $order ) {
        if ( ! $order ) {
            return null;
        }
        
        $order_handler = ParcelTracker_Order_Handler::get_instance();
        
        // Use the existing collect_order_data method from order handler
        $order_data = $order_handler->collect_order_data( $order );
        
        // Format date_created as YYYY-MM-DD only
        if ( isset( $order_data['date_created'] ) ) {
            $date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order_data['date_created'] );
            if ( $date ) {
                $order_data['date_created'] = $date->format( 'Y-m-d' );
            }
        }
        
        // Keep date_modified in ISO 8601 format
        if ( isset( $order_data['date_modified'] ) ) {
            $date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order_data['date_modified'] );
            if ( $date ) {
                $order_data['date_modified'] = $date->format( 'c' );
            }
        }
        
        // Keep date_paid in ISO 8601 format
        if ( isset( $order_data['payment']['date_paid'] ) && $order_data['payment']['date_paid'] ) {
            $date = DateTime::createFromFormat( 'Y-m-d H:i:s', $order_data['payment']['date_paid'] );
            if ( $date ) {
                $order_data['payment']['date_paid'] = $date->format( 'c' );
            }
        }
        
        // Remove api_config from response as it's not needed in sync endpoint
        if ( isset( $order_data['api_config'] ) ) {
            unset( $order_data['api_config'] );
        }
        
        return $order_data;
    }
    
    /**
     * Send orders to the orders collector endpoint
     * Sends all orders in a single request with the format:
     * {
     *   "Orders": [
     *     {
     *       "orderData": "...",
     *       "source": "...",
     *       "shopName": "...",
     *       "shopUrl": "...",
     *       "productTitle": "...",
     *       "OrdrerID": "...",
     *       "HumanReadableOrderID": "..."
     *     }
     *   ]
     * }
     */
    private function send_orders_to_collector( $orders ) {
        
        if ( empty( $orders ) || ! is_array( $orders ) ) {
            return;
        }
        
        // Get shop domain and name
        $shop_domain = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        if ( $shop_domain === '' ) {
            $shop_domain = (string) wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
        }
        if ( $shop_domain === '' ) {
            $shop_domain = '-';
        }
        
        $shop_name = (string) get_bloginfo( 'name' );
        if ( $shop_name === '' ) {
            $shop_name = $shop_domain;
        }
        
        $endpoint_url = 'https://invetoryandorders-collector.onrender.com/syncOrders';
        
        // Build array of orders in the required format
        $orders_array = array();
        $order_count = 0;
        
        foreach ( $orders as $order ) {
            if ( ! is_array( $order ) || empty( $order ) ) {
                continue;
            }
            
            $order_count++;
            
            // Get product title from order items (first item's name, or concatenated)
            $product_title = '';
            if ( isset( $order['items'] ) && is_array( $order['items'] ) && ! empty( $order['items'] ) ) {
                $item_names = array();
                foreach ( $order['items'] as $item ) {
                    if ( isset( $item['name'] ) && ! empty( $item['name'] ) ) {
                        $item_names[] = $item['name'];
                    }
                }
                if ( ! empty( $item_names ) ) {
                    $product_title = implode( ', ', $item_names );
                }
            }
            if ( empty( $product_title ) ) {
                $product_title = '-';
            }
            
            // Build order object in the required format
            $order_obj = array(
                'orderData'             => wp_json_encode( $order ),
                'source'                => 'woocommerce',
                'shopName'              => $shop_name !== '' ? $shop_name : '-',
                'shopUrl'               => $shop_domain !== '' ? $shop_domain : '-',
                'productTitle'          => $product_title,
                'OrdrerID'              => isset( $order['order_id'] ) ? (string) $order['order_id'] : '-',
                'HumanReadableOrderID' => isset( $order['order_number'] ) ? (string) $order['order_number'] : '-',
            );
            
            $orders_array[] = $order_obj;
        }
        
        if ( empty( $orders_array ) ) {
            return;
        }
        
        // Build the final request body with Orders array
        $request_body = array(
            'Orders' => $orders_array,
        );
        
        // Send POST request with all orders
        $result = $this->send_collector_request( $endpoint_url, $request_body );
    }
    
    /**
     * Send products to the inventory collector endpoint
     * Sends all products in a single request with the format:
     * {
     *   "Inventories": [
     *     {
     *       "productData": "...",
     *       "source": "...",
     *       "shopName": "...",
     *       "shopUrl": "...",
     *       "productTitle": "...",
     *       "ProductID": "...",
     *       "HumanReadableOrderID": "..."
     *     }
     *   ]
     * }
     */
    private function send_products_to_collector( $products ) {
        
        if ( empty( $products ) || ! is_array( $products ) ) {
            return;
        }
        
        // Get shop domain and name
        $shop_domain = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        if ( $shop_domain === '' ) {
            $shop_domain = (string) wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
        }
        if ( $shop_domain === '' ) {
            $shop_domain = '';
        }
        
        $shop_name = (string) get_bloginfo( 'name' );
        if ( $shop_name === '' ) {
            $shop_name = $shop_domain;
        }
                
        $endpoint_url = 'https://invetoryandorders-collector.onrender.com/syncInventories';
        
        // Build array of products in the required format
        $inventories_array = array();
        $product_count = 0;
        
        foreach ( $products as $product ) {
            if ( ! is_array( $product ) || empty( $product ) ) {
                continue;
            }
            
            $product_count++;
            
            // Get product ID and title
            $product_id = isset( $product['id'] ) ? (string) $product['id'] : '';
            $product_title = '';
            if ( isset( $product['productTitle'] ) ) {
                $product_title = (string) $product['productTitle'];
            } elseif ( isset( $product['title'] ) ) {
                $product_title = (string) $product['title'];
            } elseif ( isset( $product['name'] ) ) {
                $product_title = (string) $product['name'];
            }
            
            // productID fallback: if no ID, use productTitle
            if ( $product_id === '' && $product_title !== '' ) {
                $product_id = $product_title;
            }
            
            // Get human readable ID (use SKU if available, otherwise product title or ID)
            $human_readable_id = '';
            if ( isset( $product['sku'] ) && ! empty( $product['sku'] ) ) {
                $human_readable_id = (string) $product['sku'];
            } elseif ( ! empty( $product_title ) ) {
                $human_readable_id = $product_title;
            } elseif ( ! empty( $product_id ) ) {
                $human_readable_id = $product_id;
            } else {
                $human_readable_id = '-';
            }
            
            // Build product object in the required format
            $product_obj = array(
                'productData'             => wp_json_encode( $product ),
                'source'                  => 'woocommerce',
                'shopName'                => $shop_name !== '' ? $shop_name : ( $shop_domain !== '' ? $shop_domain : '-' ),
                'shopUrl'                 => $shop_domain !== '' ? $shop_domain : '-',
                'productTitle'            => $product_title !== '' ? $product_title : '-',
                'ProductID'               => $product_id !== '' ? $product_id : '-',
                'HumanReadableOrderID'    => $human_readable_id,
            );
            
            $inventories_array[] = $product_obj;
        }
        
        if ( empty( $inventories_array ) ) {
            return;
        }
        
        // Build the final request body with Inventories array
        $request_body = array(
            'Inventories' => $inventories_array,
        );
        
        // Send POST request with all products
        $result = $this->send_collector_request( $endpoint_url, $request_body );
    }
    
    /**
     * Send POST request to collector endpoint
     */
    private function send_collector_request( $url, $body ) {
        
        $args = array(
            'method'      => 'POST',
            'timeout'     => 30,
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'        => wp_json_encode( $body ),
            'data_format' => 'body',
            'sslverify'   => false,
        );
        
        $response = wp_remote_post( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        $body_response = wp_remote_retrieve_body( $response );
                
        if ( $code >= 200 && $code < 300 ) {
            $item_id = isset( $body['OrdrerID'] ) ? $body['OrdrerID'] : ( isset( $body['productID'] ) ? $body['productID'] : 'unknown' );
            return true;
        } else {
            return false;
        }
    }
}

