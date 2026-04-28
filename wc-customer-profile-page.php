<?php
/**
 * Plugin Name:       WC Customer Profile Page
 * Plugin URI:        https://github.com/regular-alves/wc-customer-profile-page
 * Description:       Adds an enhanced customer profile page to WooCommerce My Account.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Rodrigo Alves
 * Author URI:        https://github.com/regular-alves/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wc-customer-profile-page
 * Domain Path:       /languages
 *
 * WC requires at least: 8.0
 * WC tested up to:      9.0
 *
 * @package WCCustomerProfilePage
 */

declare( strict_types=1 );

namespace WCCustomerProfilePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCCP_VERSION', '1.0.0' );
define( 'WCCP_PLUGIN_FILE', __FILE__ );
define( 'WCCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( WCCP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WCCP_PLUGIN_DIR . 'vendor/autoload.php';
}
