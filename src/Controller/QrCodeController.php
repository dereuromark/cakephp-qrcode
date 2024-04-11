<?php

namespace QrCode\Controller;

use App\Controller\AppController;

class QrCodeController extends AppController {

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function image() {
		$content = $this->request->getQuery('content');
	}

}
