<?php
/**
 * Order handler class for collecting order data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooParcel_Order_Handler {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = WooParcel_Settings::get_instance();
        
        // Hook into WooCommerce order status change
        add_action( 'woocommerce_order_status_completed', array( $this, 'handle_completed_order' ), 10, 1 );
    }
    
    /**
     * Handle completed order
     */
    public function handle_completed_order( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order ) {
            return;
        }
        
        // Collect order data
        $order_data = $this->collect_order_data( $order );
        
        // Process the order data
        $this->process_order( $order_data );
    }
    
    
    /**
     * Collect order data
     */
    private function collect_order_data( $order ) {
        // Basic order information
        $order_data = array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'order_status' => $order->get_status(),
            'date_created' => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'date_modified' => $order->get_date_modified()->format( 'Y-m-d H:i:s' ),
        );
        
        // Customer information
        $order_data['customer'] = array(
            'id' => $order->get_customer_id(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'company' => $order->get_billing_company(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        );
        
        // Billing address
        $order_data['billing_address'] = array(
            'address_1' => $order->get_billing_address_1(),
            'address_2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postcode' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
        );
        
        // Shipping address
        $order_data['shipping_address'] = array(
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
        );
        
        // Order items and standdard values for the parcel
        $order_data['items'] = array();
        $total_length = 20;
        $total_width = 15;
        $total_height = 10;
        $total_weight = 0;
        
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            if($quantity < 1 || empty($quantity)) { $quantity = 1; }
            
            // Get product dimensions
            $length = $product ? $product->get_length() : 20;
            $width = $product ? $product->get_width() : 15;
            $height = $product ? $product->get_height() : 10;
            $weight = $product ? $product->get_weight() : 0.1;
            if($weight < 1 || empty($weight)) { $weight = 0.1; }
            if($length < 1 || empty($length)) { $length = 20; }
            if($width < 1 || empty($width)) { $width = 15; }
            if($height < 1 || empty($height)) { $height = 10; }
            
            // Calculate total dimensions (multiply by quantity)
            $total_length += (float) $length * $quantity;
            $total_width += (float) $width * $quantity;
            $total_height += (float) $height * $quantity;
            $total_weight += (float) $weight * $quantity;
            
            $order_data['items'][] = array(
                'item_id' => $item_id,
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'quantity' => $quantity,
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total(),
                'sku' => $product ? $product->get_sku() : '',
                'dimensions' => array(
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'weight' => $weight,
                ),
            );
        }
        
        if($total_weight < 1 || empty($total_weight)) { $total_weight = 1; }
        // Add total order dimensions
        $order_data['order_dimensions'] = array(
            'total_length' => round( $total_length, 2 ),
            'total_width' => round( $total_width, 2 ),
            'total_height' => round( $total_height, 2 ),
            'total_weight' => round( $total_weight, 2 ),
        );
        
        // Order totals
        $order_data['totals'] = array(
            'subtotal' => $order->get_subtotal(),
            'discount_total' => $order->get_total_discount(),
            'shipping_total' => $order->get_shipping_total(),
            'tax_total' => $order->get_total_tax(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
        );
        
        // Payment information
        $order_data['payment'] = array(
            'method' => $order->get_payment_method(),
            'method_title' => $order->get_payment_method_title(),
            'transaction_id' => $order->get_transaction_id(),
            'payment_status' => $order->is_paid() ? 'paid' : 'unpaid',
            'date_paid' => $order->get_date_paid() ? $order->get_date_paid()->format( 'Y-m-d H:i:s' ) : null,
        );
        
        // Shipping information
        $order_data['shipping'] = array(
            'method' => $order->get_shipping_method(),
            'method_id' => '',
            'cost' => $order->get_shipping_total(),
        );
        
        if ( count( $order->get_items( 'shipping' ) ) > 0 ) {
            foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
                $order_data['shipping']['method_id'] = $shipping_item->get_method_id();
                break;
            }
        }
        
        // Customer notes
        $order_data['customer_note'] = $order->get_customer_note();
        
        // API Configuration from plugin settings
        $order_data['api_config'] = array(
            'api_key' => $this->settings->get( 'api_key' ),
            'api_code' => $this->settings->get( 'api_code' ),
            'auto_awb' => $this->settings->get( 'auto_awb' ),
            'remote_api' => $this->settings->get( 'remote_api' ),
        );
        
        return $order_data;
    }
        
    /**
     * Process order data
     */
    private function process_order( $order_data ) {
        // Check if auto_awb is enabled
        if ( $this->settings->get( 'auto_awb' ) ) {
            
            $payload = $this->build_external_request_body( $order_data );

            // send data to external API
            $response = $this->send_to_remote_api( $payload );

            $orderId = $order_data['order_id'];
            $this->set_awb_data( $orderId, $response );
        }
    }

    /**
     * Build request body for external API based on collected order data.
     * Returns an associative array ready to be json_encoded.
     */
    public function build_external_request_body( $order_data ) {
        if ( empty( $order_data ) || ! is_array( $order_data ) ) {
            return array();
        }

        // Determine payment type (simple heuristic: COD vs prepaid)
        $payment_method = isset( $order_data['payment']['method'] ) ? strtolower( (string) $order_data['payment']['method'] ) : '';
        $payment_type = ( strpos( $payment_method, 'cod' ) !== false || strpos( $payment_method, 'cash' ) !== false ) ? 'cash' : 'prepaid';

        // Shipment type (default to standard; could be made configurable)
        $shipment_type = 'standard';

        // Shop/site identifiers
        $shop_domain = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        if ( $shop_domain === '' ) {
            $shop_domain = (string) get_bloginfo( 'url' );
        }

        // Order identifiers and amounts
        $order_id      = isset( $order_data['order_number'] ) && $order_data['order_number'] !== '' ? (string) $order_data['order_number'] : (string) $order_data['order_id'];
        $currency      = isset( $order_data['totals']['currency'] ) ? (string) $order_data['totals']['currency'] : '';
        $order_total   = isset( $order_data['totals']['total'] ) ? (float) $order_data['totals']['total'] : 0.0;
        $total_weight  = isset( $order_data['order_dimensions']['total_weight'] ) ? (float) $order_data['order_dimensions']['total_weight'] : 1.0;

        // Receiver details (customer / shipping)
        $receiver_address_1 = isset( $order_data['shipping_address']['address_1'] ) ? (string) $order_data['shipping_address']['address_1'] : '';
        $receiver_address_2 = isset( $order_data['shipping_address']['address_2'] ) ? (string) $order_data['shipping_address']['address_2'] : '';
        $receiver_city      = isset( $order_data['shipping_address']['city'] ) ? (string) $order_data['shipping_address']['city'] : '';
        $receiver_country   = isset( $order_data['shipping_address']['country'] ) ? (string) $order_data['shipping_address']['country'] : '';
        $receiver_name      = trim( ( isset( $order_data['customer']['first_name'] ) ? (string) $order_data['customer']['first_name'] : '' ) . ' ' . ( isset( $order_data['customer']['last_name'] ) ? (string) $order_data['customer']['last_name'] : '' ) );
        $receiver_phone     = isset( $order_data['customer']['phone'] ) ? (string) $order_data['customer']['phone'] : '';
        $receiver_email     = isset( $order_data['customer']['email'] ) ? (string) $order_data['customer']['email'] : '';
        // Sender (store) details
        $store_info = $this->get_store_contact_info();

        // Item descriptions: concatenate all product titles
        $all_titles = array();
        $all_descriptions = array();
        if ( ! empty( $order_data['items'] ) && is_array( $order_data['items'] ) ) {
            foreach ( $order_data['items'] as $it ) {
                if ( is_array( $it ) && isset( $it['name'] ) && $it['name'] !== '' ) {
                    $all_titles[] = (string) $it['name'];
                }
                // Fetch product description live to avoid changing collected shape
                if ( is_array( $it ) && isset( $it['product_id'] ) && $it['product_id'] ) {
                    $p = wc_get_product( (int) $it['product_id'] );
                    if ( $p ) {
                        $desc = $p->get_description();
                        if ( is_string( $desc ) && $desc !== '' ) {
                            $all_descriptions[] = $desc;
                        }
                    }
                }
            }
        }
        if ( empty( $all_titles ) ) {
            $all_titles[] = 'Package contents';
        }
        // If no descriptions found, fall back to titles
        if ( empty( $all_descriptions ) ) {
            $all_descriptions = $all_titles;
        }
        $joined_titles              = implode( ', ', $all_titles );
        $joined_descriptions        = implode( ' ', $all_descriptions );
        $goods_description          = $this->sanitize_description( $joined_titles );
        $goods_description_detailed = $this->sanitize_description( $joined_descriptions );

        // Sum total quantity across all items
        $goods_total_quantity = 0;
        if ( ! empty( $order_data['items'] ) && is_array( $order_data['items'] ) ) {
            foreach ( $order_data['items'] as $it ) {
                $itemQuantity = 0;
                if ( is_array( $it ) && isset( $it['quantity'] ) ) {
                    $itemQuantity = (int) $it['quantity'];
                }
                if ( $itemQuantity < 1 ) {
                    $itemQuantity = 1;
                }
                $goods_total_quantity += $itemQuantity;
            }
        }

        $request_body = array(
            'contentTypeCode'        => 'nondocument',
            'paymentType'            => $payment_type,
            'shipmentType'           => $shipment_type,
            'shopId'                 => $shop_domain,
            'orderId'                => $order_id,
            'currency'               => $currency,
            'goodsDescription'       => $goods_description,
            'goodsDescriptionDetailed'=> $goods_description_detailed,
            'goodsTotalQuantity'     => (int) $goods_total_quantity,
            'numberOfPiece'          => 1,
            'valueOfGoods'           => (float) $order_total,
            'valueOfGoodsCurrency'   => $currency,
            'valueOfShipment'        => (float) $order_total,
            'weight'                 => (float) $total_weight,
            'receiverAddress'        => trim( $receiver_address_1 . ' ' . $receiver_address_2 ),
            'receiverCity'           => $receiver_city,
            'receiverCountryCode'    => $receiver_country,
            'receiverName'           => $receiver_name,
            'receiverPhone'          => $receiver_phone,
            'receiverEmail'          => $receiver_email,
            'senderAddress'          => trim( $store_info['address1'] . ' ' . $store_info['address2'] ),
            'senderCity'             => $store_info['city'],
            'senderName'             => $store_info['name'],
            'senderPhone'            => $store_info['phone'],
            'senderCountryCode'      => $store_info['country_code'],
            'sourceShopPlatform'     => 'woocommerce',
            'shopAliasName'          => $store_info['name'],
        );

        return $request_body;
    }

    /**
     * Sanitize description: strip tags, collapse whitespace, and truncate.
     */
    private function sanitize_description( $text, $max_len = 255 ) {
        $clean = wp_strip_all_tags( (string) $text );
        // Keep only ASCII alphanumeric and spaces; drop non-alphanumeric/UTF-16 symbols
        $clean = preg_replace( '/[^A-Za-z0-9 ]+/', '', $clean );
        $clean = preg_replace( '/\s+/', ' ', $clean );
        $clean = trim( $clean );
        if ( function_exists( 'mb_substr' ) ) {
            return mb_substr( $clean, 0, $max_len );
        }
        return substr( $clean, 0, $max_len );
    }

	/**
	 * Calculate SHA-256 hash of input and return lowercase hex digest.
	 */
	private function calculate_sha256_hex( $input ) {
		return hash( 'sha256', (string) $input );
	}

    /**
     * Retrieve store contact info from WooCommerce settings / site info.
     */
    private function get_store_contact_info() {
        $address1      = (string) get_option( 'woocommerce_store_address', '' );
        $address2      = (string) get_option( 'woocommerce_store_address_2', '' );
        $city          = (string) get_option( 'woocommerce_store_city', '' );
        $default_ctry  = (string) get_option( 'woocommerce_default_country', '' ); // e.g. "US:CA"
        $country_code  = $default_ctry;
        if ( strpos( $default_ctry, ':' ) !== false ) {
            list( $country_code ) = explode( ':', $default_ctry );
        }
		$store_name    = (string) get_bloginfo( 'name' );
		$store_phone   = (string) get_option( 'woocommerce_store_phone', '' );

        return array(
            'address1'     => $address1,
            'address2'     => $address2,
            'city'         => $city,
            'country_code' => $country_code,
            'name'         => $store_name,
            'phone'        => $store_phone,
        );
    }
    
    /**
     * Send payload to remote API endpoint using WordPress HTTP API.
     * Endpoint pattern: https://{api_code}.{remote_api}/parcel/create-auto-shipping
     */
    private function send_to_remote_api( $payload ) {
        $api_code   = (string) $this->settings->get( 'api_code', '' );
        $api_key    = (string) $this->settings->get( 'api_key', '' );
        $remote_api = (string) $this->settings->get( 'remote_api', '' );
        if ( $remote_api === '' || $api_code === '' || $api_key === '' ) {

            return;
        }

        // Normalize remote_api to a host (strip protocol/path if provided)
        $host = $remote_api;
        $parsed = wp_parse_url( $remote_api );
        if ( is_array( $parsed ) ) {
            if ( isset( $parsed['host'] ) && $parsed['host'] !== '' ) {
                $host = $parsed['host'];
            } else if ( isset( $parsed['path'] ) && $parsed['path'] !== '' ) {
                $host = ltrim( $parsed['path'], '/' );
            }
        }

        $url = 'https://' . $api_code . '.' . $host . '/parcel/create-auto-shipping';

		// calcualted hash value 
		$api_key_hash = $this->calculate_sha256_hex( $api_key );
        
		$args = array(
            'method'      => 'POST',
            'timeout'     => 20,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'TenantId'     => $api_code,
				'X-Authz'      => $api_key_hash,
			),
            'body'        => wp_json_encode( $payload ),
            'data_format' => 'body',
        );

        $args['sslverify'] = false;

        $response = wp_remote_post( $url, $args );
        if ( is_wp_error( $response ) ) {
            return array(
                'status' => 0,
                'error'  => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $json = null;
        if ( is_string( $body ) && $body !== '' ) {
            $decoded = json_decode( $body, true );
            if ( is_array( $decoded ) ) {
                $json = $decoded;
            }
        }

        return array(
            'status' => (int) $code,
            'body'   => $body,
            'json'   => $json,
            'url'    => $url,
        );
    }
    
    /**
     * Generate AWB (Air Waybill) for order
     */
    private function set_awb_data( $orderId, $response_received = null ) {
        $order_id = (int) $orderId;
        if ( ! $order_id ) {
            return;
        }

        $awb_from_api = '';
        $courier_ref  = '';
        if ( is_array( $response_received ) ) {
            // Prefer nested JSON if available
            if ( isset( $response_received['json'] ) && is_array( $response_received['json'] ) ) {
                if ( isset( $response_received['json']['awb'] ) && is_string( $response_received['json']['awb'] ) ) {
                    $awb_from_api = trim( (string) $response_received['json']['awb'] );
                }
                if ( isset( $response_received['json']['courierInternalReferenceNumber'] ) && is_string( $response_received['json']['courierInternalReferenceNumber'] ) ) {
                    $courier_ref = trim( (string) $response_received['json']['courierInternalReferenceNumber'] );
                }
            } else {
                if ( isset( $response_received['awb'] ) && is_string( $response_received['awb'] ) ) {
                    $awb_from_api = trim( (string) $response_received['awb'] );
                }
                if ( isset( $response_received['courierInternalReferenceNumber'] ) && is_string( $response_received['courierInternalReferenceNumber'] ) ) {
                    $courier_ref = trim( (string) $response_received['courierInternalReferenceNumber'] );
                }
            }
        }

        // Update meta with extracted values (empty strings if absent)
        update_post_meta( $order_id, '_wooparcel_awb_number', $awb_from_api );
        update_post_meta( $order_id, '_wooparcel_courier_reference', $courier_ref );
    }
    
}

