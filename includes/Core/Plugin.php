<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Core;

defined( 'ABSPATH' ) || exit;

use WCCustomerProfilePage\Notes\Ajax as NotesAjax;
use WCCustomerProfilePage\Notes\Repository as NotesRepository;
use WCCustomerProfilePage\Profile\EntryPoints;
use WCCustomerProfilePage\Profile\ProfilePage;

final class Plugin {

	private static ?self $instance = null;

	private function __construct() {}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		$this->maybe_upgrade_db();

		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );

		( new EntryPoints() )->register();
		( new ProfilePage() )->register();
		( new Assets() )->register();
		( new NotesAjax( new NotesRepository() ) )->register();
	}

	private function maybe_upgrade_db(): void {
		if ( get_option( 'wccp_db_version' ) !== WCCP_VERSION ) {
			Installer::activate();
			update_option( 'wccp_db_version', WCCP_VERSION );
		}
	}

	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WCCP_PLUGIN_FILE,
				true
			);
		}
	}
}
