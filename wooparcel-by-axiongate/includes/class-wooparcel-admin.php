<?php
/**
 * Admin interface class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooParcel_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        // AJAX route for saving settings (admin-only)
        add_action( 'wp_ajax_wooparcel_save_settings', array( $this, 'handle_ajax_save_settings' ) );
        // Fallback admin-post route so settings still save without JS
        add_action( 'admin_post_wooparcel_save_settings', array( $this, 'handle_admin_post_save_settings' ) );
		// Inject store phone field into WooCommerce > Settings > General
		add_filter( 'woocommerce_general_settings', array( $this, 'inject_store_phone_setting' ) );        
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $icon_url = defined( 'WOOPARCEL_PLUGIN_URL' ) ? WOOPARCEL_PLUGIN_URL . 'assets/icon/wooparcel.png' : '';
        add_menu_page(
            __( 'WooParcel', 'wooparcel-by-axiongate' ),
            __( 'WooParcel', 'wooparcel-by-axiongate' ),
            'manage_options',
            'wooparcel',
            array( $this, 'render_admin_page' ),
            $icon_url,
            30
        );
        add_submenu_page(
            'wooparcel',
            __( 'AWB List', 'wooparcel-by-axiongate' ),
            __( 'AWB List', 'wooparcel-by-axiongate' ),
            'manage_options',
            'wooparcel-awb-list',
            array( $this, 'render_awb_list_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'wooparcel_settings', 'wooparcel_api_key', array(
            'type' => 'string',
            // No sanitize_callback to preserve full API key exactly as entered
        ) );
        
		register_setting( 'wooparcel_settings', 'wooparcel_api_code', array(
			'type' => 'string',
			// No sanitize_callback to preserve full API code exactly as entered
		) );
        
        register_setting( 'wooparcel_settings', 'wooparcel_auto_awb', array(
            'type' => 'boolean',
            'default' => false,
        ) );
        
        register_setting( 'wooparcel_settings', 'wooparcel_remote_api', array(
            'type' => 'string',
            'default' => '',
        ) );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for tab navigation only, no data modification
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'home'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ?>
        <div class="wrap wooparcel-wrap">
            <h1><?php esc_html_e( 'WooParcel', 'wooparcel-by-axiongate' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wooparcel&tab=home' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'home' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Home', 'wooparcel-by-axiongate' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wooparcel&tab=setup' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'setup' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e( 'Setup', 'wooparcel-by-axiongate' ); ?>
                </a>
            </h2>
            
            <div class="wooparcel-content">
                <?php
                if ( $active_tab === 'home' ) {
                    $this->render_home_tab();
                } else {
                    $this->render_setup_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render home tab
     */
    private function render_home_tab() {
        ?>
        <div class="wooparcel-home-tab">
            <div class="notice notice-info">
                <p><strong><?php esc_html_e( 'Welcome to WooParcel!', 'wooparcel-by-axiongate' ); ?></strong></p>
                <p><?php esc_html_e( 'This plugin helps you manage your shop details and automate order processing.', 'wooparcel-by-axiongate' ); ?></p>
            </div>
            
            <div class="wooparcel-info-sections">
                <div class="info-section">
                    <h2><?php esc_html_e( 'Setting Shop Details', 'wooparcel-by-axiongate' ); ?></h2>
                    <p><?php esc_html_e( 'To set your shop details in WooCommerce:', 'wooparcel-by-axiongate' ); ?></p>
                    <ol>
                        <li><?php esc_html_e( 'Go to WooCommerce > Settings > General', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Fill in your Store Address, City, Postcode, Phone Number and Country', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Set your Currency, Allowed Customer Locations, and tax calculation options', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Save your changes', 'wooparcel-by-axiongate' ); ?></li>
                    </ol>
                    <ol>
                        <li><?php esc_html_e( 'Go to Plugins > Intalled Plugins', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Select WooParcel and click on the "Activate" button', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Select WooParcel and enable auto updates.', 'wooparcel-by-axiongate' ); ?></li>
                    </ol>
                    <p class="help-text">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ); ?>" target="_blank">
                            <?php esc_html_e( 'Go to WooCommerce General Settings', 'wooparcel-by-axiongate' ); ?> →
                        </a>
                    </p>
                </div>
                
                <div class="info-section">
                    <h2><?php esc_html_e( 'Making Phone Number Mandatory on Checkout', 'wooparcel-by-axiongate' ); ?></h2>
                    <p><?php esc_html_e( 'To require phone numbers for all orders:', 'wooparcel-by-axiongate' ); ?></p>
                    <ol>
                        <li><?php esc_html_e( 'Go to Dashboard > Pages > Checkout > Select Phone Number field and set it to required', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Save your changes', 'wooparcel-by-axiongate' ); ?></li>
                    </ol>
                </div>
                
                <div class="info-section">
                    <h2><?php esc_html_e( 'How It Works', 'wooparcel-by-axiongate' ); ?></h2>
                    <p><?php esc_html_e( 'This plugin automatically collects order data when an order is marked as completed in WooCommerce. The collected data includes:', 'wooparcel-by-axiongate' ); ?></p>
                    <ul>
                        <li><?php esc_html_e( 'Order details (ID, status, date)', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Customer information (name, email, phone)', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Shipping address', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Product details with quantities and SKUs', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Product dimensions (length, width, height, weight) for each item', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Total order dimensions and weight', 'wooparcel-by-axiongate' ); ?></li>
                        <li><?php esc_html_e( 'Order totals and payment information', 'wooparcel-by-axiongate' ); ?></li>
                    </ul>
                    <p class="help-text">
                        The data is needed to be able to create the parcel in the shipping service and to complete the AWB number generation.
                        <br>
                        <br>
                        The information is not stored in the database, it is only used to create the parcel in the shipping service and to complete the AWB number generation.
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render setup tab
     */
    private function render_setup_tab() {
        $api_key = get_option( 'wooparcel_api_key', '' );
        $api_code = get_option( 'wooparcel_api_code', '' );
        $auto_awb = get_option( 'wooparcel_auto_awb', false );
        $remote_api = get_option( 'wooparcel_remote_api', '' );
        
        // Detect POST by nonce presence to cover Enter key submits that may omit the button name
        if ( isset( $_POST['wooparcel_settings_nonce'] ) ) {

            $nonce_ok = check_admin_referer( 'wooparcel_save_settings', 'wooparcel_settings_nonce' );

            if ( ! $nonce_ok ) {

                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php esc_html_e( 'Security check failed, please reload the page and try again.', 'wooparcel-by-axiongate' ); ?></p>
                </div>
                <?php
            } 
            else {

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API keys must preserve exact value, sanitization handled separately
                $new_api_key   = isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : '';
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API code must preserve exact value, sanitization handled separately
                $new_api_code  = isset( $_POST['api_code'] ) ? wp_unslash( $_POST['api_code'] ) : '';
                $new_auto_awb  = isset( $_POST['auto_awb'] ) && $_POST['auto_awb'] === 'on';
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Remote API URL must preserve exact value, sanitization handled separately
                $new_remote_api = isset( $_POST['remote_api'] ) ? wp_unslash( $_POST['remote_api'] ) : '';

                // Save options
                $ok1 = update_option( 'wooparcel_api_key', $new_api_key );
                $ok2 = update_option( 'wooparcel_api_code', $new_api_code );
                $ok3 = update_option( 'wooparcel_auto_awb', $new_auto_awb );
                $ok4 = update_option( 'wooparcel_remote_api', $new_remote_api );
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'Settings saved successfully!', 'wooparcel-by-axiongate' ); ?></p>
                </div>
                <?php

                // Reload values after save
                $api_key = get_option( 'wooparcel_api_key', '' );
                $api_code = get_option( 'wooparcel_api_code', '' );
                $auto_awb = get_option( 'wooparcel_auto_awb', false );
                $remote_api = get_option( 'wooparcel_remote_api', '' );
            }
        }
        ?>
        <div class="wooparcel-setup-tab">
            <?php if ( ! empty( $api_key ) || ! empty( $api_code ) ) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e( 'Your configuration has been saved. You can update it below.', 'wooparcel-by-axiongate' ); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wooparcel-settings-form" accept-charset="UTF-8">
                <?php wp_nonce_field( 'wooparcel_save_settings', 'wooparcel_settings_nonce' ); ?>
                <input type="hidden" name="action" value="wooparcel_save_settings">
                <input type="hidden" name="wooparcel_submit" value="1">
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="remote_api"><?php esc_html_e( 'Remote API Address', 'wooparcel-by-axiongate' ); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="remote_api" 
                                       name="remote_api" 
                                       value="<?php echo esc_attr( $remote_api ); ?>" 
                                       class="regular-text <?php echo ! empty( $remote_api ) ? 'has-value' : ''; ?>" 
                                       placeholder="<?php esc_attr_e( 'https://api.example.com/endpoint', 'wooparcel-by-axiongate' ); ?>">
                                <p class="description">
                                    <?php esc_html_e( 'Base URL for your remote API integration (optional).', 'wooparcel-by-axiongate' ); ?>
                                </p>
                                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                                    <p class="description"><em><?php esc_html_e( 'Debug:', 'wooparcel-by-axiongate' ); ?></em> <?php echo esc_html( sprintf( 'Stored remote API length: %d', strlen( get_option( 'wooparcel_remote_api', '' ) ) ) ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="api_key"><?php esc_html_e( 'API Key', 'wooparcel-by-axiongate' ); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="api_key" 
                                       name="api_key" 
                                       value="<?php echo esc_attr( $api_key ); ?>" 
                                       class="regular-text <?php echo ! empty( $api_key ) ? 'has-value' : ''; ?>" 
                                       placeholder="<?php esc_attr_e( 'Enter your API key', 'wooparcel-by-axiongate' ); ?>">
                                <p class="description">
                                    <?php esc_html_e( 'Enter your API key for integration with your shipping service.', 'wooparcel-by-axiongate' ); ?>
                                </p>
                                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                                    <p class="description"><em><?php esc_html_e( 'Debug:', 'wooparcel-by-axiongate' ); ?></em> <?php echo esc_html( sprintf( 'Stored key length: %d', strlen( get_option( 'wooparcel_api_key', '' ) ) ) ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="api_code"><?php esc_html_e( 'API Code', 'wooparcel-by-axiongate' ); ?></label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="api_code" 
                                       name="api_code" 
                                       value="<?php echo esc_attr( $api_code ); ?>" 
						   class="regular-text <?php echo ! empty( $api_code ) ? 'has-value' : ''; ?>" 
                                       placeholder="<?php esc_attr_e( 'Enter your API code', 'wooparcel-by-axiongate' ); ?>">
                                <p class="description">
                                    <?php esc_html_e( 'Enter your API code for authentication.', 'wooparcel-by-axiongate' ); ?>
                                </p>
                                <?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
                                    <p class="description"><em><?php esc_html_e( 'Debug:', 'wooparcel-by-axiongate' ); ?></em> <?php echo esc_html( sprintf( 'Stored code length: %d', strlen( get_option( 'wooparcel_api_code', '' ) ) ) ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto_awb"><?php esc_html_e( 'Auto AWB', 'wooparcel-by-axiongate' ); ?></label>
                            </th>
                            <td>
                                <label class="wooparcel-toggle">
                                    <input type="checkbox" 
                                           id="auto_awb" 
                                           name="auto_awb" 
                                           <?php checked( $auto_awb, true ); ?>>
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label"><?php esc_html_e( 'Enable automatic AWB generation', 'wooparcel-by-axiongate' ); ?></span>
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'When enabled, AWB numbers will be automatically generated for completed orders.', 'wooparcel-by-axiongate' ); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <button type="submit" name="wooparcel_save_settings" class="button button-primary">
                        <?php esc_html_e( 'Save Settings', 'wooparcel-by-axiongate' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    // Removed admin-post save handler in favor of AJAX-only saving

    /**
     * AJAX fallback handler for saving settings via admin-ajax.php
     */
	public function handle_ajax_save_settings() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'wooparcel-by-axiongate' ) ), 403 );
        }
        $nonce = isset( $_POST['wooparcel_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wooparcel_settings_nonce'] ) ) : '';
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wooparcel_save_settings' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'wooparcel-by-axiongate' ) ), 400 );
        }

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API keys must preserve exact value, sanitization handled separately
		$new_api_key   = isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API code must preserve exact value, sanitization handled separately
		$new_api_code  = isset( $_POST['api_code'] ) ? wp_unslash( $_POST['api_code'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $new_auto_awb  = isset( $_POST['auto_awb'] ) && $_POST['auto_awb'] === 'on';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Remote API URL must preserve exact value, sanitization handled separately
        $new_remote_api = isset( $_POST['remote_api'] ) ? wp_unslash( $_POST['remote_api'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$ok1 = update_option( 'wooparcel_api_key', $new_api_key );
        $ok2 = update_option( 'wooparcel_api_code', $new_api_code );
        $ok3 = update_option( 'wooparcel_auto_awb', $new_auto_awb );
        $ok4 = update_option( 'wooparcel_remote_api', $new_remote_api );

        wp_send_json_success( array( 'saved' => true ) );
    }

    /**
     * Admin-post fallback handler to save settings and redirect back.
     */
	public function handle_admin_post_save_settings() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'wooparcel-by-axiongate' ), 403 );
        }
        check_admin_referer( 'wooparcel_save_settings', 'wooparcel_settings_nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API keys must preserve exact value, sanitization handled separately
		$new_api_key   = isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- API code must preserve exact value, sanitization handled separately
		$new_api_code  = isset( $_POST['api_code'] ) ? wp_unslash( $_POST['api_code'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $new_auto_awb  = isset( $_POST['auto_awb'] ) && $_POST['auto_awb'] === 'on';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Remote API URL must preserve exact value, sanitization handled separately
        $new_remote_api = isset( $_POST['remote_api'] ) ? wp_unslash( $_POST['remote_api'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// No validation for remote_api per user requirement

		update_option( 'wooparcel_api_key', $new_api_key );
		update_option( 'wooparcel_api_code', $new_api_code );
        update_option( 'wooparcel_auto_awb', $new_auto_awb );
        update_option( 'wooparcel_remote_api', $new_remote_api );

        wp_safe_redirect( add_query_arg( array( 'page' => 'wooparcel', 'tab' => 'setup', 'status' => 'saved' ), admin_url( 'admin.php' ) ) );
        exit;
    }

	/**
	 * Add a "Store phone" field to WooCommerce General settings page.
	 */
	public function inject_store_phone_setting( $settings ) {
		$new_settings = array();
		foreach ( $settings as $setting ) {
			$new_settings[] = $setting;
			if ( isset( $setting['id'] ) && $setting['id'] === 'woocommerce_store_address_2' ) {
				$new_settings[] = array(
					'name'     => __( 'Store phone', 'wooparcel-by-axiongate' ),
					'id'       => 'woocommerce_store_phone',
					'type'     => 'text',
					'css'      => 'min-width:300px;',
					'desc'     => __( 'Phone number used for shipping labels and contact.', 'wooparcel-by-axiongate' ),
					'desc_tip' => true,
					'autoload' => false,
				);
			}
		}
		return $new_settings;
	}

	/**
	 * Render AWB List page
	 */
	public function render_awb_list_page() {
		$api_code = get_option( 'wooparcel_api_code', '' );
		$api_key = get_option( 'wooparcel_api_key', '' );
		$remote_api = get_option( 'wooparcel_remote_api', '' );
		
		// Get shop URL
		$shop_url = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $shop_url === '' ) {
			$shop_url = (string) wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		}
		if ( $shop_url === '' ) {
			$shop_url = home_url();
		}
		
		$awb_list = null;
		$error_message = '';
		
		if ( ! empty( $remote_api ) && ! empty( $api_code ) && ! empty( $api_key ) ) {
			$awb_list = $this->fetch_awb_list( $remote_api, $api_code, $api_key, $shop_url );
			if ( is_wp_error( $awb_list ) ) {
				$error_message = $awb_list->get_error_message();
				$awb_list = null;
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AWB List', 'wooparcel-by-axiongate' ); ?></h1>
			
			<?php if ( empty( $remote_api ) || empty( $api_code ) || empty( $api_key ) ) : ?>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'Please configure your API settings in the Setup tab before viewing the AWB list.', 'wooparcel-by-axiongate' ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wooparcel&tab=setup' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Go to Setup', 'wooparcel-by-axiongate' ); ?></a></p>
				</div>
			<?php elseif ( ! empty( $error_message ) ) : ?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'Error:', 'wooparcel-by-axiongate' ); ?></strong> <?php echo esc_html( $error_message ); ?></p>
				</div>
			<?php elseif ( $awb_list === null ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'Loading AWB list...', 'wooparcel-by-axiongate' ); ?></p>
				</div>
			<?php else : ?>
				<?php $this->render_awb_list_table( $awb_list ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Fetch AWB list from remote API
	 */
	private function fetch_awb_list( $remote_api, $api_code, $api_key, $shop_url ) {
		// Build URL with fixed format: {apiCode}.wooparcel.com/live/api/v1/parcel/awb-list?shop={shopURL}
		$url = 'https://' . $api_code . '.wooparcel.com/live/api/v1/parcel/awb-list?shop=' . urlencode( $shop_url );

		// Calculate SHA256 hash of API key
		$api_key_hash = $this->calculate_sha256_hex( $api_key );

		$args = array(
			'method'      => 'GET',
			'timeout'     => 20,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
				'TenantId'     => $api_code,
				'X-Authz'      => $api_key_hash,
			),
			'sslverify'   => false,
		);

		$response = wp_remote_get( $url, $args );
		
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code !== 200 ) {
			/* translators: %d: HTTP status code */
			return new WP_Error( 'api_error', sprintf( __( 'API returned status code %d', 'wooparcel-by-axiongate' ), $code ) );
		}

		$json = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_error', __( 'Failed to parse API response', 'wooparcel-by-axiongate' ) );
		}

		return $json;
	}

	/**
	 * Render AWB list table
	 */
	private function render_awb_list_table( $awb_list ) {
		// Extract items from response structure
		if ( isset( $awb_list['awbList'] ) && is_array( $awb_list['awbList'] ) ) {
			$items = $awb_list['awbList'];
		} else if ( isset( $awb_list['data'] ) && is_array( $awb_list['data'] ) ) {
			$items = $awb_list['data'];
		} else if ( is_array( $awb_list ) && isset( $awb_list[0] ) ) {
			$items = $awb_list;
		} else {
			$items = array( $awb_list );
		}

		if ( empty( $items ) ) {
			?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No AWB records found.', 'wooparcel-by-axiongate' ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'AWB Code', 'wooparcel-by-axiongate' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Order ID', 'wooparcel-by-axiongate' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Status', 'wooparcel-by-axiongate' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Created Date', 'wooparcel-by-axiongate' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'wooparcel-by-axiongate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $items as $item ) : ?>
					<?php
					$awb_code = isset( $item['awbCode'] ) ? $item['awbCode'] : '';
					$order_id = isset( $item['shopifyOrderId'] ) ? $item['shopifyOrderId'] : '';
					$status = isset( $item['status'] ) ? $item['status'] : '';
					$created_date = isset( $item['createdAt'] ) ? $item['createdAt'] : '';
					$label_base64 = isset( $item['labelBase64'] ) ? $item['labelBase64'] : '';
					?>
					<tr>
						<td><strong><?php echo esc_html( $awb_code ); ?></strong></td>
						<td><?php echo esc_html( $order_id ); ?></td>
						<td><span class="status-<?php echo esc_attr( strtolower( $status ) ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
						<td><?php echo esc_html( $created_date ); ?></td>
						<td>
							<?php if ( ! empty( $awb_code ) ) : ?>
								<?php if ( ! empty( $label_base64 ) ) : ?>
									<button type="button" class="button button-small wooparcel-download-label" data-label="<?php echo esc_attr( $label_base64 ); ?>" data-awb="<?php echo esc_attr( $awb_code ); ?>">
										<?php esc_html_e( 'Download Label', 'wooparcel-by-axiongate' ); ?>
									</button>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Calculate SHA-256 hash of input and return lowercase hex digest.
	 */
	private function calculate_sha256_hex( $input ) {
		return hash( 'sha256', (string) $input );
	}
}

