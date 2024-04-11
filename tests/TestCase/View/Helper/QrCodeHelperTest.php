<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use QrCode\View\Helper\QrCodeHelper;

class QrCodeHelperTest extends TestCase {

	/**
	 * @var \QrCode\View\Helper\QrCodeHelper
	 */
	protected $QrCode;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$view = new View();
		$this->QrCode = new QrCodeHelper($view);

		$this->loadRoutes();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->QrCode);

		parent::tearDown();
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImage(): void {
		$content = 'Foo Bar';
		$image = $this->QrCode->image($content);
		$this->assertNotEmpty($image);
		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $image);
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::svg()
	 *
	 * @return void
	 */
	public function testSvg(): void {
		$content = 'Foo Bar';
		$image = $this->QrCode->svg($content);
		$this->assertNotEmpty($image);
		$expected = '<img src="/qr-code/QrCode/image?content=Foo+Bar" alt="QR Code">';
		$this->assertSame($expected, $image);
	}

}
