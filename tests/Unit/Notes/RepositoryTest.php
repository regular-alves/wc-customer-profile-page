<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit\Notes
 */

namespace WCCustomerProfilePage\Tests\Unit\Notes;

use WCCustomerProfilePage\Notes\Repository;
use WP_Mock\Tools\TestCase;
use WP_Mock;
use Mockery;

class RepositoryTest extends TestCase {

	private Repository $sut;

	/** @var \Mockery\MockInterface */
	private $wpdb;

	public function setUp(): void {
		parent::setUp();

		$this->wpdb         = Mockery::mock( 'wpdb' );
		$this->wpdb->prefix = 'wp_';
		$this->wpdb->users  = 'wp_users';
		$GLOBALS['wpdb']    = $this->wpdb;

		$this->sut = new Repository();
	}

	public function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	// ── table_name ───────────────────────────────────────────────────────────────

	public function test_table_name_uses_wpdb_prefix(): void {
		$this->assertSame( 'wp_cpfw_customer_notes', $this->sut->table_name() );
	}

	// ── create ───────────────────────────────────────────────────────────────────

	public function test_create_returns_insert_id_on_success(): void {
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-01-01 12:00:00' );

		$this->wpdb->shouldReceive( 'insert' )->once()->andReturn( 1 );
		$this->wpdb->insert_id = 42;

		$this->assertSame( 42, $this->sut->create( 5, 1, '<p>Note</p>' ) );
	}

	public function test_create_returns_zero_when_insert_fails(): void {
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-01-01 12:00:00' );

		$this->wpdb->shouldReceive( 'insert' )->once()->andReturn( false );

		$this->assertSame( 0, $this->sut->create( 5, 1, '<p>Note</p>' ) );
	}

	// ── update ───────────────────────────────────────────────────────────────────

	public function test_update_returns_true_on_success(): void {
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-01-01 12:00:00' );

		$this->wpdb->shouldReceive( 'update' )->once()->andReturn( 1 );

		$this->assertTrue( $this->sut->update( 10, 1, '<p>Updated</p>' ) );
	}

	public function test_update_returns_false_when_row_not_owned_by_author(): void {
		WP_Mock::userFunction( 'current_time' )->with( 'mysql' )->andReturn( '2026-01-01 12:00:00' );

		$this->wpdb->shouldReceive( 'update' )->once()->andReturn( 0 );

		$this->assertFalse( $this->sut->update( 10, 99, '<p>Updated</p>' ) );
	}

	// ── delete ───────────────────────────────────────────────────────────────────

	public function test_delete_returns_true_on_success(): void {
		$this->wpdb->shouldReceive( 'delete' )->once()->andReturn( 1 );

		$this->assertTrue( $this->sut->delete( 10, 1 ) );
	}

	public function test_delete_returns_false_when_row_not_owned_by_author(): void {
		$this->wpdb->shouldReceive( 'delete' )->once()->andReturn( 0 );

		$this->assertFalse( $this->sut->delete( 10, 99 ) );
	}

	// ── get_by_customer ──────────────────────────────────────────────────────────

	public function test_get_by_customer_returns_rows(): void {
		$rows = [
			[
				'id'          => '1',
				'customer_id' => '5',
				'author_id'   => '1',
				'note'        => '<p>Hi</p>',
				'author_name' => 'Admin',
				'created_at'  => '2026-01-01 12:00:00',
				'updated_at'  => '2026-01-01 12:00:00',
			],
		];

		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'PREPARED SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->with( 'PREPARED SQL', ARRAY_A )->andReturn( $rows );

		$this->assertSame( $rows, $this->sut->get_by_customer( 5 ) );
	}

	public function test_get_by_customer_returns_empty_array_when_no_rows(): void {
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'PREPARED SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->andReturn( null );

		$this->assertSame( [], $this->sut->get_by_customer( 5 ) );
	}

	public function test_get_by_customer_calls_esc_like_when_search_is_given(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->once()->with( 'hello' )->andReturn( 'hello' );
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'PREPARED SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$this->sut->get_by_customer( 5, 1, 5, 'hello' );

		$this->assertConditionsMet();
	}

	public function test_get_by_customer_skips_esc_like_when_search_is_empty(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->never();
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'PREPARED SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$this->sut->get_by_customer( 5, 1, 5, '' );

		$this->assertConditionsMet();
	}

	public function test_get_by_customer_passes_author_id_to_prepare_when_provided(): void {
		// esc_like must NOT be called (no search), but prepare must receive args that include the author_id
		$this->wpdb->shouldReceive( 'esc_like' )->never();
		$this->wpdb->shouldReceive( 'prepare' )
			->once()
			->with( Mockery::type( 'string' ), 5, 2, 5, 0 ) // customer_id, author_id, per_page, offset
			->andReturn( 'PREPARED SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->andReturn( [] );

		$this->sut->get_by_customer( 5, 1, 5, '', 2 );

		$this->assertConditionsMet();
	}

	// ── count_by_customer ────────────────────────────────────────────────────────

	public function test_count_by_customer_returns_integer(): void {
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'COUNT SQL' );
		$this->wpdb->shouldReceive( 'get_var' )->once()->with( 'COUNT SQL' )->andReturn( '7' );

		$this->assertSame( 7, $this->sut->count_by_customer( 5 ) );
	}

	public function test_count_by_customer_calls_esc_like_when_search_is_given(): void {
		$this->wpdb->shouldReceive( 'esc_like' )->once()->with( 'keyword' )->andReturn( 'keyword' );
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'COUNT SQL' );
		$this->wpdb->shouldReceive( 'get_var' )->once()->andReturn( '3' );

		$this->sut->count_by_customer( 5, 'keyword' );

		$this->assertConditionsMet();
	}

	public function test_count_by_customer_returns_zero_when_get_var_returns_null(): void {
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'COUNT SQL' );
		$this->wpdb->shouldReceive( 'get_var' )->once()->andReturn( null );

		$this->assertSame( 0, $this->sut->count_by_customer( 5 ) );
	}

	// ── get_authors_by_customer ──────────────────────────────────────────────────

	public function test_get_authors_by_customer_returns_authors(): void {
		$authors = [
			[ 'author_id' => '1', 'author_name' => 'Admin' ],
			[ 'author_id' => '2', 'author_name' => 'Editor' ],
		];

		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'AUTHORS SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->with( 'AUTHORS SQL', ARRAY_A )->andReturn( $authors );

		$this->assertSame( $authors, $this->sut->get_authors_by_customer( 5 ) );
	}

	public function test_get_authors_by_customer_returns_empty_array_when_none(): void {
		$this->wpdb->shouldReceive( 'prepare' )->once()->andReturn( 'AUTHORS SQL' );
		$this->wpdb->shouldReceive( 'get_results' )->once()->andReturn( null );

		$this->assertSame( [], $this->sut->get_authors_by_customer( 5 ) );
	}
}
