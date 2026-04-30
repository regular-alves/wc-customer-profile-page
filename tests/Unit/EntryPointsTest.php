<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit
 */

namespace WCCustomerProfilePage\Tests\Unit;

use WCCustomerProfilePage\Profile\EntryPoints;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class EntryPointsTest extends TestCase {

	private EntryPoints $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new EntryPoints();
	}

	public function test_returns_original_url_when_not_in_admin(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$result = $this->sut->replace_with_profile_page( 'https://example.com/wp-admin/user-edit.php?user_id=1', 1 );

		$this->assertSame( 'https://example.com/wp-admin/user-edit.php?user_id=1', $result );
	}

	public function test_returns_original_url_when_no_capability(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );

		$result = $this->sut->replace_with_profile_page( 'https://example.com/wp-admin/user-edit.php?user_id=5', 5 );

		$this->assertSame( 'https://example.com/wp-admin/user-edit.php?user_id=5', $result );
	}
}
