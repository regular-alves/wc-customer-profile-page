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

	public static function get_phone_country_prefix( string $country_code ): string {
		$map = [
			'AC' => '247',  'AD' => '376',  'AE' => '971',  'AF' => '93',
			'AG' => '1268', 'AI' => '1264', 'AL' => '355',  'AM' => '374',
			'AO' => '244',  'AQ' => '672',  'AR' => '54',   'AS' => '1684',
			'AT' => '43',   'AU' => '61',   'AW' => '297',  'AX' => '358',
			'AZ' => '994',  'BA' => '387',  'BB' => '1246', 'BD' => '880',
			'BE' => '32',   'BF' => '226',  'BG' => '359',  'BH' => '973',
			'BI' => '257',  'BJ' => '229',  'BL' => '590',  'BM' => '1441',
			'BN' => '673',  'BO' => '591',  'BQ' => '599',  'BR' => '55',
			'BS' => '1242', 'BT' => '975',  'BW' => '267',  'BY' => '375',
			'BZ' => '501',  'CA' => '1',    'CC' => '61',   'CD' => '243',
			'CF' => '236',  'CG' => '242',  'CH' => '41',   'CI' => '225',
			'CK' => '682',  'CL' => '56',   'CM' => '237',  'CN' => '86',
			'CO' => '57',   'CR' => '506',  'CU' => '53',   'CV' => '238',
			'CW' => '599',  'CX' => '61',   'CY' => '357',  'CZ' => '420',
			'DE' => '49',   'DJ' => '253',  'DK' => '45',   'DM' => '1767',
			'DO' => '1809', 'DZ' => '213',  'EC' => '593',  'EE' => '372',
			'EG' => '20',   'ER' => '291',  'ES' => '34',   'ET' => '251',
			'FI' => '358',  'FJ' => '679',  'FK' => '500',  'FM' => '691',
			'FO' => '298',  'FR' => '33',   'GA' => '241',  'GB' => '44',
			'GD' => '1473', 'GE' => '995',  'GF' => '594',  'GG' => '44',
			'GH' => '233',  'GI' => '350',  'GL' => '299',  'GM' => '220',
			'GN' => '224',  'GP' => '590',  'GQ' => '240',  'GR' => '30',
			'GT' => '502',  'GU' => '1671', 'GW' => '245',  'GY' => '592',
			'HK' => '852',  'HN' => '504',  'HR' => '385',  'HT' => '509',
			'HU' => '36',   'ID' => '62',   'IE' => '353',  'IL' => '972',
			'IM' => '44',   'IN' => '91',   'IO' => '246',  'IQ' => '964',
			'IR' => '98',   'IS' => '354',  'IT' => '39',   'JE' => '44',
			'JM' => '1876', 'JO' => '962',  'JP' => '81',   'KE' => '254',
			'KG' => '996',  'KH' => '855',  'KI' => '686',  'KM' => '269',
			'KN' => '1869', 'KP' => '850',  'KR' => '82',   'KW' => '965',
			'KY' => '1345', 'KZ' => '7',    'LA' => '856',  'LB' => '961',
			'LC' => '1758', 'LI' => '423',  'LK' => '94',   'LR' => '231',
			'LS' => '266',  'LT' => '370',  'LU' => '352',  'LV' => '371',
			'LY' => '218',  'MA' => '212',  'MC' => '377',  'MD' => '373',
			'ME' => '382',  'MF' => '590',  'MG' => '261',  'MH' => '692',
			'MK' => '389',  'ML' => '223',  'MM' => '95',   'MN' => '976',
			'MO' => '853',  'MP' => '1670', 'MQ' => '596',  'MR' => '222',
			'MS' => '1664', 'MT' => '356',  'MU' => '230',  'MV' => '960',
			'MW' => '265',  'MX' => '52',   'MY' => '60',   'MZ' => '258',
			'NA' => '264',  'NC' => '687',  'NE' => '227',  'NF' => '672',
			'NG' => '234',  'NI' => '505',  'NL' => '31',   'NO' => '47',
			'NP' => '977',  'NR' => '674',  'NU' => '683',  'NZ' => '64',
			'OM' => '968',  'PA' => '507',  'PE' => '51',   'PF' => '689',
			'PG' => '675',  'PH' => '63',   'PK' => '92',   'PL' => '48',
			'PM' => '508',  'PR' => '1787', 'PS' => '970',  'PT' => '351',
			'PW' => '680',  'PY' => '595',  'QA' => '974',  'RE' => '262',
			'RO' => '40',   'RS' => '381',  'RU' => '7',    'RW' => '250',
			'SA' => '966',  'SB' => '677',  'SC' => '248',  'SD' => '249',
			'SE' => '46',   'SG' => '65',   'SH' => '290',  'SI' => '386',
			'SJ' => '47',   'SK' => '421',  'SL' => '232',  'SM' => '378',
			'SN' => '221',  'SO' => '252',  'SR' => '597',  'SS' => '211',
			'ST' => '239',  'SV' => '503',  'SX' => '1721', 'SY' => '963',
			'SZ' => '268',  'TC' => '1649', 'TD' => '235',  'TG' => '228',
			'TH' => '66',   'TJ' => '992',  'TK' => '690',  'TL' => '670',
			'TM' => '993',  'TN' => '216',  'TO' => '676',  'TR' => '90',
			'TT' => '1868', 'TV' => '688',  'TW' => '886',  'TZ' => '255',
			'UA' => '380',  'UG' => '256',  'US' => '1',    'UY' => '598',
			'UZ' => '998',  'VA' => '379',  'VC' => '1784', 'VE' => '58',
			'VG' => '1284', 'VI' => '1340', 'VN' => '84',   'VU' => '678',
			'WF' => '681',  'WS' => '685',  'YE' => '967',  'YT' => '262',
			'ZA' => '27',   'ZM' => '260',  'ZW' => '263',
		];

		return $map[ strtoupper( $country_code ) ] ?? '';
	}

	protected function make_customer( int $user_id ): \WC_Customer {
		return new \WC_Customer( $user_id );
	}
}
