<?php
/**
 * Plugin Name:       Customer Profile Page for WooCommerce
 * Plugin URI:        https://github.com/regular-alves/customer-profile-page-for-woocommerce
 * Description:       Adds an enhanced customer profile page to WooCommerce My Account.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Gois.dev
 * Author URI:        https://gois.dev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       customer-profile-page-for-woocommerce
 * Domain Path:       /languages
 *
 * WC requires at least: 9.0
 * WC tested up to:      9.0
 *
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage;

use WCCustomerProfilePage\Core\Plugin;

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

add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap' );

function bootstrap(): void {
	if ( ! class_exists( \WooCommerce::class ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\missing_woocommerce_notice' );
		return;
	}

	Plugin::get_instance()->init();
}

function missing_woocommerce_notice(): void {
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'Customer Profile Page for WooCommerce requires WooCommerce to be installed and active.', 'customer-profile-page-for-woocommerce' ); ?>
		</p>
	</div>
	<?php
}
