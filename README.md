=== WooParcel ===
Contributors: Your Name
Tags: woocommerce, orders, parcel, shipping
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage shop details, configure API settings, and collect order data when orders are completed.

== Description ==

WooParcel is a WooCommerce plugin that helps you:

* View helpful information on configuring shop details and making phone numbers mandatory
* Configure API settings for integration with shipping services
* Automatically collect order data when orders are marked as completed
* Generate AWB numbers automatically (optional)

== Features ==

* **Home Tab**: Provides clear instructions on:
  - How to set shop details in WooCommerce
  - How to make phone numbers mandatory for orders
  - What data is collected for completed orders

* **Setup Tab**: Configure your integration settings:
  - API Key and API Code inputs
  - Auto AWB toggle button
  - Save settings to local database

* **Order Collection**: Automatically collects the following data when an order is completed:
  - Order details (ID, status, dates)
  - Customer information (name, email, phone)
  - Billing and shipping addresses
  - Product details with SKUs
  - Order totals and payment information
  - Shipping information

== Installation ==

1. Upload the `wooparcel` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooParcel in the admin menu to configure settings

== Requirements ==

* WordPress 5.0 or higher
* WooCommerce 3.0 or higher
* PHP 7.2 or higher

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes, WooParcel requires WooCommerce to be installed and active.

= Where is the collected order data stored? =

Order data is collected and processed when orders are marked as completed. By default, it's logged to PHP error logs when WP_DEBUG is enabled. You can extend the plugin to send this data to external APIs or store it in custom database tables.

= What is the Auto AWB feature? =

The Auto AWB (Air Waybill) feature automatically generates AWB numbers for completed orders. This can be enabled or disabled in the Setup tab.

== Screenshots ==

1. Home tab with setup instructions
2. Setup tab with API configuration options

== Changelog ==

= 1.0.0 =
* Initial release
* Two-tab admin interface (Home and Setup)
* API configuration with persistent storage
* Automatic order data collection on completion
* Auto AWB generation feature
* Comprehensive order data collection
