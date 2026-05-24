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
		$logo = $options['logo'] ?? null;
		$logoSize = (float)($options['logoSize'] ?? 0.2);
		unset($options['logo'], $options['logoSize']);

		$options = $this->normalizeOptions($options, $content, hasLogo: $logo !== null);

		// When a logo overlays the centre of the QR code we need to render
		// to a non-base64 stream so we can post-process the SVG to inject
		// the logo element. Re-encode at the end so the returned data URI
		// is still drop-in for `<img src="...">`.
		$wantsBase64 = (bool)($options['imageBase64'] ?? true);
		if ($logo !== null) {
			$options['imageBase64'] = false;
		}

		$qrcode = (new QRCode(new QROptions($options)))->render($content);

		if ($logo !== null) {
			$qrcode = $this->overlayLogo($qrcode, $logo, $logoSize);
			if ($wantsBase64 && str_contains($qrcode, '<svg ')) {
				$qrcode = 'data:image/svg+xml;base64,' . base64_encode($qrcode);
			}
		}

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
		$logo = $options['logo'] ?? null;
		$logoSize = (float)($options['logoSize'] ?? 0.2);
		unset($options['logo'], $options['logoSize']);

		$options = $this->normalizeOptions($options, $content, hasLogo: $logo !== null);
		$options['outputBase64'] = false;

		$qrcode = (new QRCode(new QROptions($options)))->render($content);
		if ($logo !== null) {
			$qrcode = $this->overlayLogo($qrcode, $logo, $logoSize);
		}

		return $qrcode;
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
	 * @param bool $hasLogo When true, auto-bump default level to H so the
	 *     centre overlay doesn't defeat error correction. Explicit
	 *     caller-supplied `level` still wins.
	 *
	 * @return array<string, mixed>
	 */
	protected function normalizeOptions(array $options, ?string $content = null, bool $hasLogo = false): array {
		$callerSetLevel = isset($options['level']);
		$defaultLevel = $this->getConfig('defaultLevel');
		if ($defaultLevel === null) {
			$defaultLevel = static::defaultLevelForPayload($content);
		}
		// A centred logo overlays ~10-25% of the modules. Bump to H (~30%
		// recovery) so the QR remains scannable even with the centre
		// obscured. Honour an explicit caller-supplied `level` though —
		// they may have a reason to insist on lower recovery for
		// readability tests.
		if ($hasLogo && !$callerSetLevel) {
			$defaultLevel = 'H';
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
        $options['eccLevel'] = match ($options['level']) {
            'M' => EccLevel::M,
            'Q' => EccLevel::Q,
            'H' => EccLevel::H,
            default => EccLevel::L,
        };

		return [
			'imageTransparent' => $options['transparent'],
			'addQuietzone' => $options['margin'] > 0,
		] + $options;
	}

	/**
	 * Overlay a centred logo onto a rendered QR code.
	 *
	 * Currently supports SVG output only. PNG / Imagick overlay would need
	 * a GD / Imagick composite, which is a bigger surface and is deferred
	 * to a follow-up. Non-SVG renders pass through unchanged so callers
	 * can still pass the `logo` option without breaking the response.
	 *
	 * @param string $rendered Output of `(new QRCode($opts))->render()`.
	 * @param string $logo Logo source — accepts:
	 *     - absolute filesystem path
	 *     - path relative to WWW_ROOT
	 *     - `data:` URI (used as-is)
	 *     - any other URL (left as-is; rendered as an `xlink:href`)
	 * @param float $logoSize Fraction of the QR width the logo should cover.
	 *     Default 0.2 (20%). Clamped to [0.05, 0.35] — beyond ~35% even
	 *     ECC-H can't recover the centre and scanners start failing.
	 *
	 * @return string
	 */
	protected function overlayLogo(string $rendered, string $logo, float $logoSize): string {
		// SVG-only for now. The detection is shape-based rather than
		// content-type-based because the rendered SVG might not start
		// with the XML decl (chillerlan omits it for inline use).
		if (!str_contains($rendered, '<svg ')) {
			return $rendered;
		}

		$logoSize = max(0.05, min(0.35, $logoSize));

		[$width, $height] = $this->detectSvgDimensions($rendered);
		$size = min($width, $height) * $logoSize;
		$x = ($width - $size) / 2;
		$y = ($height - $size) / 2;

		$logoUri = $this->logoToDataUri($logo);
		// SVG 2 `href` works on all current readers; emit the SVG 1.1
		// `xlink:href` too for older parsers that haven't caught up.
		$imageTag = sprintf(
			'<image x="%s" y="%s" width="%s" height="%s" '
			. 'href="%s" xlink:href="%s" preserveAspectRatio="xMidYMid meet"/>',
			$x,
			$y,
			$size,
			$size,
			h($logoUri),
			h($logoUri),
		);

		// Inject before the closing `</svg>`. str_replace with `limit=1` so
		// nested SVGs (e.g. an SVG already embedded inside the rendered
		// QR) don't get an extra logo at the wrong level.
		$pos = strrpos($rendered, '</svg>');
		if ($pos === false) {
			return $rendered;
		}

		return substr($rendered, 0, $pos) . $imageTag . substr($rendered, $pos);
	}

	/**
	 * Extract (width, height) from an SVG string. Reads `viewBox` first
	 * because that's the most reliable for chillerlan's output; falls back
	 * to `width` / `height` attributes, then to a sensible default.
	 *
	 * @param string $svg
     *
	 * @return array{0: float, 1: float}
	 */
	protected function detectSvgDimensions(string $svg): array {
		if (preg_match('/\bviewBox="(?:[\d.\-]+)\s+(?:[\d.\-]+)\s+([\d.]+)\s+([\d.]+)"/', $svg, $m) === 1) {
			return [(float)$m[1], (float)$m[2]];
		}
		$width = 100.0;
		$height = 100.0;
		if (preg_match('/\swidth="([\d.]+)"/', $svg, $m) === 1) {
			$width = (float)$m[1];
		}
		if (preg_match('/\sheight="([\d.]+)"/', $svg, $m) === 1) {
			$height = (float)$m[1];
		}

		return [$width, $height];
	}

	/**
	 * Turn a logo source into a string usable as an SVG `<image>` href.
	 *
	 * - `data:` URIs pass through.
	 * - Absolute filesystem paths get base64-encoded into a `data:` URI so
	 *   the SVG stays self-contained (important when the QR is served from
	 *   a CDN that wouldn't fetch the logo separately).
	 * - Relative paths are resolved against `WWW_ROOT`.
	 * - Anything else (`http(s)://...`) is returned as-is and rendered as
	 *   a URL reference; the browser fetches it when it paints the SVG.
	 *
	 * @param string $logo
     *
	 * @return string
	 */
	protected function logoToDataUri(string $logo): string {
		if (str_starts_with($logo, 'data:') || str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
			return $logo;
		}

		$absolute = $logo;
		if (!is_file($absolute)) {
			$absolute = defined('WWW_ROOT') ? WWW_ROOT . ltrim($logo, '/') : $logo;
		}
		if (!is_file($absolute) || !is_readable($absolute)) {
			// Fall through unchanged — the SVG will reference the URL
			// literally. Browsers that can resolve it will; others fail
			// gracefully (the QR is still scannable without the logo).
			return $logo;
		}

		$contents = @file_get_contents($absolute);
		if ($contents === false) {
			return $logo;
		}
		$mime = function_exists('mime_content_type') ? (mime_content_type($absolute) ?: 'image/png') : 'image/png';

		return 'data:' . $mime . ';base64,' . base64_encode($contents);
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
