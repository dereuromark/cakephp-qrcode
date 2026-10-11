<?php

namespace QrCode\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use InvalidArgumentException;
use QrCode\Lib\OutputType;
use QrCode\Utility\Formatter;
use QrCode\Utility\FormatterInterface;
use QrCode\View\QrCodePngView;
use QrCode\View\QrCodeView;

class QrCodeController extends AppController {

	/**
	 * @return string[]
	 */
	public function viewClasses(): array {
		return [QrCodeView::class, QrCodePngView::class];
	}

	/**
	 * Byte-mode QR capacity at version 40, by ECC level (bytes).
	 *
	 * The previous `MAX_CONTENT_LENGTH = 2953` constant was the level-L cap.
	 * Anyone bumping `level` to H got past the controller's length check but
	 * then failed in the renderer with a less helpful error, since the real
	 * cap at H is ~1273 bytes. The right ceiling is ECC-dependent.
	 *
	 * @var array<string, int>
	 */
	public const MAX_CONTENT_LENGTH_BY_LEVEL = [
		'L' => 2953,
		'M' => 2331,
		'Q' => 1663,
		'H' => 1273,
	];

	/**
	 * Backward-compat alias for the L-level cap. Kept so apps that referenced
	 * `QrCodeController::MAX_CONTENT_LENGTH` continue to compile; new code
	 * should consult `MAX_CONTENT_LENGTH_BY_LEVEL` (or the public helper
	 * `maxContentLengthForLevel()`) instead.
	 *
	 * @var int
	 */
	public const MAX_CONTENT_LENGTH = 2953;

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function image() {
		$this->request->allowMethod(['get', 'head']);

		$content = $this->request->getQuery('content');
		if (!is_string($content) || $content === '') {
			throw new BadRequestException('Missing or invalid "content" parameter.');
		}

		$level = $this->resolveLevel($this->request->getQuery('level'));
		$cap = static::maxContentLengthForLevel($level);
		if (strlen($content) > $cap) {
			throw new BadRequestException(sprintf(
				'Content too long for QR code at ECC level %s (max %d bytes).',
				$level,
				$cap,
			));
		}

		$result = $this->formatter()->formatText($content);
		$options = ['level' => $level];

		if ($this->request->getParam('_ext') === 'png') {
			$options = OutputType::apply($options, OutputType::PNG);
		}

		// Validate the request identity before rendering the image.
		$cacheResponse = $this->applyHttpCache($content, $level);
		if ($cacheResponse instanceof Response) {
			return $cacheResponse;
		}

		$this->set(compact('result', 'options'));

		return null;
	}

	/**
	 * Set cache headers and return a 304 response for matching validators.
	 *
	 * The ETag identifies input content, level and extension; it does not hash
	 * rendered bytes or include custom formatter and library settings.
	 * A null return lets the caller continue rendering.
	 *
	 * @param string $content Raw payload going into the QR code.
	 * @param string $level Resolved ECC level.
	 *
	 * @return \Cake\Http\Response|null
	 */
	protected function applyHttpCache(string $content, string $level): ?Response {
		$ext = (string)$this->request->getParam('_ext');
		$etag = '"' . sha1($content . '|' . $level . '|' . $ext) . '"';

		$ifNoneMatch = (string)$this->request->getHeaderLine('If-None-Match');
		if ($this->matchesEtag($ifNoneMatch, $etag)) {
			// The 304 path doesn't render a body. Browsers and CDNs use the
			// previously-cached response. `Cache-Control` is repeated so a
			// proxy that revalidates also picks up the same TTL.
			return $this->response
				->withStatus(304)
				->withHeader('ETag', $etag)
				->withHeader('Cache-Control', $this->cacheControlHeader());
		}

		// Cake mutates Response immutably — re-assign so the view render
		// uses the augmented headers.
		$this->response = $this->response
			->withHeader('ETag', $etag)
			->withHeader('Cache-Control', $this->cacheControlHeader());

		return null;
	}

	/**
	 * @param string $header
	 * @param string $etag
     *
	 * @return bool
	 */
	protected function matchesEtag(string $header, string $etag): bool {
		foreach (explode(',', $header) as $candidate) {
			$candidate = trim($candidate);
			if (str_starts_with($candidate, 'W/')) {
				$candidate = substr($candidate, 2);
			}
			if ($candidate === '*' || $candidate === $etag) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cache-Control value applied to public image responses.
	 *
	 * Override the default through the QrCode.cacheControl configuration.
	 *
	 * @return string
	 */
	protected function cacheControlHeader(): string {
		$configured = Configure::read('QrCode.cacheControl');

		return is_string($configured) && $configured !== ''
			? $configured
			: 'public, max-age=31536000, immutable';
	}

	/**
	 * @param string $level
     *
	 * @return int
	 */
	public static function maxContentLengthForLevel(string $level): int {
		return static::MAX_CONTENT_LENGTH_BY_LEVEL[$level] ?? static::MAX_CONTENT_LENGTH_BY_LEVEL['L'];
	}

	/**
	 * Coerce a query-string level value to one of `L`, `M`, `Q`, `H`. Anything
	 * else falls back to `L` so the cap stays generous and we don't surprise
	 * callers who passed nothing at all.
	 *
	 * @param mixed $level
     *
	 * @return string
	 */
	protected function resolveLevel(mixed $level): string {
		if (is_string($level)) {
			$upper = strtoupper($level);
			if (isset(static::MAX_CONTENT_LENGTH_BY_LEVEL[$upper])) {
				return $upper;
			}
		}

		return 'L';
	}

	/**
	 * @return \QrCode\Utility\FormatterInterface
	 */
	public function formatter(): FormatterInterface {
		$className = Configure::read('QrCode.formatter') ?? Formatter::class;

		if (!is_string($className) || !is_subclass_of($className, FormatterInterface::class)) {
			throw new InvalidArgumentException(
				sprintf('Formatter class must implement FormatterInterface, got %s', is_string($className) ? $className : gettype($className)),
			);
		}

		return new $className();
	}

}
