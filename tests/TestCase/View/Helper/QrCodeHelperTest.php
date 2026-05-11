<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Imagick;
use QrCode\Utility\FormatterInterface;
use QrCode\View\Helper\QrCodeHelper;
use ReflectionMethod;

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
		$expected = '<img src="/qr-code/qr-code/image.svg?content=Foo+Bar" alt="QR Code">';
		$this->assertSame($expected, $image);
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::svg()
	 *
	 * @return void
	 */
	public function testResource(): void {
		$this->skipIf(!class_exists(Imagick::class), 'Imagick not available');

		$content = 'Foo Bar';
		$image = $this->QrCode->resource($content);
		$this->assertInstanceOf(Imagick::class, $image);

		$this->assertNotEmpty($image->getImageBlob());
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::png()
	 *
	 * @return void
	 */
	public function testPng(): void {
		$content = 'Foo Bar';
		$image = $this->QrCode->png($content);
		$this->assertNotEmpty($image);
		$expected = '<img src="/qr-code/qr-code/image.png?content=Foo+Bar" alt="QR Code">';
		$this->assertSame($expected, $image);
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::raw()
	 *
	 * @return void
	 */
	public function testRaw(): void {
		$content = 'Test Content';
		$raw = $this->QrCode->raw($content);
		$this->assertNotEmpty($raw);
		$this->assertStringContainsString('<svg', $raw);
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::formatter()
	 *
	 * @return void
	 */
	public function testFormatter(): void {
		$formatter = $this->QrCode->formatter();
		$this->assertInstanceOf(FormatterInterface::class, $formatter);
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImageWithOptions(): void {
		$content = 'Test';
		$image = $this->QrCode->image($content, ['level' => 'M']);
		$this->assertNotEmpty($image);
		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $image);
	}

	/**
	 * Test image with ECC level Q
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImageWithLevelQ(): void {
		$content = 'Test';
		$image = $this->QrCode->image($content, ['level' => 'Q']);
		$this->assertNotEmpty($image);
	}

	/**
	 * Test image with ECC level H
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImageWithLevelH(): void {
		$content = 'Test';
		$image = $this->QrCode->image($content, ['level' => 'H']);
		$this->assertNotEmpty($image);
	}

	/**
	 * Test image with margin
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImageWithMargin(): void {
		$content = 'Test';
		$image = $this->QrCode->image($content, ['margin' => 10]);
		$this->assertNotEmpty($image);
	}

	/**
	 * Test image with transparent background
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testImageWithTransparent(): void {
		$content = 'Test';
		$image = $this->QrCode->image($content, ['transparent' => true]);
		$this->assertNotEmpty($image);
	}

	/**
	 * Test svg with query options
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::svg()
	 *
	 * @return void
	 */
	public function testSvgWithOptions(): void {
		$content = 'Test';
		$image = $this->QrCode->svg($content, ['level' => 'H']);
		$this->assertStringContainsString('level=H', $image);
	}

	/**
	 * Test png with query options
	 *
	 * @uses \QrCode\View\Helper\QrCodeHelper::png()
	 *
	 * @return void
	 */
	public function testPngWithOptions(): void {
		$content = 'Test';
		$image = $this->QrCode->png($content, ['scale' => 5]);
		$this->assertStringContainsString('scale=5', $image);
	}

	/**
	 * Logo overlay happy path: the rendered output contains an `<image>`
	 * element pointing at the supplied data URI, centred on the QR code.
	 *
	 * @return void
	 */
	public function testRawWithLogoEmbedsImageElement(): void {
		$content = 'https://example.com';
		$logo = 'data:image/png;base64,iVBORw0KGgo=';

		$raw = $this->QrCode->raw($content, ['logo' => $logo, 'logoSize' => 0.2]);

		$this->assertStringContainsString('<image', $raw);
		$this->assertStringContainsString('preserveAspectRatio="xMidYMid meet"', $raw);
		$this->assertStringContainsString($logo, $raw);
	}

	/**
	 * Setting a logo auto-bumps ECC to H so the centre overlay doesn't
	 * defeat the QR's error correction. Verified by comparing the raw
	 * output to a normalized form with no logo at level L — the
	 * with-logo version should have more dense module patterns
	 * characteristic of H-level encoding.
	 *
	 * @return void
	 */
	public function testLogoAutoBumpsEccToH(): void {
		$content = 'https://example.com';

		// Use reflection to inspect what level normalizeOptions applied
		// rather than comparing rendered byte counts (which include the
		// injected <image> element and varying whitespace).
		$ref = new ReflectionMethod($this->QrCode, 'normalizeOptions');
		/** @var array<string, mixed> $withLogo */
		$withLogo = $ref->invoke($this->QrCode, [], $content, true);
		/** @var array<string, mixed> $withoutLogo */
		$withoutLogo = $ref->invoke($this->QrCode, [], $content, false);

		$this->assertSame('H', $withLogo['level']);
		$this->assertNotSame('H', $withoutLogo['level']);
	}

	/**
	 * Caller-supplied `level` wins over the auto-bump — they may have a
	 * specific reason to insist on a lower level (e.g. readability test).
	 *
	 * @return void
	 */
	public function testLogoRespectsExplicitLevel(): void {
		$content = 'https://example.com';

		$ref = new ReflectionMethod($this->QrCode, 'normalizeOptions');
		/** @var array<string, mixed> $withLogo */
		$withLogo = $ref->invoke($this->QrCode, ['level' => 'M'], $content, true);

		$this->assertSame('M', $withLogo['level'], 'Caller-supplied level must win over the logo auto-bump');
	}

	/**
	 * @return void
	 */
	public function testDefaultLevelForPayload(): void {
		$this->assertSame('L', QrCodeHelper::defaultLevelForPayload('https://example.com'));
		$this->assertSame('L', QrCodeHelper::defaultLevelForPayload('plain text'));
		$this->assertSame('L', QrCodeHelper::defaultLevelForPayload(null));
		$this->assertSame('L', QrCodeHelper::defaultLevelForPayload(''));

		$this->assertSame('Q', QrCodeHelper::defaultLevelForPayload('WIFI:T:WPA;S:Home;P:pwd;;'));
		$this->assertSame('Q', QrCodeHelper::defaultLevelForPayload('MECARD:N:Doe,John;;'));
		$this->assertSame('Q', QrCodeHelper::defaultLevelForPayload("BEGIN:VCARD\nVERSION:3.0\nFN:Doe"));

		// Case-insensitive prefix match — readers don't care about case, neither do we.
		$this->assertSame('Q', QrCodeHelper::defaultLevelForPayload('wifi:T:WPA;S:Home;P:pwd;;'));
	}

}
