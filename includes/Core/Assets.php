<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Core;

defined( 'ABSPATH' ) || exit;

use WCCustomerProfilePage\Profile\ProfilePage;

class Assets {

	public function register(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_entry_points' ] );
	}

	public function enqueue( string $hook ): void {
		if ( ! $this->is_profile_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'wccp-customer-profile',
			WCCP_PLUGIN_URL . 'assets/css/customer-profile.css',
			[],
			WCCP_VERSION
		);

		wp_enqueue_script(
			'wccp-customer-profile',
			WCCP_PLUGIN_URL . 'assets/js/customer-profile.js',
			[],
			WCCP_VERSION,
			true
		);
	}

	public function enqueue_entry_points( string $hook ): void {
		if ( ! $this->is_entry_point_page( $hook ) ) {
			return;
		}

		wp_enqueue_script(
			'wccp-entry-points',
			WCCP_PLUGIN_URL . 'assets/js/entry-points.js',
			[],
			WCCP_VERSION,
			true
		);

		wp_localize_script(
			'wccp-entry-points',
			'wccpEntryPoints',
			[
				'profileUrl' => add_query_arg( 'page', ProfilePage::PAGE_SLUG, admin_url( 'admin.php' ) ),
			]
		);
	}

	public function is_profile_page( string $hook ): bool {
		return 'admin_page_' . ProfilePage::PAGE_SLUG === $hook;
	}

	public function is_entry_point_page( string $hook ): bool {
		return in_array(
			$hook,
			[
				'post.php',
				'woocommerce_page_wc-orders',
				'woocommerce_page_wc-reports',
				'woocommerce_page_wc-admin',
			],
			true
		);
	}
}
