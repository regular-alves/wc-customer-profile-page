<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit\Notes
 */

namespace WCCustomerProfilePage\Tests\Unit\Notes;

use WCCustomerProfilePage\Notes\Ajax;
use WCCustomerProfilePage\Notes\Repository;
use WP_Mock\Tools\TestCase;
use WP_Mock;
use Mockery;

class AjaxTest extends TestCase {

	private Ajax $sut;

	/** @var Repository&\Mockery\MockInterface */
	private $repo;

	public function setUp(): void {
		parent::setUp();
		$this->repo = Mockery::mock( Repository::class );
		$this->sut  = new Ajax( $this->repo );
	}

	public function tearDown(): void {
		$_POST = [];
		parent::tearDown();
	}

	// ── Helpers ──────────────────────────────────────────────────────────────────

	/** Sets up the nonce check and capability gate for a passing request. */
	private function mock_authorized(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )->with( 'wccp_notes' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( true );
	}

	/** Returns an absint-compatible WP_Mock stub that casts the first arg. */
	private function mock_absint(): void {
		WP_Mock::userFunction( 'absint' )->andReturnUsing( fn( $n ) => abs( (int) $n ) );
	}

	// ── register ─────────────────────────────────────────────────────────────────

	public function test_register_adds_four_ajax_actions(): void {
		WP_Mock::expectActionAdded( 'wp_ajax_wccp_get_notes',   [ $this->sut, 'handle_get' ] );
		WP_Mock::expectActionAdded( 'wp_ajax_wccp_add_note',    [ $this->sut, 'handle_add' ] );
		WP_Mock::expectActionAdded( 'wp_ajax_wccp_update_note', [ $this->sut, 'handle_update' ] );
		WP_Mock::expectActionAdded( 'wp_ajax_wccp_delete_note', [ $this->sut, 'handle_delete' ] );

		$this->sut->register();

		$this->assertConditionsMet();
	}

	// ── handle_get ───────────────────────────────────────────────────────────────

	public function test_handle_get_sends_403_when_user_lacks_capability(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )->with( 'wccp_notes' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_get();
	}

	public function test_handle_get_sends_400_when_customer_id_is_missing(): void {
		$_POST = [];
		$this->mock_authorized();
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_get();
	}

