<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use QrCode\View\Helper\QrCodeHelper;
use RuntimeException;

class LegacyNormalizerHelper extends QrCodeHelper {

	/**
	 * @var list<string>
	 */
	public array $observedLevels = [];

	public ?string $customLevel = null;

	public bool $renderNested = false;

	/**
	 * @param array<string, mixed> $options Options
	 *
	 * @return array<string, mixed>
	 */
	protected function normalizeOptions(array $options): array {
		if (!empty($options['failNormalization'])) {
			throw new RuntimeException('Custom normalization failed.');
		}
		if ($this->renderNested) {
			$this->renderNested = false;
			$this->raw('WIFI:T:WPA;S:Home;P:password;;');
		}
		if ($this->customLevel !== null) {
			$options += ['level' => $this->customLevel];
		}
		$options = parent::normalizeOptions($options);
		$this->observedLevels[] = $options['level'];

		return $options;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function defaultOptions(): array {
		return $this->normalizeOptions([]);
	}

}
