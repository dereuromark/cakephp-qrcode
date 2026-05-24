<?php
declare(strict_types=1);

namespace QrCode\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use InvalidArgumentException;
use QrCode\Lib\OutputType;
use QrCode\Utility\Formatter;
use QrCode\Utility\FormatterInterface;

class QrCodeController extends AppController {

	/**
	 * QR code max capacity (binary, byte mode) per the spec.
	 *
	 * @var int
	 */
	public const MAX_CONTENT_LENGTH = 2953;

	/**
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$result = null;
		$options = [];
		if ($this->request->is('post')) {
			$content = $this->request->getData('content');
			if (!is_string($content) || $content === '') {
				throw new BadRequestException('Missing or invalid "content" parameter.');
			}
			if (strlen($content) > static::MAX_CONTENT_LENGTH) {
				throw new BadRequestException('Content too long for QR code.');
			}
			$result = $content;
		}

		$this->set(['result' => $result, 'options' => $options]);
	}

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

		$this->set(['result' => $result, 'options' => $options]);
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
