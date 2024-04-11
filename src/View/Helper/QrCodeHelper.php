<?php

namespace QrCode\View\Helper;

use Cake\View\Helper;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class QrCodeHelper extends Helper {

	/**
	 * @var int
	 */
	public const HTML_TYPE_FA6 = 1;

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
	];

	/**
	 * Base64 encoded image directly returned.
	 *
	 * @param string $content
	 * @param array $options
	 *
	 * @return string
	 */
	public function image(string $content, array $options = []): string {
		$options += [
			'version' => Version::AUTO, // to avoid code length issues
			'scale' => 3,
			'margin' => 0,
			'imageBase64' => true,
			'transparent' => false,
			'eccLevel' => EccLevel::L,
			//'outputType' => QROutputInterface::MARKUP_SVG,
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

		$qrcode = (new QRCode(new QROptions($options)))->render($content);

		return sprintf('<img src="%s" alt="QR Code">', $qrcode);
	}

	/**
	 * SVG image from controller rendering.
	 *
	 * Make sure the action is allowed/accessible.
	 *
	 * @param string $content
	 * @param array $options
	 *
	 * @return string
	 */
	public function svg(string $content, array $options = []): string {
		$url = $this->Url->build(['plugin' => 'QrCode', 'controller' => 'QrCode', 'action' => 'image', '?' => ['content' => $content] + $options]);

		return sprintf('<img src="%s" alt="QR Code">', $url);
	}

}
