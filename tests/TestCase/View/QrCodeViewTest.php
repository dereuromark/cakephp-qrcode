<?php

declare(strict_types=1);

namespace QrCode\Test\TestCase\View;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use QrCode\View\QrCodeView;

/**
 * @uses \QrCode\View\QrCodeView
 */
class QrCodeViewTest extends TestCase {

	/**
	 * Test contentType returns correct MIME type
	 *
	 * @return void
	 */
	public function testContentType(): void {
		$this->assertSame('image/svg+xml', QrCodeView::contentType());
	}

	/**
	 * Test constructor disables auto layout
	 *
	 * @return void
	 */
	public function testConstructorDisablesLayout(): void {
		$request = new ServerRequest(['params' => ['_ext' => 'svg']]);
		$response = new Response();
		$view = new QrCodeView($request, $response);

		$this->assertSame('', $view->getLayout());
	}

	/**
	 * Test initialize sets content type for SVG
	 *
	 * @return void
	 */
	public function testInitializeSetsSvgContentType(): void {
		$request = new ServerRequest(['params' => ['_ext' => 'svg']]);
		$response = new Response();
		$view = new QrCodeView($request, $response);

		$response = $view->getResponse();
		$this->assertStringContainsString('image/svg+xml', $response->getHeaderLine('Content-Type'));
	}

	/**
	 * Test initialize sets content type for PNG
	 *
	 * @return void
	 */
	public function testInitializeSetsPngContentType(): void {
		$request = new ServerRequest(['params' => ['_ext' => 'png']]);
		$response = new Response();
		$view = new QrCodeView($request, $response);

		$response = $view->getResponse();
		$this->assertStringContainsString('image/png', $response->getHeaderLine('Content-Type'));
	}

	/**
	 * Test initialize with default extension (svg)
	 *
	 * @return void
	 */
	public function testInitializeWithDefaultExtension(): void {
		$request = new ServerRequest();
		$response = new Response();
		$view = new QrCodeView($request, $response);

		$response = $view->getResponse();
		$this->assertStringContainsString('image/svg+xml', $response->getHeaderLine('Content-Type'));
	}

	/**
	 * Test constructor without parameters
	 *
	 * @return void
	 */
	public function testConstructorWithoutParams(): void {
		$view = new QrCodeView();
		$this->assertSame('', $view->getLayout());
	}

	/**
	 * Test default config
	 *
	 * @return void
	 */
	public function testDefaultConfig(): void {
		$view = new QrCodeView();
		$this->assertSame('svg', $view->getConfig('ext'));
	}

}
