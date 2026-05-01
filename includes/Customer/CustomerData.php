<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Customer;

defined( 'ABSPATH' ) || exit;

class CustomerData {

	private const CACHE_GROUP = 'wccp_customer';

	public function get_customer( int $user_id ): ?\WC_Customer {
		try {
			$customer = $this->make_customer( $user_id );

			return $customer->get_id() ? $customer : null;
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * @return array{total_spent: float, order_count: int, avg_order: float}
	 */
	public function get_kpis( int $user_id ): array {
		$cache_key = 'kpis_' . $user_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return (array) $cached;
		}

		$total_spent = (float) wc_get_customer_total_spent( $user_id );
		$order_count = (int) wc_get_customer_order_count( $user_id );
		$avg_order   = $order_count > 0 ? $total_spent / $order_count : 0.0;

		$kpis = [
			'total_spent' => $total_spent,
			'order_count' => $order_count,
			'avg_order'   => $avg_order,
		];

		wp_cache_set( $cache_key, $kpis, self::CACHE_GROUP, 300 );

		return $kpis;
	}

	/**
	 * @return \WC_Order[]
	 */
	public function get_recent_orders( int $user_id, int $limit = 3 ): array {
		$cache_key = 'orders_' . $user_id . '_' . $limit;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return (array) $cached;
		}

		$orders = wc_get_orders(
			[
				'customer_id' => $user_id,
				'limit'       => $limit,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'status'      => array_keys( wc_get_order_statuses() ),
			]
		);

		wp_cache_set( $cache_key, $orders, self::CACHE_GROUP, 300 );

		return $orders;
	}

	public function get_orders_admin_url( int $user_id ): string {
		$base = class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
			? admin_url( 'admin.php?page=wc-orders' )
			: admin_url( 'edit.php?post_type=shop_order' );

		return add_query_arg( '_customer_user', $user_id, $base );
	}

	public function get_first_order_date( int $user_id ): ?\WC_DateTime {
		$cache_key = 'first_order_' . $user_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return ( $cached instanceof \WC_DateTime ) ? $cached : null;
		}

		$orders = wc_get_orders(
			[
				'customer_id' => $user_id,
				'limit'       => 1,
				'orderby'     => 'date',
				'order'       => 'ASC',
				'status'      => array_keys( wc_get_order_statuses() ),
			]
		);

		$date = ! empty( $orders ) ? $orders[0]->get_date_created() : null;

		wp_cache_set( $cache_key, $date ?? 0, self::CACHE_GROUP, 300 );

		return $date;
	}

	public function get_avg_order_interval( int $user_id ): ?int {
		$cache_key = 'avg_interval_' . $user_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached >= 0 ? (int) $cached : null;
		}

		$orders = wc_get_orders(
			[
				'customer_id' => $user_id,
				'limit'       => -1,
				'orderby'     => 'date',
				'order'       => 'ASC',
				'status'      => array_keys( wc_get_order_statuses() ),
			]
		);

		if ( count( $orders ) < 2 ) {
			wp_cache_set( $cache_key, -1, self::CACHE_GROUP, 300 );
			return null;
		}

		$timestamps = [];
		foreach ( $orders as $order ) {
			$date = $order->get_date_created();
			if ( $date instanceof \WC_DateTime ) {
				$timestamps[] = $date->getTimestamp();
			}
		}

		if ( count( $timestamps ) < 2 ) {
			wp_cache_set( $cache_key, -1, self::CACHE_GROUP, 300 );
			return null;
		}

		$total_seconds = 0;
		$count         = count( $timestamps ) - 1;
		for ( $i = 1; $i <= $count; $i++ ) {
			$total_seconds += $timestamps[ $i ] - $timestamps[ $i - 1 ];
		}

		$avg_days = (int) round( $total_seconds / $count / DAY_IN_SECONDS );
		wp_cache_set( $cache_key, $avg_days, self::CACHE_GROUP, 300 );

		return $avg_days;
	}

	protected function make_customer( int $user_id ): \WC_Customer {
		return new \WC_Customer( $user_id );
	}
}
