<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use QrCode\Utility\Formatter;

class FormatterTest extends TestCase {

	/**
	 * @var \QrCode\Utility\Formatter
	 */
	protected $formatter;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$view = new View();
		$this->formatter = new Formatter($view);

		$this->loadRoutes();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->formatter);

		parent::tearDown();
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testWifi(): void {
		$network = 'FooBar';
		$result = $this->formatter->formatWifi($network, 'pwd');
		$this->assertSame('WIFI:T:WPA;S:FooBar;P:pwd', $result);
	}

}
