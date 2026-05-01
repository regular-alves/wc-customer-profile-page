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
		add_filter( 'script_loader_tag', [ $this, 'add_module_type' ], 10, 2 );
	}

	public function add_module_type( string $tag, string $handle ): string {
		if ( 'wccp-customer-profile' === $handle ) {
			return str_replace( ' src=', ' type="module" src=', $tag );
		}
		return $tag;
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

		wp_localize_script(
			'wccp-customer-profile',
			'wccpNotes',
			[
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'wccp_notes' ),
				'currentUserId' => (string) get_current_user_id(),
				'i18n'          => [
					'edit'          => __( 'Edit', 'customer-profile-page-for-woocommerce' ),
					'delete'        => __( 'Delete', 'customer-profile-page-for-woocommerce' ),
					'save'          => __( 'Save', 'customer-profile-page-for-woocommerce' ),
					'cancel'        => __( 'Cancel', 'customer-profile-page-for-woocommerce' ),
					'bold'          => __( 'Bold', 'customer-profile-page-for-woocommerce' ),
					'italic'        => __( 'Italic', 'customer-profile-page-for-woocommerce' ),
					'underline'     => __( 'Underline', 'customer-profile-page-for-woocommerce' ),
					'confirmDelete' => __( 'Are you sure you want to delete this note?', 'customer-profile-page-for-woocommerce' ),
					'empty'         => __( 'No notes yet.', 'customer-profile-page-for-woocommerce' ),
					'insertLink'    => __( 'Enter URL:', 'customer-profile-page-for-woocommerce' ),
					'error'         => __( 'Something went wrong. Please try again.', 'customer-profile-page-for-woocommerce' ),
					'loading'       => __( 'Loading…', 'customer-profile-page-for-woocommerce' ),
					'allAuthors'    => __( 'All authors', 'customer-profile-page-for-woocommerce' ),
					'page'          => __( 'Page', 'customer-profile-page-for-woocommerce' ),
					'of'            => __( 'of', 'customer-profile-page-for-woocommerce' ),
				],
			]
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
