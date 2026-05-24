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

		// Pure function of (content, options, ext) — identical inputs always
		// produce identical bytes, so we can serve aggressive cache headers
		// and short-circuit on a matching If-None-Match. The ETag is a hash
		// of everything that affects the rendered payload (content + level
		// + extension); a request that differs in any one of those gets a
		// different ETag and a fresh render.
		$cacheResponse = $this->applyHttpCache($content, $level);
		if ($cacheResponse instanceof Response) {
			return $cacheResponse;
		}

		$this->set(['result' => $result, 'options' => $options]);

		return null;
	}

	/**
	 * Set strong `Cache-Control` + `ETag` headers on the response, and return
	 * a 304 short-circuit if the client already has the same ETag.
	 *
	 * Returned `null` means we filled in the headers but didn't short-circuit
	 * — the caller continues with the normal view render. A non-null return
	 * is a complete 304 response the caller must return as-is.
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
		if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
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
	 * Cache-Control value applied to all rendered QR codes.
	 *
	 * Default is `public, max-age=31536000, immutable` — one year, immutable
	 * because the content is keyed by hash. Apps with a different policy can
	 * override via `Configure::write('QrCode.cacheControl', '...')`.
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
