<?php

namespace QrCode\Utility;

use Cake\Routing\Router;
use InvalidArgumentException;

class Formatter implements FormatterInterface {

	/**
	 * @var array<string, string>
	 */
	protected array $types = [
		'text' => 'Text',
		'url' => 'Url',
		'tel' => 'Phone Number',
		'sms' => 'Text message',
		'email' => 'E-Mail',
		'geo' => 'Geo',
		'wifi' => 'Wifi Network',
		'market' => 'Market',
		'card' => 'Vcard',
	];

	/**
	 * @return array<string, string>
	 */
	public function types(): array {
		return $this->types;
	}

	/**
	 * @param string $text
	 * @param string|null $type
	 *
	 * @return string
	 */
	public function formatText(string $text, ?string $type = null): string {
		switch ($type) {
			case 'text':
				break;
			case 'url':
				$text = Router::url($text, true);

				break;
			case 'tel':
				$text = 'tel:' . $text;

				break;
			case 'email':
				$text = 'mailto:' . $text;

				break;
			case 'market':
				$text = 'market://search?q=pname:' . $text;
		}

		return $text;
	}

	/**
	 * @param array<string, mixed> $content
	 *
	 * @return string
	 */
	public function formatCard(array $content): string {
		if (isset($content['birthday']) && is_array($content['birthday'])) {
			$content['birthday'] = $content['birthday']['year'] . '-' . $content['birthday']['month'] . '-' . $content['birthday']['day'];
		}

		$res = [];
		foreach ($content as $key => $val) {
			switch ($key) {
				case 'name':
					$res[] = 'N:' . $this->escapeMecard((string)$val);

					break;
				case 'nickname':
					$res[] = 'NICKNAME:' . $this->escapeMecard((string)$val);

					break;
				case 'sound':
					$res[] = 'SOUND:' . $this->escapeMecard((string)$val);

					break;
				case 'note':
					$res[] = 'NOTE:' . $this->escapeMecard((string)$val);

					break;
				case 'birthday':
					if (strlen((string)$val) !== 8) {
						if (strlen((string)$val) < 10) {
							throw new InvalidArgumentException('Invalid date format for birthday');
						}
						$val = substr((string)$val, 0, 4) . substr((string)$val, 6, 2) . substr((string)$val, 10, 2);
					}
					$res[] = 'BDAY:' . $val;

					break;
				case 'tel':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'TEL:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'video':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'TEL-AV:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'address':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ADR:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'org':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ORG:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'role':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'ROLE:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'email':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'EMAIL:' . $this->escapeMecard((string)$v);
					}

					break;
				case 'url':
					$val = (array)$val;
					foreach ($val as $v) {
						$res[] = 'URL:' . $this->escapeMecard(Router::url($v, true));
					}

					break;
			}
		}

