<?php

namespace QrCode\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use chillerlan\QRCode\Output\QROutputInterface;
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
	 * @return \Cake\Http\Response|null|void
	 */
	public function image() {
		$content = $this->request->getQuery('content');

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
		/** @var class-string<FormatterInterface> $className */
		$className = Configure::read('QrCode.formatter') ?? Formatter::class;

		return new $className();
	}

}
