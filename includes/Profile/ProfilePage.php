<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Profile;

defined( 'ABSPATH' ) || exit;

use WCCustomerProfilePage\Customer\CustomerData;

class ProfilePage {

	public const PAGE_SLUG = 'wccp-customer-profile';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_head', [ $this, 'highlight_menu' ] );
		add_filter( 'admin_title', [ $this, 'set_customer_title' ], 10, 2 );
		add_action( 'admin_post_wccp_send_password_reset', [ $this, 'handle_send_password_reset' ] );
	}

	public function register_page(): void {
		add_submenu_page(
			null,
			__( 'Customer Profile', 'customer-profile-page-for-woocommerce' ),
			__( 'Customer Profile', 'customer-profile-page-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ $this, 'render' ]
		);
	}

	public function set_customer_title( string $admin_title, string $title ): string {
		if ( ! $this->is_current_page() ) {
			return $admin_title;
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $user_id ) {
			return $admin_title;
		}

		$customer = ( new CustomerData() )->get_customer( $user_id );

		if ( null === $customer ) {
			return $admin_title;
		}

		$full_name = trim( $customer->get_first_name() . ' ' . $customer->get_last_name() );

		if ( ! $full_name ) {
			return $admin_title;
		}

		return $full_name . $admin_title;
	}

	public function highlight_menu(): void {
		global $parent_file, $submenu_file; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! $this->is_current_page() ) {
			return;
		}

		$parent_file  = 'woocommerce'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu_file = 'wc-orders';   // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	public function is_current_page(): bool {
		return is_admin()
			&& isset( $_GET['page'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& self::PAGE_SLUG === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public function handle_send_password_reset(): void {
		check_admin_referer( 'wccp_send_password_reset' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'customer-profile-page-for-woocommerce' ) );
		}

		$user_id  = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$redirect = add_query_arg(
			[ 'page' => self::PAGE_SLUG, 'user_id' => $user_id ],
			admin_url( 'admin.php' )
		);

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			wp_safe_redirect( add_query_arg( 'wccp_reset', 'error', $redirect ) );
			exit;
		}

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			wp_safe_redirect( add_query_arg( 'wccp_reset', 'error', $redirect ) );
			exit;
		}

		WC()->mailer()->get_emails()['WC_Email_Customer_Reset_Password']->trigger( $user->user_login, $key );

		wp_safe_redirect( add_query_arg( 'wccp_reset', 'sent', $redirect ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'customer-profile-page-for-woocommerce' ) );
		}

		$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $user_id ) {
			wp_die( esc_html__( 'Invalid customer ID.', 'customer-profile-page-for-woocommerce' ) );
		}

		$data     = new CustomerData();
		$customer = $data->get_customer( $user_id );

		if ( null === $customer ) {
			wp_die( esc_html__( 'Customer not found.', 'customer-profile-page-for-woocommerce' ) );
		}

		$kpis               = $data->get_kpis( $user_id );
		$recent_orders      = $data->get_recent_orders( $user_id );
		$orders_url         = $data->get_orders_admin_url( $user_id );
		$first_order_date   = $data->get_first_order_date( $user_id );
		$avg_order_interval = $data->get_avg_order_interval( $user_id );

		load_template(
			WCCP_PLUGIN_DIR . 'templates/admin/customer-profile.php',
			false,
			compact( 'customer', 'kpis', 'recent_orders', 'orders_url', 'user_id', 'first_order_date', 'avg_order_interval' )
		);
	}
}
