<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit
 */

namespace WCCustomerProfilePage\Tests\Unit;

use WCCustomerProfilePage\Profile\ProfilePage;
use WP_Mock\Tools\TestCase;
use WP_Mock;

class ProfilePageTest extends TestCase {

	private ProfilePage $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ProfilePage();
	}

	// -------------------------------------------------------------------------
	// render
	// -------------------------------------------------------------------------

	public function test_render_dies_when_no_capability(): void {
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );
		WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_die' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->render();
	}

	public function test_render_dies_when_user_id_missing(): void {
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( true );
		WP_Mock::userFunction( 'absint' )->andReturn( 0 );
		WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_die' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->render();
	}

	public function test_render_dies_when_customer_not_found(): void {
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( true );
		$_GET['user_id'] = '99';
		WP_Mock::userFunction( 'absint' )->andReturn( 99 );
		WP_Mock::userFunction( 'esc_html__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_die' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );

		try {
			$this->sut->render();
		} finally {
			unset( $_GET['user_id'] );
		}
	}

	// -------------------------------------------------------------------------
	// highlight_menu
	// -------------------------------------------------------------------------

	public function test_highlight_menu_does_not_change_globals_on_other_page(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		global $parent_file, $submenu_file;
		$parent_file  = 'original-parent';
		$submenu_file = 'original-submenu';

		$this->sut->highlight_menu();

		$this->assertSame( 'original-parent', $parent_file );
		$this->assertSame( 'original-submenu', $submenu_file );

		unset( $GLOBALS['parent_file'], $GLOBALS['submenu_file'] );
	}

	// -------------------------------------------------------------------------
	// is_current_page
	// -------------------------------------------------------------------------

	public function test_is_current_page_returns_false_when_not_admin(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$this->assertFalse( $this->sut->is_current_page() );
	}

	public function test_is_current_page_returns_false_when_different_page(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		$_GET['page'] = 'wc-orders';

		$this->assertFalse( $this->sut->is_current_page() );

		unset( $_GET['page'] );
	}

	// -------------------------------------------------------------------------
	// set_customer_title
	// -------------------------------------------------------------------------

	public function test_set_customer_title_returns_original_when_not_current_page(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$result = $this->sut->set_customer_title( ' — My Site', 'Customer Profile' );

		$this->assertSame( ' — My Site', $result );
	}

	public function test_set_customer_title_returns_original_when_no_user_id(): void {
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		$_GET['page']    = ProfilePage::PAGE_SLUG;
		$_GET['user_id'] = '0';
		WP_Mock::userFunction( 'absint' )->andReturn( 0 );

		$result = $this->sut->set_customer_title( ' — My Site', 'Customer Profile' );

		$this->assertSame( ' — My Site', $result );

		unset( $_GET['page'], $_GET['user_id'] );
	}
}
