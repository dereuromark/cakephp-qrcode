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

	/**
	 * @return void
	 */
	public function testFormatCardBirthdayShapes(): void {
		$resultLong = $this->formatter->formatCard([
			'name' => 'Doe',
			'birthday' => '1990-01-15',
		]);
		$this->assertStringContainsString('BDAY:19900115;', $resultLong);

		$resultShort = $this->formatter->formatCard([
			'name' => 'Doe',
			'birthday' => '19900115',
		]);
		$this->assertStringContainsString('BDAY:19900115;', $resultShort);
	}

	/**
	 * vCard 4.0 happy path: produces a CRLF-terminated payload wrapped
	 * in BEGIN/END with VERSION:4.0 at the top. Same input shape as
	 * formatCard() so callers can swap by changing method name only.
	 *
	 * @return void
	 */
	public function testFormatVcardBasicShape(): void {
		$result = $this->formatter->formatVcard([
			'name' => 'Doe John',
			'tel' => '+49 30 12345',
			'email' => 'doe@example.org',
			'url' => 'https://example.org/~doe',
		]);

		$this->assertStringStartsWith("BEGIN:VCARD\r\nVERSION:4.0\r\n", $result);
		$this->assertStringEndsWith("\r\nEND:VCARD\r\n", $result);
		$this->assertStringContainsString("FN:Doe John\r\n", $result);
		$this->assertStringContainsString("TEL:+49 30 12345\r\n", $result);
		$this->assertStringContainsString("EMAIL:doe@example.org\r\n", $result);
		$this->assertStringContainsString("URL:https://example.org/~doe\r\n", $result);
	}

	/**
	 * Reserved-char escaping per RFC 6350 §3.4: `\` `,` `;` plus
	 * newline normalization to literal `\n`. Backslash MUST be
	 * escaped first (same trap as the WiFi/MECARD escapers).
	 *
	 * @return void
	 */
	public function testFormatVcardEscapesReservedChars(): void {
		$result = $this->formatter->formatVcard([
			'name' => 'Smith; John, Jr.',
			'note' => "Line1\nLine2 with \\ backslash",
		]);

		$this->assertStringContainsString('FN:Smith\\; John\\, Jr.', $result);
		$this->assertStringContainsString('NOTE:Line1\\nLine2 with \\\\ backslash', $result);
	}

	/**
	 * Multiple values for tel/email/url each emit their own line.
	 *
	 * @return void
	 */
	public function testFormatVcardEmitsOneLinePerMultiValue(): void {
		$result = $this->formatter->formatVcard([
			'name' => 'Doe',
			'tel' => ['+1 555 1', '+1 555 2'],
			'email' => ['a@example.org', 'b@example.org'],
		]);

		$this->assertSame(2, substr_count($result, "\r\nTEL:"));
		$this->assertSame(2, substr_count($result, "\r\nEMAIL:"));
	}

	/**
	 * Birthday accepts both `YYYY-MM-DD` and the legacy 8-char `YYYYMMDD`
	 * shape, matching what formatCard() already accepts.
	 *
	 * @return void
	 */
	public function testFormatVcardBirthdayShapes(): void {
		$resultLong = $this->formatter->formatVcard([
			'name' => 'Doe',
			'birthday' => '1990-01-15',
		]);
		$this->assertStringContainsString("BDAY:19900115\r\n", $resultLong);

		$resultShort = $this->formatter->formatVcard([
			'name' => 'Doe',
			'birthday' => '19900115',
		]);
		$this->assertStringContainsString("BDAY:19900115\r\n", $resultShort);
	}

}
