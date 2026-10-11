<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use QrCode\View\Helper\QrCodeHelper;
use RuntimeException;

class LegacyNormalizerCompatibilityTest extends TestCase {

	/**
	 * @return void
	 */
	public function testOldOverrideKeepsCustomDefaultsDuringImageAndRawRendering(): void {
		$helper = new LegacyNormalizerHelper(new View());
		$helper->customLevel = 'M';

		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $helper->image('Legacy image'));
		$this->assertStringContainsString('<svg', $helper->raw('Legacy raw'));
		$this->assertSame(['M', 'M'], $helper->observedLevels);
		$helper->image('Explicit level', ['level' => 'H']);
		$this->assertSame('H', $helper->observedLevels[2]);
	}

	/**
	 * @return void
	 */
	public function testOldOverrideReceivesPayloadAndLogoDefaults(): void {
		$helper = new LegacyNormalizerHelper(new View());
		$this->assertStringContainsString('<svg', $helper->raw('WIFI:T:WPA;S:Home;P:password;;'));
		$logo = 'data:image/png;base64,iVBORw0KGgo=';
		$image = $helper->image('Logo payload', ['logo' => $logo]);
		preg_match('/src="data:image\/svg\+xml;base64,([^"]+)"/', $image, $matches);
		$this->assertNotEmpty($matches);
		$svg = base64_decode($matches[1], true);
		$this->assertIsString($svg);
		$this->assertStringContainsString($logo, $svg);
		$this->assertSame(['Q', 'H'], $helper->observedLevels);
		$this->assertSame('L', $helper->defaultOptions()['level']);
	}

	/**
	 * @return void
	 */
	public function testFailedOverrideDoesNotLeavePayloadDefaultsBehind(): void {
		$helper = new LegacyNormalizerHelper(new View());
		try {
			$helper->raw('WIFI:T:WPA;S:Home;P:password;;', ['failNormalization' => true]);
			$this->fail('Expected normalization to fail.');
		} catch (RuntimeException $exception) {
			$this->assertSame('Custom normalization failed.', $exception->getMessage());
		}
		$this->assertSame('L', $helper->defaultOptions()['level']);
	}

}

class LegacyNormalizerHelper extends QrCodeHelper {

	/**
	 * @var list<string>
	 */
	public array $observedLevels = [];

	public ?string $customLevel = null;

	/**
	 * @param array<string, mixed> $options Options
     *
	 * @return array<string, mixed>
	 */
	protected function normalizeOptions(array $options): array {
		if (!empty($options['failNormalization'])) {
			throw new RuntimeException('Custom normalization failed.');
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
