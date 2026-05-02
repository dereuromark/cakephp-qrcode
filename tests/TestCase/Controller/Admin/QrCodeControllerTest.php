<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Controller\Admin;

use Cake\Http\Exception\BadRequestException;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
