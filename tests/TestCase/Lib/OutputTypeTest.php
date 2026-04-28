<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Imagick;
use InvalidArgumentException;
use QrCode\Lib\OutputType;

/**
 * @uses \QrCode\Lib\OutputType
 */
class OutputTypeTest extends TestCase {

	/**
	 * @return void
	 */
	public function testApplyPngSetsOutputInterface(): void {
		$options = OutputType::apply([], OutputType::PNG);

		$this->assertArrayHasKey('outputInterface', $options);
		$this->assertSame(QRGdImagePNG::class, $options['outputInterface']);
	}

	/**
	 * @return void
	 */
	public function testApplyImagickSetsOutputInterface(): void {
		$options = OutputType::apply([], OutputType::IMAGICK);

		$this->assertArrayHasKey('outputInterface', $options);
		$this->assertSame(QRImagick::class, $options['outputInterface']);
	}

	/**
	 * @return void
	 */
	public function testApplySvgSetsOutputInterface(): void {
		$options = OutputType::apply([], OutputType::SVG);

		$this->assertArrayHasKey('outputInterface', $options);
		$this->assertSame(QRMarkupSVG::class, $options['outputInterface']);
	}

	/**
	 * @return void
	 */
	public function testApplyPreservesOtherOptions(): void {
		$options = OutputType::apply(['scale' => 5, 'margin' => 2], OutputType::PNG);

		$this->assertSame(5, $options['scale']);
		$this->assertSame(2, $options['margin']);
	}

	/**
	 * v5 needs `outputType => CUSTOM` to actually use the `outputInterface` override.
	 * v6 removed `outputType` entirely.
	 *
	 * @return void
	 */
	public function testApplySetsOutputTypeCustomOnV5(): void {
		if (OutputType::isV6()) {
			$this->markTestSkipped('Only relevant on chillerlan/php-qrcode v5.');
		}

		$options = OutputType::apply([], OutputType::PNG);

		$this->assertArrayHasKey('outputType', $options);
		$this->assertSame('custom', $options['outputType']);
	}

	/**
	 * @return void
	 */
	public function testApplyOmitsOutputTypeOnV6(): void {
		if (!OutputType::isV6()) {
			$this->markTestSkipped('Only relevant on chillerlan/php-qrcode v6.');
		}

		$options = OutputType::apply([], OutputType::PNG);

		$this->assertArrayNotHasKey('outputType', $options);
	}

	/**
	 * @return void
	 */
	public function testApplyThrowsForUnknownType(): void {
		$this->expectException(InvalidArgumentException::class);

		OutputType::apply([], 'bogus');
	}

	/**
	 * @return void
	 */
	public function testIsV6DetectsByConstantPresence(): void {
		$expected = !defined(QROutputInterface::class . '::GDIMAGE_PNG');

		$this->assertSame($expected, OutputType::isV6());
	}

	/**
	 * End-to-end: shim-prepared options actually produce a PNG when passed to QRCode.
	 *
	 * @return void
	 */
	public function testEndToEndProducesPng(): void {
		$options = OutputType::apply(['outputBase64' => false], OutputType::PNG);
		$result = (new QRCode(new QROptions($options)))->render('Foo Bar');

		$this->assertIsString($result);
		$this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $result);
	}

	/**
	 * End-to-end: shim-prepared options produce SVG markup.
	 *
	 * @return void
	 */
	public function testEndToEndProducesSvg(): void {
		$options = OutputType::apply(['outputBase64' => false], OutputType::SVG);
		$result = (new QRCode(new QROptions($options)))->render('Foo Bar');

		$this->assertIsString($result);
		$this->assertStringContainsString('<svg', $result);
	}

	/**
	 * End-to-end: shim-prepared options produce an Imagick instance.
	 *
	 * @return void
	 */
	public function testEndToEndProducesImagickResource(): void {
		$this->skipIf(!class_exists(Imagick::class), 'Imagick not available');

		$options = OutputType::apply(['outputBase64' => false, 'returnResource' => true], OutputType::IMAGICK);
		$result = (new QRCode(new QROptions($options)))->render('Foo Bar');

		$this->assertInstanceOf(Imagick::class, $result);
	}

}
