<?php
declare(strict_types=1);

namespace QrCode\Test\TestCase\Utility;

use Cake\TestSuite\TestCase;
use QrCode\Utility\Formatter;

class FormatterTest extends TestCase {

	/**
	 * @var \QrCode\Utility\Formatter
	 */
	protected $formatter;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->formatter = new Formatter();

		$this->loadRoutes();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->formatter);

		parent::tearDown();
	}

	/**
	 * @uses \QrCode\View\Helper\QrCodeHelper::image()
	 *
	 * @return void
	 */
	public function testWifi(): void {
		$network = 'FooBar';
		$result = $this->formatter->formatWifi($network, 'pwd');
		$this->assertSame('WIFI:T:WPA;S:FooBar;P:pwd;;', $result);
	}

	/**
	 * Regression: SSID containing `;` used to truncate the payload because
	 * the formatter pasted it raw. The WiFi QR spec mandates backslash-
	 * escaping `\`, `;`, `,`, `:`, and `"` in SSID and password.
	 *
	 * @return void
	 */
	public function testWifiEscapesReservedCharactersInSsidAndPassword(): void {
		$result = $this->formatter->formatWifi('Cafe;Free', 'p\\ass:w,ord');
		$this->assertSame('WIFI:T:WPA;S:Cafe\\;Free;P:p\\\\ass\\:w\\,ord;;', $result);
	}

	/**
	 * Regression: backslash must be escaped before the other reserved chars,
	 * otherwise a leading `\` gets double-escaped to `\\\\` (six chars) by
	 * the subsequent rules. Verifies the ordering of `str_replace` args in
	 * `escapeWifi()`.
	 *
	 * @return void
	 */
	public function testWifiBackslashIsEscapedExactlyOnce(): void {
		$result = $this->formatter->formatWifi('A\\B', 'A\\B');
		$this->assertSame('WIFI:T:WPA;S:A\\\\B;P:A\\\\B;;', $result);
	}

	/**
	 * @return void
	 */
	public function testFormatCardEscapesNameSeparator(): void {
		$result = $this->formatter->formatCard(['name' => 'Doe;John']);
		$this->assertSame('MECARD:N:Doe\\;John;', $result);
	}

	/**
	 * Address, org, role and email used to concatenate raw values, so a single `;` truncated the card.
	 *
	 * @return void
	 */
	public function testFormatCardEscapesScalarFields(): void {
		$result = $this->formatter->formatCard([
			'name' => 'Doe',
			'address' => 'Main St; Apt 5',
			'org' => 'Acme; Inc',
			'role' => 'CEO:Founder',
			'email' => 'a,b@example.com',
		]);
		$this->assertSame(
			'MECARD:N:Doe;ADR:Main St\\; Apt 5;ORG:Acme\\; Inc;ROLE:CEO\\:Founder;EMAIL:a\\,b@example.com;',
			$result,
		);
	}

	/**
	 * @return void
	 */
	public function testFormatCardEscapesBackslashFirst(): void {
		$result = $this->formatter->formatCard(['name' => 'Back\\Slash']);
		$this->assertSame('MECARD:N:Back\\\\Slash;', $result);
	}

	/**
	 * @return void
	 */
	public function testFormatCardNoteEscaping(): void {
		$result = $this->formatter->formatCard(['note' => "Line1\nLine2; with comma,"]);
		$this->assertSame('MECARD:NOTE:Line1\\nLine2\\; with comma\\,;', $result);
	}

}
