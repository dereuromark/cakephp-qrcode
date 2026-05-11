<?php

namespace QrCode\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
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
	 * @return \Cake\Http\Response|null|void
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

		$this->set(compact('result', 'options'));
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
