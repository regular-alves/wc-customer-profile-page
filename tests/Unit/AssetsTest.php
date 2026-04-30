<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit
 */

namespace WCCustomerProfilePage\Tests\Unit;

use WCCustomerProfilePage\Core\Assets;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class AssetsTest extends TestCase {

	private Assets $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new Assets();
	}

	public function test_is_profile_page_returns_false_for_other_hook(): void {
		$this->assertFalse( $this->sut->is_profile_page( 'woocommerce_page_wc-orders' ) );
		$this->assertFalse( $this->sut->is_profile_page( 'toplevel_page_woocommerce' ) );
		$this->assertFalse( $this->sut->is_profile_page( '' ) );
	}

	public function test_enqueue_does_nothing_on_wrong_hook(): void {
		WP_Mock::userFunction( 'wp_enqueue_style' )->never();

		$this->sut->enqueue( 'edit.php' );

		$this->assertConditionsMet();
	}

	/**
	 * @dataProvider entry_point_hooks_provider
	 */
	public function test_is_entry_point_page_returns_true_for_wc_hooks( string $hook ): void {
		$this->assertTrue( $this->sut->is_entry_point_page( $hook ) );
	}

	public function entry_point_hooks_provider(): array {
		return [
			[ 'post.php' ],
			[ 'woocommerce_page_wc-orders' ],
			[ 'woocommerce_page_wc-reports' ],
			[ 'woocommerce_page_wc-admin' ],
		];
	}

	public function test_is_entry_point_page_returns_false_for_other_hooks(): void {
		$this->assertFalse( $this->sut->is_entry_point_page( 'edit.php' ) );
		$this->assertFalse( $this->sut->is_entry_point_page( 'users.php' ) );
		$this->assertFalse( $this->sut->is_entry_point_page( '' ) );
	}

	public function test_enqueue_entry_points_does_nothing_on_wrong_hook(): void {
		WP_Mock::userFunction( 'wp_enqueue_script' )->never();
		WP_Mock::userFunction( 'wp_localize_script' )->never();

		$this->sut->enqueue_entry_points( 'edit.php' );

		$this->assertConditionsMet();
	}
}
