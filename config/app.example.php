<?php

use QrCode\Utility\Formatter;

return [
	'QrCode' => [
		'formatter' => Formatter::class,

		// Cache-Control header applied to all rendered QR codes. Must be a
		// non-empty string; anything else falls back to the default below.
		// Default (when unset/empty): 'public, max-age=31536000, immutable'
		// (one year, immutable — content is keyed by content hash via ETag).
		'cacheControl' => 'public, max-age=31536000, immutable',
	],
];
