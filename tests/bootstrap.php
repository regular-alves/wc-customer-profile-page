<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package WCCustomerProfilePage
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );

WP_Mock::bootstrap();