	public function test_handle_get_returns_paginated_notes_on_success(): void {
		$_POST = [ 'customer_id' => '5' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_option' )->with( 'date_format' )->andReturn( 'Y-m-d' );

		$this->repo->shouldReceive( 'get_by_customer' )->once()->andReturn( [] );
		$this->repo->shouldReceive( 'count_by_customer' )->once()->andReturn( 0 );
		$this->repo->shouldReceive( 'get_authors_by_customer' )->once()->andReturn( [] );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( Mockery::on( function ( $data ) {
				return isset( $data['notes'], $data['total'], $data['page'], $data['per_page'], $data['total_pages'], $data['authors'] )
					&& $data['total']      === 0
					&& $data['page']       === 1
					&& $data['per_page']   === 5
					&& $data['total_pages'] === 1;
			} ) )
			->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_get();
	}

	public function test_handle_get_maps_avatar_url_and_date_onto_each_note(): void {
		$_POST = [ 'customer_id' => '5' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_option' )->with( 'date_format' )->andReturn( 'Y-m-d' );
		WP_Mock::userFunction( 'get_avatar_url' )->andReturn( 'https://example.com/avatar.jpg' );
		WP_Mock::userFunction( 'date_i18n' )->andReturn( '2026-01-01' );

		$note = [ 'id' => '1', 'author_id' => '1', 'note' => '<p>Hi</p>', 'created_at' => '2026-01-01 12:00:00', 'author_name' => 'Admin', 'customer_id' => '5', 'updated_at' => '2026-01-01 12:00:00' ];

		$this->repo->shouldReceive( 'get_by_customer' )->andReturn( [ $note ] );
		$this->repo->shouldReceive( 'count_by_customer' )->andReturn( 1 );
		$this->repo->shouldReceive( 'get_authors_by_customer' )->andReturn( [] );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( Mockery::on( function ( $data ) {
				$n = $data['notes'][0];
				return $n['avatar_url'] === 'https://example.com/avatar.jpg'
					&& $n['date_formatted'] === '2026-01-01';
			} ) )
			->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_get();
	}

	// ── handle_add ───────────────────────────────────────────────────────────────

	public function test_handle_add_sends_403_when_user_lacks_capability(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )->with( 'wccp_notes' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_add();
	}

	public function test_handle_add_sends_400_when_customer_id_is_missing(): void {
		$_POST = [ 'note' => '<p>Note</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_add();
	}

	public function test_handle_add_sends_400_when_note_is_empty_after_kses(): void {
		$_POST = [ 'customer_id' => '5', 'note' => '<script>bad</script>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '' ); // strips everything
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_add();
	}

	public function test_handle_add_sends_500_when_repository_fails(): void {
		$_POST = [ 'customer_id' => '5', 'note' => '<p>Note</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '<p>Note</p>' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );

		$this->repo->shouldReceive( 'create' )->once()->andReturn( false );

		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_add();
	}

	public function test_handle_add_returns_success_with_note_data(): void {
		$_POST = [ 'customer_id' => '5', 'note' => '<p>Note</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '<p>Note</p>' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		WP_Mock::userFunction( 'get_option' )->with( 'date_format' )->andReturn( 'Y-m-d' );
		WP_Mock::userFunction( 'date_i18n' )->andReturn( '2026-01-01' );
		WP_Mock::userFunction( 'get_avatar_url' )->andReturn( 'https://example.com/avatar.jpg' );

		$user               = new \stdClass();
		$user->display_name = 'Admin';
		WP_Mock::userFunction( 'get_userdata' )->with( 1 )->andReturn( $user );

		$this->repo->shouldReceive( 'create' )->once()->andReturn( 42 );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( Mockery::on( function ( $data ) {
				return $data['id'] === 42
					&& $data['note'] === '<p>Note</p>'
					&& $data['author_name'] === 'Admin';
			} ) )
			->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_add();
	}

	// ── handle_update ────────────────────────────────────────────────────────────

	public function test_handle_update_sends_403_when_user_lacks_capability(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )->with( 'wccp_notes' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_update();
	}

	public function test_handle_update_sends_400_when_note_id_is_missing(): void {
		$_POST = [ 'note' => '<p>Updated</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '<p>Updated</p>' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_update();
	}

	public function test_handle_update_sends_500_when_repository_fails(): void {
		$_POST = [ 'note_id' => '10', 'note' => '<p>Updated</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '<p>Updated</p>' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );

		$this->repo->shouldReceive( 'update' )->once()->andReturn( false );

		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_update();
	}

	public function test_handle_update_returns_success(): void {
		$_POST = [ 'note_id' => '10', 'note' => '<p>Updated</p>' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'wp_kses' )->andReturn( '<p>Updated</p>' );
		WP_Mock::userFunction( 'wp_unslash' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );

		$this->repo->shouldReceive( 'update' )->once()->andReturn( true );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( Mockery::on( fn( $data ) => $data['id'] === 10 && $data['note'] === '<p>Updated</p>' ) )
			->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_update();
	}

	// ── handle_delete ────────────────────────────────────────────────────────────

	public function test_handle_delete_sends_403_when_user_lacks_capability(): void {
		WP_Mock::userFunction( 'check_ajax_referer' )->with( 'wccp_notes' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_woocommerce' )->andReturn( false );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_delete();
	}

	public function test_handle_delete_sends_400_when_note_id_is_missing(): void {
		$_POST = [];

		$this->mock_authorized();
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_delete();
	}

	public function test_handle_delete_sends_500_when_repository_fails(): void {
		$_POST = [ 'note_id' => '10' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );

		$this->repo->shouldReceive( 'delete' )->once()->andReturn( false );

		WP_Mock::userFunction( 'wp_send_json_error' )->once()->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_delete();
	}

	public function test_handle_delete_returns_success(): void {
		$_POST = [ 'note_id' => '10' ];

		$this->mock_authorized();
		$this->mock_absint();
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );

		$this->repo->shouldReceive( 'delete' )->once()->andReturn( true );

		WP_Mock::userFunction( 'wp_send_json_success' )
			->once()
			->with( [ 'id' => 10 ] )
			->andThrow( new \RuntimeException() );

		$this->expectException( \RuntimeException::class );
		$this->sut->handle_delete();
	}
}
