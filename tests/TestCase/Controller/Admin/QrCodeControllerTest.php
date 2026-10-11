<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Controller\Admin;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use chillerlan\QRCode\QRCode;
use DOMDocument;

/**
 * @uses \QrCode\Controller\Admin\QrCodeController
 */
class QrCodeControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @uses \QrCode\Controller\Admin\QrCodeController::index()
	 *
	 * @return void
	 */
	public function testIndex(): void {
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'index']);

		$this->assertResponseOk();
		$this->assertContentType('text/html');
	}

	/**
	 * @return void
	 */
	public function testIndexPostRendersZero(): void {
		$this->post(
			['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'index'],
			['content' => '0'],
		);

		$this->assertResponseOk();
		$this->assertResponseContains('data:image/svg+xml;base64,');
	}

	/**
	 * @uses \QrCode\Controller\Admin\QrCodeController::image()
	 *
	 * @return void
	 */
	public function testImage(): void {
		$this->disableErrorHandlerMiddleware();

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg', '?' => ['content' => 'Foo Bar']]);

		$this->assertResponseOk();
		$this->assertContentType('image/svg+xml');
		$document = new DOMDocument();
		$this->assertTrue($document->loadXML((string)$this->_response->getBody()));
		$this->assertSame('svg', $document->documentElement->localName);

	}

	/**
	 * @return void
	 */
	public function testImagePngReturnsDecodableImage(): void {
		$this->skipIf(!extension_loaded('gd'), 'GD is required for PNG output');
		$this->get(['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'png', '?' => ['content' => 'Foo Bar']]);

		$this->assertResponseOk();
		$this->assertContentType('image/png');
		$decoded = (new QRCode())->readFromBlob((string)$this->_response->getBody());
		$this->assertSame('Foo Bar', (string)$decoded);
	}

	/**
	 * Posting overlong content must be rejected before the helper renders the QR.
	 *
	 * @return void
	 */
	public function testIndexPostRejectsOverlongContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(
			['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'index'],
			['content' => str_repeat('a', 2954)],
		);
	}

	/**
	 * @return void
	 */
	public function testIndexPostRejectsArrayContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->post(
			['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'index'],
			['content' => ['x', 'y']],
		);
	}

	/**
	 * @return void
	 */
	public function testImageRejectsArrayContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get(['prefix' => 'Admin', 'plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg', '?' => ['content' => ['x']]]);
	}

}
