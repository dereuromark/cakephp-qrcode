<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Controller\Admin;

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

}