		return 'MECARD:' . implode(';', $res) . ';';
	}

	/**
	 * Build an RFC 6350 vCard 4.0 payload from the same input shape
	 * `formatCard()` accepts.
	 *
	 * vCard 4.0 is the IETF-blessed contact format and the one iOS
	 * scanners increasingly prefer over MECARD (Apple silently drops
	 * fields from MECARD payloads in some recent iOS versions). The
	 * input shape mirrors `formatCard()` so callers can switch between
	 * the two by changing the method name only.
	 *
	 * Reserved-character escaping per RFC 6350 §3.4: `\` → `\\`,
	 * `,` → `\,`, `;` → `\;`, newlines → literal `\n`. Note that `:`
	 * is NOT escaped in vCard (unlike MECARD) — the field separator
	 * is the colon between key and value, and bare colons inside the
	 * value are unambiguous.
	 *
	 * @param array<string, mixed> $content Same shape as `formatCard()`.
	 *
	 * @return string vCard 4.0 payload (CRLF-terminated per spec).
	 */
	public function formatVcard(array $content): string {
		if (isset($content['birthday']) && is_array($content['birthday'])) {
			$content['birthday'] = $content['birthday']['year'] . '-' . $content['birthday']['month'] . '-' . $content['birthday']['day'];
		}

		$lines = [
			'BEGIN:VCARD',
			'VERSION:4.0',
		];

		foreach ($content as $key => $val) {
			switch ($key) {
				case 'name':
					$lines[] = 'FN:' . $this->escapeVcard((string)$val);

					break;
				case 'nickname':
					$lines[] = 'NICKNAME:' . $this->escapeVcard((string)$val);

					break;
				case 'note':
					$lines[] = 'NOTE:' . $this->escapeVcard((string)$val);

					break;
				case 'birthday':
					$lines[] = 'BDAY:' . $this->normalizeBirthday((string)$val);

					break;
				case 'tel':
					foreach ((array)$val as $v) {
						$lines[] = 'TEL:' . $this->escapeVcard((string)$v);
					}

					break;
				case 'video':
					// No standard vCard equivalent for MECARD's TEL-AV; emit
					// as a typed TEL with the closest match.
					foreach ((array)$val as $v) {
						$lines[] = 'TEL;TYPE=video:' . $this->escapeVcard((string)$v);
					}

					break;
				case 'address':
					foreach ((array)$val as $v) {
						// vCard's structured ADR splits into 7 fields
						// (po-box, ext, street, locality, region, postcode,
						// country) but the input is a single string; emit
						// the same single-string shape MECARD uses — the
						// reader puts it in the "street" slot.
						$lines[] = 'ADR:;;' . $this->escapeVcard((string)$v) . ';;;;';
					}

					break;
				case 'org':
					foreach ((array)$val as $v) {
						$lines[] = 'ORG:' . $this->escapeVcard((string)$v);
					}

					break;
				case 'role':
					foreach ((array)$val as $v) {
						$lines[] = 'ROLE:' . $this->escapeVcard((string)$v);
					}

					break;
				case 'email':
					foreach ((array)$val as $v) {
						$lines[] = 'EMAIL:' . $this->escapeVcard((string)$v);
					}

					break;
				case 'url':
					foreach ((array)$val as $v) {
						// `Router::url` is intentionally NOT applied to URLs
						// inside a vCard value: contact cards typically embed
						// canonical external URLs (a homepage, a LinkedIn
						// profile) that shouldn't be rewritten against the
						// emitting app's base URL.
						$lines[] = 'URL:' . $this->escapeVcard((string)$v);
					}

					break;
			}
		}

		$lines[] = 'END:VCARD';

		// RFC 6350 §3.2: vCard lines MUST be terminated by CRLF. Tolerant
		// scanners accept LF, but emitting strict CRLF here keeps the
		// payload spec-conformant for the strict ones.
		return implode("\r\n", $lines) . "\r\n";
	}

	/**
	 * Normalize a date input to RFC 6350 birthday format `YYYYMMDD`.
	 *
	 * Accepts both the compressed form (`19900115`, 8 chars) and the
	 * extended form (`1990-01-15`, 10 chars with separators). Anything
	 * else throws so the caller knows to pre-format their input.
	 *
	 * @param string $value
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return string
	 */
	protected function normalizeBirthday(string $value): string {
		if (strlen($value) === 8 && ctype_digit($value)) {
			return $value;
		}
		// Extended format: `YYYY-MM-DD` (chars 0-3, 5-6, 8-9).
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) === 1) {
			return $matches[1] . $matches[2] . $matches[3];
		}

		throw new InvalidArgumentException('Invalid date format for birthday');
	}

	/**
	 * Escape vCard 4.0 reserved characters per RFC 6350 §3.4. Backslash
	 * must be replaced first (same trap as the WiFi/MECARD escapers) so
	 * subsequent rules don't double-escape it.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function escapeVcard(string $value): string {
		return str_replace(
			['\\', "\r\n", "\n", "\r", ',', ';'],
			['\\\\', '\\n', '\\n', '\\n', '\\,', '\\;'],
			$value,
		);
	}

	/**
	 * Escape MECARD/vCard reserved characters so a single field cannot break the payload.
	 *
	 * Backslash must be escaped first; afterwards `;`, `:`, `,` and CR/LF are escaped per the
	 * MECARD spec so a field separator inside a value cannot truncate the rest of the card.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function escapeMecard(string $value): string {
		return str_replace(
			['\\', ';', ':', ',', "\r", "\n"],
			['\\\\', '\\;', '\\:', '\\,', '', '\\n'],
			$value,
		);
	}

	/**
	 * Build a WiFi credential payload per the de-facto WiFi QR spec.
	 *
	 * SSID and password must escape the same set of reserved characters as
	 * MECARD (`\`, `;`, `,`, `:`) plus `"` — otherwise an SSID containing `;`
	 * truncates the rest of the payload and a password containing `\` corrupts
	 * the credential on the scanning device. Without escaping, networks named
	 * e.g. `Cafe;Free` simply will not connect from a scanned code.
	 *
	 * @param string $network
	 * @param string $password
	 * @param string|null $type Authentication type (`WPA`, `WEP`, `nopass`); defaults to `WPA`.
	 *
	 * @return string
	 */
	public function formatWifi(string $network, string $password, ?string $type = null): string {
		if ($type === null) {
			$type = 'WPA';
		}

		$options = [
			'T:' . $type,
			'S:' . $this->escapeWifi($network),
			'P:' . $this->escapeWifi($password),
		];

		return 'WIFI:' . implode(';', $options) . ';;';
	}

	/**
	 * Escape WiFi-QR reserved characters. Backslash must be escaped first
	 * (otherwise the subsequent replacements double-escape it). The set is
	 * `\` `;` `,` `:` `"` per the spec referenced by Android's documented
	 * QR-WiFi handling.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function escapeWifi(string $value): string {
		return str_replace(
			['\\', ';', ',', ':', '"'],
			['\\\\', '\\;', '\\,', '\\:', '\\"'],
			$value,
		);
	}

	/**
	 * @param string $number
	 * @param string $content
	 *
	 * @return string
	 */
	public function formatSms(string $number, string $content): string {
		return 'smsto:' . $number . ':' . $content;
	}

	/**
	 * @param float $lat
	 * @param float $lng
	 *
	 * @return string
	 */
	public function formatGeo(float $lat, float $lng): string {
		return 'geo:' . implode(',', [$lat, $lng]); // like 77.1,11.8
	}

}
