<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use RuntimeException;
use TestApp\View\Helper\LegacyNormalizerHelper;

class LegacyNormalizerCompatibilityTest extends TestCase {

	/**
	 * @return void
	 */
	public function testOldOverrideKeepsCustomDefaultsDuringImageAndRawRendering(): void {
		$helper = new LegacyNormalizerHelper(new View());
		$helper->customLevel = 'M';

		$this->assertStringStartsWith('<img src="data:image/svg+xml;base64,', $helper->image('Legacy image'));
		$this->assertStringContainsString('<svg', $helper->raw('Legacy raw'));
		$helper->image('Custom logo level', ['logo' => 'data:image/png;base64,iVBORw0KGgo=']);
		$this->assertSame(['M', 'M', 'M'], $helper->observedLevels);
		$helper->image('Explicit level', ['level' => 'H']);
		$this->assertSame('H', $helper->observedLevels[3]);
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
		$this->assertSame(['L', 'H'], $helper->observedLevels);
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

	/**
	 * @return void
	 */
	public function testNestedRenderingRestoresOuterLogoDefaults(): void {
		$helper = new LegacyNormalizerHelper(new View());
		$helper->renderNested = true;
		$this->assertStringStartsWith('<img ', $helper->image('Outer logo', ['logo' => 'data:image/png;base64,iVBORw0KGgo=']));
		$this->assertSame(['L', 'H'], $helper->observedLevels);
		$this->assertSame('L', $helper->defaultOptions()['level']);
	}

}
