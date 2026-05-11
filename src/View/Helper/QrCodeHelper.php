<?php

namespace QrCode\View\Helper;

use Cake\View\Helper;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;
use QrCode\Lib\OutputType;
use QrCode\Utility\Config;
use QrCode\Utility\Formatter;
use QrCode\Utility\FormatterInterface;

/**
 * QrCodeHelper - Generates QR code images in templates.
 *
 * Provides methods to generate QR codes as inline base64 images, SVG, or PNG.
 * Uses chillerlan/php-qrcode library for generation.
 *
 * Usage:
 * ```php
 * // Inline base64 image
 * echo $this->QrCode->image('https://example.com');
 *
 * // SVG from controller
 * echo $this->QrCode->svg('https://example.com');
 *
 * // PNG from controller
 * echo $this->QrCode->png('https://example.com');
 * ```
 *
 * @author Mark Scherer
 * @license MIT
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class QrCodeHelper extends Helper {

	/**
	 * @var array
	 */
	protected array $helpers = [
		'Url',
	];

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'colorMap' => [],
		'formatter' => Formatter::class,
	];

	/**
	 * @return \QrCode\Utility\FormatterInterface
	 */
	public function formatter(): FormatterInterface {
		$className = $this->getConfig('formatter');

		if (!is_string($className) || !is_subclass_of($className, FormatterInterface::class)) {
			throw new InvalidArgumentException(
				sprintf('Formatter class must implement FormatterInterface, got %s', is_string($className) ? $className : gettype($className)),
			);
		}

		return new $className();
	}

	/**
	 * Base64 encoded image directly returned.
	 *
	 * @param string $content
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function image(string $content, array $options = []): string {
		$options = $this->normalizeOptions($options, $content);

		$qrcode = (new QRCode(new QROptions($options)))->render($content);

		return sprintf('<img src="%s" alt="QR Code">', h($qrcode));
	}

	/**
	 * SVG image from controller rendering.
	 *
	 * Make sure the action is allowed/accessible.
	 *
	 * @param string $content
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function svg(string $content, array $options = []): string {
		$url = $this->Url->build(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => Config::TYPE_SVG, '?' => ['content' => $content] + $options]);

		return sprintf('<img src="%s" alt="QR Code">', h($url));
	}

	/**
	 * PNG image from controller rendering.
	 *
	 * Make sure the action is allowed/accessible.
	 *
	 * @param string $content
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function png(string $content, array $options = []): string {
		$url = $this->Url->build(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '_ext' => Config::TYPE_PNG, '?' => ['content' => $content] + $options]);

		return sprintf('<img src="%s" alt="QR Code">', h($url));
	}

	/**
	 * Raw image display.
	 *
	 * @internal
	 *
	 * @param string $content
	 * @param array<string, mixed> $options
	 *
	 * @return string
	 */
	public function raw(string $content, array $options = []): string {
		$options = $this->normalizeOptions($options, $content);

		$options['outputBase64'] = false;

		return (new QRCode(new QROptions($options)))->render($content);
	}

	/**
	 * Get Imagick resource for further customization.
	 *
	 * @internal
	 *
	 * @param string $content
	 * @param array<string, mixed> $options
	 *
	 * @return object
	 */
	public function resource(string $content, array $options = []): object {
		$options = $this->normalizeOptions($options, $content);

		$options['outputBase64'] = false;
		$options = OutputType::apply($options, OutputType::IMAGICK);
		$options['returnResource'] = true;

		return (new QRCode(new QROptions($options)))->render($content);
	}

	/**
	 * @param array<string, mixed> $options
	 * @param string|null $content Optional payload — used to pick a payload-aware
	 *     default ECC level. WiFi credentials, MECARD, and vCard payloads are
	 *     routinely printed at small sizes or partially obscured by logos, so
	 *     they default to level Q (~25% recovery) instead of the generic
	 *     short-URL default L (~7%). Callers can always override by passing
	 *     a `level` option, or set `QrCode.defaultLevel` in Configure.
	 *
	 * @return array<string, mixed>
	 */
	protected function normalizeOptions(array $options, ?string $content = null): array {
		$defaultLevel = $this->getConfig('defaultLevel');
		if ($defaultLevel === null) {
			$defaultLevel = static::defaultLevelForPayload($content);
		}
		$options += [
			'version' => Version::AUTO, // to avoid code length issues
			'scale' => 3,
			'margin' => 0,
			'imageBase64' => true,
			'transparent' => false,
			'level' => $defaultLevel,
			'connectPaths' => true,
		];

		switch ($options['level']) {
			case 'M':
				$options['eccLevel'] = EccLevel::M;

				break;
			case 'Q':
				$options['eccLevel'] = EccLevel::Q;

				break;
			case 'H':
				$options['eccLevel'] = EccLevel::H;

				break;
			default:
				$options['eccLevel'] = EccLevel::L;
		}

		$options = [
			'imageTransparent' => $options['transparent'],
			'addQuietzone' => $options['margin'] > 0,
		] + $options;

		return $options;
	}

	/**
	 * Pick a sensible default ECC level for a given payload. Short URLs and
	 * plain text default to L (~7% recovery) for the densest possible code;
	 * structured payloads commonly printed small or partially obscured by a
	 * logo overlay (WiFi credentials, vCard/MECARD contact cards) default to
	 * Q (~25% recovery) so a scanner can still read them through wear or a
	 * centred logo. The payload prefix is the WiFi/MECARD/vCard standard
	 * marker — anything else stays at L.
	 *
	 * @param string|null $content
     *
	 * @return string One of `L`, `M`, `Q`, `H`.
	 */
	public static function defaultLevelForPayload(?string $content): string {
		if ($content === null) {
			return 'L';
		}
		$prefix = strtoupper(substr($content, 0, 16));
		if (
			str_starts_with($prefix, 'WIFI:')
			|| str_starts_with($prefix, 'MECARD:')
			|| str_starts_with($prefix, 'BEGIN:VCARD')
		) {
			return 'Q';
		}

		return 'L';
	}

}
