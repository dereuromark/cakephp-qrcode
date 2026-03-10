<?php

declare(strict_types=1);

namespace QrCode\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use QrCode\Utility\Config;

/**
 * @uses \QrCode\Utility\Config
 */
class ConfigTest extends TestCase {

	/**
	 * Test TYPE_DEFAULT constant
	 *
	 * @return void
	 */
	public function testTypeDefault(): void {
		$this->assertSame('', Config::TYPE_DEFAULT);
	}

	/**
	 * Test TYPE_SVG constant
	 *
	 * @return void
	 */
	public function testTypeSvg(): void {
		$this->assertSame('svg', Config::TYPE_SVG);
	}

	/**
	 * Test TYPE_PNG constant
	 *
	 * @return void
	 */
	public function testTypePng(): void {
		$this->assertSame('png', Config::TYPE_PNG);
	}

}
