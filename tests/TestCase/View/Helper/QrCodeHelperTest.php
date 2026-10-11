<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use DOMDocument;
use Imagick;
use QrCode\Lib\OutputType;
use QrCode\Utility\FormatterInterface;
use QrCode\View\Helper\QrCodeHelper;
use ReflectionMethod;
use TestApp\View\Helper\LegacyNormalizerHelper;

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
	 * @return void
	 */
	public function testImage(): void {
		$content = 'Foo Bar';
		$image = $this->QrCode->image($content);
		$this->assertNotEmpty($image);
		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $image);
	}

	/**
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
	 * @return void
	 */
	public function testRaw(): void {
		$content = 'Test Content';
		$raw = $this->QrCode->raw($content);
		$this->assertNotEmpty($raw);
		$this->assertStringContainsString('<svg', $raw);
	}

	/**
	 * @return void
	 */
	public function testFormatter(): void {
		$formatter = $this->QrCode->formatter();
		$this->assertInstanceOf(FormatterInterface::class, $formatter);
	}

	/**
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
	 * @return void
	 */
	public function testPngWithOptions(): void {
		$content = 'Test';
		$image = $this->QrCode->png($content, ['scale' => 5]);
		$this->assertStringContainsString('scale=5', $image);
	}

	/**
	 * @return void
	 */
	public function testImageWithLogoEmbedsImageElement(): void {
		$logo = 'data:image/png;base64,iVBORw0KGgo=';
		$image = $this->QrCode->image('https://example.com', ['logo' => $logo]);
		preg_match('/src="data:image\/svg\+xml;base64,([^"]+)"/', $image, $matches);

		$this->assertNotEmpty($matches);
		$svg = base64_decode($matches[1], true);
		$this->assertIsString($svg);
		$this->assertStringContainsString('<image', $svg);
		$this->assertStringContainsString($logo, $svg);
		$document = new DOMDocument();
		$this->assertTrue($document->loadXML($svg));
		$imageNode = $document->getElementsByTagName('image')->item(0);
		$this->assertNotNull($imageNode);
		$this->assertSame($logo, $imageNode->getAttributeNS('http://www.w3.org/1999/xlink', 'href'));
	}

	/**
	 * @return void
	 */
	public function testPngImageWithUnsupportedLogoRemainsAnImage(): void {
		$this->skipIf(!extension_loaded('gd'), 'GD not available');
		$options = OutputType::apply(['logo' => 'data:image/png;base64,iVBORw0KGgo='], OutputType::PNG);
		$image = $this->QrCode->image('https://example.com', $options);
		preg_match('/src="data:image\/png;base64,([^"]+)"/', $image, $matches);

		$this->assertNotEmpty($matches);
		$png = base64_decode($matches[1], true);
		$this->assertIsString($png);
		$this->assertStringStartsWith("\x89PNG", $png);
	}

	/**
	 * @return void
	 */
	public function testImageWrapsRawSvgInDataUri(): void {
		$image = $this->QrCode->image('https://example.com', ['outputBase64' => false]);

		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $image);
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
		$ref = new ReflectionMethod($this->QrCode, 'normalizeRenderOptions');
		/** @var array<string, mixed> $withLogo */
		$withLogo = $ref->invoke($this->QrCode, [], true);
		/** @var array<string, mixed> $withoutLogo */
		$withoutLogo = $ref->invoke($this->QrCode, [], false);

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

		$ref = new ReflectionMethod($this->QrCode, 'normalizeRenderOptions');
		/** @var array<string, mixed> $withLogo */
		$withLogo = $ref->invoke($this->QrCode, ['level' => 'M'], true);

		$this->assertSame('M', $withLogo['level'], 'Caller-supplied level must win over the logo auto-bump');
	}

	public function testLongContactPreservesReleasedDefaultCapacity(): void {
		$content = 'MECARD:N:Contact;NOTE:' . str_repeat('x', 2000) . ';;';
		$image = $this->QrCode->image($content);
		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $image);
	}

	public function testStructuredPayloadUsesReleasedDefaultLevel(): void {
		$helper = new LegacyNormalizerHelper(new View());
		$payload = 'MECARD:N:Contact;;';
		$helper->image($payload);
		$helper->setConfig('defaultLevel', 'M');
		$helper->image($payload);
		$helper->image($payload, ['level' => 'Q']);
		$this->assertSame(['L', 'M', 'Q'], $helper->observedLevels);
	}

}
