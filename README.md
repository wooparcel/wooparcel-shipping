# WooParcel by AxionGate

Manage shop details, configure API settings, and collect order data when orders are completed.

- Plugin Name: WooParcel by AxionGate
- Plugin URI: https://wooparcel.com
- Description: Manage shop details, configure API settings, and collect order data when orders are completed.
- Version: 1.0.0
- Author: wooparcel
- Author URI: https://wooparcel.com
- License: GPL v2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html
- Text Domain: wooparcel-by-axiongate
- Requires at least: WordPress 5.0
- Tested up to: 6.8
- Requires PHP: 7.2
- WC requires at least: 3.0
- WC tested up to: 8.0

## Description

WooParcel by AxionGate is a WooCommerce plugin that helps you:

- View helpful information on configuring shop details and making phone numbers mandatory
- Configure API settings for integration with shipping services (connections keys provided by WooParcel by AxionGate team after enrollment.)
- Automatically collect order data when orders are marked as completed
- Generate and list AWBs

## Features

- Home Tab: clear instructions on:
  - How to set shop details in WooCommerce
  - How to make phone numbers mandatory for orders
  - What data is collected for completed orders
- Setup Tab: configure your integration settings:
  - API Key and API Code inputs
  - Auto AWB toggle button
  - Save settings to local database
- Order Collection: automatically collects on order completion:
  - Order details (ID, status, dates)
  - Customer information (name, email, phone)
  - Billing and shipping addresses
  - Product details with SKUs
  - Order totals and payment information
  - Shipping information
- AWB List: view generated AWBs and download labels when available

## Installation

1. Upload the `wooparcel-by-axiongate` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooParcel by AxionGate in the admin menu to configure settings
4. Open the AWB List submenu to view recent AWBs and download labels

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Frequently Asked Questions

### Does this plugin require WooCommerce?
Yes, WooParcel requires WooCommerce to be installed and active.

### Where is the collected order data stored?
Order data is collected and processed when orders are marked as completed.

### What is the Auto AWB feature?
The Auto AWB (Air Waybill) feature automatically generates AWB numbers for completed orders. This can be enabled or disabled in the Setup tab.

## Screenshots

1. Home tab with setup instructions
2. Setup tab with API configuration options
3. AWB List Page

## Changelog

### 1.0.0
- Initial release
- Two-tab admin interface (Home and Setup)
- API configuration with persistent storage
- Automatic order data collection on completion
- Auto AWB generation feature
- Comprehensive order data collection


