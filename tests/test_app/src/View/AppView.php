<?php
declare(strict_types=1);

namespace TestApp\View;

use Cake\View\View;

/**
 * Fake AppView for IDE autocomplete it templates
 *
 * @property \QrCode\View\Helper\QrCodeHelper $QrCode
 */
class AppView extends View {

	public function initialize(): void {
		$this->loadHelper('QrCode.QrCode');
	}
}
