<?php

namespace QrCode\Utility;

interface FormatterInterface {

	/**
	 * @param string $text
	 * @param string|null $type
	 *
	 * @return string
	 */
	public function formatText(string $text, ?string $type = null): string;

	/**
	 * @param array<string, mixed> $content
	 *
	 * @return string
	 */
	public function formatCard(array $content): string;

	/**
	 * @param string $number
	 * @param string $content
	 *
	 * @return string
	 */
	public function formatSms(string $number, string $content): string;

	/**
	 * @param float $lat
	 * @param float $lng
	 *
	 * @return string
	 */
	public function formatGeo(float $lat, float $lng): string;

}
