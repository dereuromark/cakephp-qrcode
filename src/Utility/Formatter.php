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
					if (strlen($val) !== 8) {
						if (strlen($val) < 10) {
							throw new InvalidArgumentException('Invalid date format for birthday');
						}
						$val = substr($val, 0, 4) . substr($val, 6, 2) . substr($val, 10, 2);
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
	 * @param string $network
	 * @param string $password
	 * @param string|null $type
	 *
	 * @return string
	 */
	public function formatWifi(string $network, string $password, ?string $type = null): string {
		if ($type === null) {
			$type = 'WPA';
		}

		$options = [
			'T:' . $type,
			'S:' . $network,
			'P:' . $password,
		];

		return 'WIFI:' . implode(';', $options) . ';;';
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
