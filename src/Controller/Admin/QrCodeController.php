<?php
declare(strict_types=1);

namespace QrCode\Controller\Admin;

use App\Controller\AppController;

class QrCodeController extends AppController {

	/**
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function index() {
	}

	/**
	 * @return \Cake\Http\Response|null|void Renders view
	 */
	public function image() {
		$content = $this->request->getQuery('content');
	}

}
