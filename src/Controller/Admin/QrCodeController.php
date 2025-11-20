<?php
declare(strict_types=1);

namespace QrCode\Controller\Admin;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use chillerlan\QRCode\Output\QROutputInterface;
use InvalidArgumentException;
use QrCode\Utility\Formatter;
use QrCode\Utility\FormatterInterface;

class QrCodeController extends AppController {

	/**
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
		$result = null;
		$options = [];
		if ($this->request->is('post')) {
			$result = $this->request->getData('content');
		}

		$this->set(compact('result', 'options'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function image() {
		$content = $this->request->getQuery('content');

		if ($content && strlen($content) > 2953) { // QR code max capacity
			throw new BadRequestException('Content too long for QR code');
		}

		$result = $this->formatter()->formatText($content);
		$options = [];

		if ($this->request->getParam('_ext') === 'png') {
			$options['outputType'] = QROutputInterface::GDIMAGE_PNG;
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
