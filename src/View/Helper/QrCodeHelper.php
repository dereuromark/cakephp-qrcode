<?php

namespace QrCode\View\Helper;

use Cake\View\Helper;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use InvalidArgumentException;
use QrCode\Utility\Config;
use QrCode\Utility\Formatter;
use QrCode\Utility\FormatterInterface;

/**
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
		$options = $this->normalizeOptions($options);

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
		$options = $this->normalizeOptions($options);

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
		$options = $this->normalizeOptions($options);

		$options['outputBase64'] = false;
		$options['outputType'] = QROutputInterface::IMAGICK;
		$options['returnResource'] = true;

		return (new QRCode(new QROptions($options)))->render($content);
	}

	/**
	 * @param array<string, mixed> $options
	 *
	 * @return array<string, mixed>
	 */
	protected function normalizeOptions(array $options): array {
		$options += [
			'version' => Version::AUTO, // to avoid code length issues
			'scale' => 3,
			'margin' => 0,
			'imageBase64' => true,
			'transparent' => false,
			'level' => 'L',
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

}
