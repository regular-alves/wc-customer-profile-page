<?php
/**
 * @package WCCustomerProfilePage
 */

namespace WCCustomerProfilePage\Core;

defined( 'ABSPATH' ) || exit;

use WCCustomerProfilePage\Notes\Repository;

class Installer {

	public static function activate(): void {
		( new Repository() )->create_table();
	}
}
