<?php
/**
 * @package WCCustomerProfilePage\Tests\Unit
 */

namespace WCCustomerProfilePage\Tests\Unit;

use WCCustomerProfilePage\Core\Plugin;
use WP_Mock\Tools\TestCase;

class PluginTest extends TestCase {

	public function test_get_instance_returns_same_instance(): void {
		$a = Plugin::get_instance();
		$b = Plugin::get_instance();

		$this->assertSame( $a, $b );
	}

	public function test_declare_hpos_compatibility_skips_when_class_missing(): void {
		Plugin::get_instance()->declare_hpos_compatibility();

		$this->assertTrue( true );
	}
}
