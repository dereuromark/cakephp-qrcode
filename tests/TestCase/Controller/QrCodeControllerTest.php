<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Controller;

use Cake\Http\Exception\BadRequestException;
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

	/**
	 * Array `content` would previously hit `strlen()` on mixed and throw an uncaught TypeError.
	 *
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

		$this->get(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg', '?' => ['content' => ['x', 'y']]]);
	}

	/**
	 * @return void
	 */
	public function testImageRejectsMissingContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg']);
	}

	/**
	 * @return void
	 */
	public function testImageRejectsOverlongContent(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => 'svg', '?' => ['content' => str_repeat('a', 2954)]]);
	}

	/**
	 * The capacity check is ECC-aware: at level H the byte-mode cap drops
	 * to ~1273 bytes, so content that fits at L must be rejected at H.
	 * Previously the controller checked against the flat L cap and the
	 * over-budget payload passed through, then failed deeper in the
	 * renderer with a less useful error.
	 *
	 * @return void
	 */
	public function testImageEnforcesEccAwareCapacity(): void {
		$this->disableErrorHandlerMiddleware();
		$this->expectException(BadRequestException::class);
		$this->expectExceptionMessage('level H');

		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		// 1500 bytes fits at L (cap 2953) but not at H (cap 1273).
		$this->get([
			'plugin' => 'QrCode',
			'controller' => 'QrCode',
			'action' => 'image',
			'_ext' => 'svg',
			'?' => ['content' => str_repeat('a', 1500), 'level' => 'H'],
		]);
	}

	/**
	 * Content that fits the L-level cap continues to render when the caller
	 * doesn't specify a level — preserves backward compatibility.
	 *
	 * @return void
	 */
	public function testImageStillAcceptsLevelLDefaultCap(): void {
		$this->session([
			'Auth' => [
				'User' => [
					'id' => 1,
				],
			],
		]);

		$this->get([
			'plugin' => 'QrCode',
			'controller' => 'QrCode',
			'action' => 'image',
			'_ext' => 'svg',
			'?' => ['content' => str_repeat('a', 1500)],
		]);

		$this->assertResponseOk();
	}

}
