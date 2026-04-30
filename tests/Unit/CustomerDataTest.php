<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit
 */

namespace WCCustomerProfilePage\Tests\Unit;

use WCCustomerProfilePage\Customer\CustomerData;
use WP_Mock\Tools\TestCase;
use WP_Mock;
use Mockery;

/**
 * Subclass that overrides make_customer() to avoid instantiating WC_Customer.
 */
class TestableCustomerData extends CustomerData {

	private ?\WC_Customer $mock_customer = null;
	private bool $should_throw           = false;

	public function set_mock_customer( ?\WC_Customer $customer ): void {
		$this->mock_customer = $customer;
	}

	public function make_throw(): void {
		$this->should_throw = true;
	}

	protected function make_customer( int $user_id ): \WC_Customer {
		if ( $this->should_throw ) {
			throw new \Exception( 'WC_Customer error' );
		}

		return $this->mock_customer; // @phpstan-ignore-line
	}
}

class CustomerDataTest extends TestCase {

	private TestableCustomerData $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new TestableCustomerData();
	}

	// -------------------------------------------------------------------------
	// get_customer
	// -------------------------------------------------------------------------

	public function test_get_customer_returns_null_when_id_is_zero(): void {
		$mock = Mockery::mock( \WC_Customer::class );
		$mock->shouldReceive( 'get_id' )->andReturn( 0 );

		$this->sut->set_mock_customer( $mock );

		$this->assertNull( $this->sut->get_customer( 99 ) );
	}

	public function test_get_customer_returns_null_on_exception(): void {
		$this->sut->make_throw();

		$this->assertNull( $this->sut->get_customer( 1 ) );
	}

	// -------------------------------------------------------------------------
	// get_kpis
	// -------------------------------------------------------------------------

	public function test_get_kpis_returns_cached_value(): void {
		$cached = [ 'total_spent' => 100.0, 'order_count' => 2, 'avg_order' => 50.0 ];

		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'kpis_1', 'wccp_customer' )
			->andReturn( $cached );

		$result = $this->sut->get_kpis( 1 );

		$this->assertSame( $cached, $result );
	}

	public function test_get_kpis_avg_order_is_zero_when_no_orders(): void {
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wc_get_customer_total_spent' )->andReturn( 0.0 );
		WP_Mock::userFunction( 'wc_get_customer_order_count' )->andReturn( 0 );
		WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );

		$result = $this->sut->get_kpis( 1 );

		$this->assertSame( 0.0, $result['avg_order'] );
	}

	// -------------------------------------------------------------------------
	// get_recent_orders
	// -------------------------------------------------------------------------

	public function test_get_recent_orders_returns_cached_value(): void {
		$cached = [ Mockery::mock( \WC_Order::class ) ];

		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'orders_1_3', 'wccp_customer' )
			->andReturn( $cached );

		$result = $this->sut->get_recent_orders( 1 );

		$this->assertSame( $cached, $result );
	}

	public function test_get_recent_orders_respects_custom_limit(): void {
		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'orders_1_5', 'wccp_customer' )
			->andReturn( false );

		WP_Mock::userFunction( 'wc_get_order_statuses' )->andReturn( [] );
		WP_Mock::userFunction( 'wc_get_orders' )
			->with( \WP_Mock\Functions::type( 'array' ) )
			->andReturn( [] );
		WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );

		$this->sut->get_recent_orders( 1, 5 );

		$this->assertConditionsMet();
	}

	public function test_get_recent_orders_returns_empty_array_when_none(): void {
		WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		WP_Mock::userFunction( 'wc_get_order_statuses' )->andReturn( [] );
		WP_Mock::userFunction( 'wc_get_orders' )->andReturn( [] );
		WP_Mock::userFunction( 'wp_cache_set' )->andReturn( true );

		$result = $this->sut->get_recent_orders( 1 );

		$this->assertSame( [], $result );
	}

	// -------------------------------------------------------------------------
	// get_first_order_date
	// -------------------------------------------------------------------------

	public function test_get_first_order_date_returns_cached_datetime(): void {
		$mock_date = Mockery::mock( \WC_DateTime::class );

		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'first_order_1', 'wccp_customer' )
			->andReturn( $mock_date );

		$result = $this->sut->get_first_order_date( 1 );

		$this->assertSame( $mock_date, $result );
	}

	public function test_get_first_order_date_returns_null_from_zero_sentinel(): void {
		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'first_order_1', 'wccp_customer' )
			->andReturn( 0 );

		$result = $this->sut->get_first_order_date( 1 );

		$this->assertNull( $result );
	}

	public function test_get_first_order_date_returns_null_when_no_orders(): void {
		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'first_order_1', 'wccp_customer' )
			->andReturn( false );

		WP_Mock::userFunction( 'wc_get_order_statuses' )->andReturn( [] );
		WP_Mock::userFunction( 'wc_get_orders' )->andReturn( [] );

		WP_Mock::userFunction( 'wp_cache_set' )
			->once()
			->with( 'first_order_1', 0, 'wccp_customer', 300 );

		$result = $this->sut->get_first_order_date( 1 );

		$this->assertNull( $result );
	}

	// -------------------------------------------------------------------------
	// get_avg_order_interval
	// -------------------------------------------------------------------------

	public function test_get_avg_order_interval_returns_cached_value(): void {
		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'avg_interval_1', 'wccp_customer' )
			->andReturn( 30 );

		$result = $this->sut->get_avg_order_interval( 1 );

		$this->assertSame( 30, $result );
	}

	public function test_get_avg_order_interval_returns_null_from_negative_sentinel(): void {
		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'avg_interval_1', 'wccp_customer' )
			->andReturn( -1 );

		$result = $this->sut->get_avg_order_interval( 1 );

		$this->assertNull( $result );
	}

	public function test_get_avg_order_interval_returns_null_when_fewer_than_two_orders(): void {
		$mock_order = Mockery::mock( \WC_Order::class );

		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'avg_interval_1', 'wccp_customer' )
			->andReturn( false );

		WP_Mock::userFunction( 'wc_get_order_statuses' )->andReturn( [] );
		WP_Mock::userFunction( 'wc_get_orders' )->andReturn( [ $mock_order ] );

		WP_Mock::userFunction( 'wp_cache_set' )
			->once()
			->with( 'avg_interval_1', -1, 'wccp_customer', 300 );

		$result = $this->sut->get_avg_order_interval( 1 );

		$this->assertNull( $result );
	}

	public function test_get_avg_order_interval_averages_multiple_intervals(): void {
		$timestamps = [ 0, 10 * DAY_IN_SECONDS, 40 * DAY_IN_SECONDS ]; // intervals: 10, 30 → avg 20

		$orders = array_map(
			function ( int $ts ) {
				$date = Mockery::mock( \WC_DateTime::class );
				$date->shouldReceive( 'getTimestamp' )->andReturn( $ts );
				$order = Mockery::mock( \WC_Order::class );
				$order->shouldReceive( 'get_date_created' )->andReturn( $date );
				return $order;
			},
			$timestamps
		);

		WP_Mock::userFunction( 'wp_cache_get' )
			->with( 'avg_interval_1', 'wccp_customer' )
			->andReturn( false );

		WP_Mock::userFunction( 'wc_get_order_statuses' )->andReturn( [] );
		WP_Mock::userFunction( 'wc_get_orders' )->andReturn( $orders );

		WP_Mock::userFunction( 'wp_cache_set' )
			->once()
			->with( 'avg_interval_1', 20, 'wccp_customer', 300 );

		$result = $this->sut->get_avg_order_interval( 1 );

		$this->assertSame( 20, $result );
	}

	// -------------------------------------------------------------------------
	// get_orders_admin_url
	// -------------------------------------------------------------------------

	public function test_get_orders_admin_url_uses_legacy_url_when_hpos_disabled(): void {
		WP_Mock::userFunction( 'admin_url' )
			->with( 'edit.php?post_type=shop_order' )
			->andReturn( 'https://example.com/wp-admin/edit.php?post_type=shop_order' );

		WP_Mock::userFunction( 'add_query_arg' )
			->with( '_customer_user', 7, 'https://example.com/wp-admin/edit.php?post_type=shop_order' )
			->andReturn( 'https://example.com/wp-admin/edit.php?post_type=shop_order&_customer_user=7' );

		$result = ( new CustomerData() )->get_orders_admin_url( 7 );

		$this->assertStringContainsString( '_customer_user=7', $result );
	}
}
