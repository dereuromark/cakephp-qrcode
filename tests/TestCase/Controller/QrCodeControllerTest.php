<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * QrCode\Controller\QrCodeController Test Case
 *
 * @uses \QrCode\Controller\QrCodeController
 */
class QrCodeControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @uses \QrCode\Controller\QrCodeController::image()
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

		$this->get(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg', '?' => ['content' => 'Foo Bar']]);

		$this->assertResponseOk();
	}

}
