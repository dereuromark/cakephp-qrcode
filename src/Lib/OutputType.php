<?php
declare(strict_types=1);

namespace QrCode\Lib;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRImagick;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\Output\QROutputInterface;
use InvalidArgumentException;

/**
 * Compatibility shim for chillerlan/php-qrcode v5 and v6.
 *
 * v5 selected output via the `outputType` string option (e.g. 'png', 'imagick').
 * v6 removed `outputType` entirely and selects output via the `outputInterface`
 * class-name option (e.g. QRGdImagePNG::class).
 *
 * Why both: the v5 default for `outputInterface` is null and is only honored
 * when `outputType === QROutputInterface::CUSTOM`; v6 honors `outputInterface`
 * directly. Setting both keeps the same call site working across majors.
 */
class OutputType {

	/**
	 * @var string
	 */
	public const PNG = 'png';

	/**
	 * @var string
	 */
	public const SVG = 'svg';

	/**
	 * @var string
	 */
	public const IMAGICK = 'imagick';

	/**
	 * @var array<string, class-string>
	 */
	protected const CLASS_MAP = [
		self::PNG => QRGdImagePNG::class,
		self::SVG => QRMarkupSVG::class,
		self::IMAGICK => QRImagick::class,
	];

	/**
	 * @return bool True when chillerlan/php-qrcode v6+ is installed.
	 */
	public static function isV6(): bool {
		return !defined(QROutputInterface::class . '::GDIMAGE_PNG');
	}

	/**
	 * @param array<string, mixed> $options
	 * @param string $type One of self::PNG, self::SVG, self::IMAGICK.
	 *
	 * @throws \InvalidArgumentException When $type is not a known output type.
	 *
	 * @return array<string, mixed>
	 */
	public static function apply(array $options, string $type): array {
		if (!isset(static::CLASS_MAP[$type])) {
			throw new InvalidArgumentException(sprintf('Unknown output type: %s', $type));
		}

		$options['outputInterface'] = static::CLASS_MAP[$type];

		if (!static::isV6()) {
			$options['outputType'] = constant(QROutputInterface::class . '::CUSTOM');
		}

		return $options;
	}

}
