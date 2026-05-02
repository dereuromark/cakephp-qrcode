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
	 * QR code max capacity (binary, byte mode) per the spec.
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
		if (strlen($content) > static::MAX_CONTENT_LENGTH) {
			throw new BadRequestException('Content too long for QR code.');
		}

		$result = $this->formatter()->formatText($content);
		$options = [];

		if ($this->request->getParam('_ext') === 'png') {
			$options = OutputType::apply($options, OutputType::PNG);
		}

		$this->set(compact('result', 'options'));
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
