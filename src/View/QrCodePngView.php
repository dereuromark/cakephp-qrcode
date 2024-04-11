<?php

namespace QrCode\View;

use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use QrCode\Utility\Config;

class QrCodePngView extends QrCodeView {

	/**
	 * @return string
	 */
	public static function contentType(): string {
		return 'image/png';
	}

	/**
	 * @return void
	 */
	public function initialize(): void {
		$ext = $this->request->getParam('_ext') ?: 'png';
		/** @var string $contentType */
		$contentType = $this->response->getMimeType($ext);

		$response = $this->getResponse();
		$responseType = $response->getHeaderLine('Content-Type');
		if ($responseType === '' || str_starts_with($responseType, 'text/html')) {
			$response = $response->withType($contentType);
		}
		$this->setResponse($response);
	}

	/**
	 * Default config options.
	 *
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'ext' => 'png',
	];

	/**
	 * @var array<string, string>
	 */
	protected array $mimeTypes = [
		Config::TYPE_SVG => 'image/svg+xml',
		Config::TYPE_PNG => 'image/png',
	];

	/**
	 * Constructor
	 *
	 * @param \Cake\Http\ServerRequest|null $request Request instance.
	 * @param \Cake\Http\Response|null $response Response instance.
	 * @param \Cake\Event\EventManager|null $eventManager Event manager instance.
	 * @param array $viewOptions View options. See View::$_passedVars for list of
	 *   options which get set as class properties.
	 *
	 * @throws \Cake\Core\Exception\CakeException
	 */
	public function __construct(
		?ServerRequest $request = null,
		?Response $response = null,
		?EventManager $eventManager = null,
		array $viewOptions = [],
	) {
		parent::__construct($request, $response, $eventManager, $viewOptions);

		$this->layout = '';
		$this->disableAutoLayout();
	}

}
