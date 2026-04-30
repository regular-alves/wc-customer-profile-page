<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Intercepts WooCommerce entry points that normally link to user-edit.php
 * and redirects them to the dedicated customer profile page.
 *
 * WP > Users list is intentionally NOT intercepted because it does not use
 * get_edit_user_link() internally — only WC contexts do.
 */
class EntryPoints {

	public function register(): void {
		add_filter( 'edit_profile_url', [ $this, 'replace_with_profile_page' ], 10, 2 );
	}

	public function replace_with_profile_page( string $url, int $user_id ): string {
		if ( ! is_admin() ) {
			return $url;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $url;
		}

		return $this->build_profile_url( $user_id );
	}

	public function build_profile_url( int $user_id ): string {
		return add_query_arg(
			[
				'page'    => ProfilePage::PAGE_SLUG,
				'user_id' => $user_id,
			],
			admin_url( 'admin.php' )
		);
	}
}
