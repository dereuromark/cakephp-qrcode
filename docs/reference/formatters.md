# Payload formatters

Formatting creates payload strings; it does not generate an image or validate all application input.
The default `QrCode\Utility\Formatter` provides these methods:

| Method | Result |
| --- | --- |
| `formatText($text, $type)` | Unchanged text, absolute URL, `tel:`, `mailto:` or Android market link. |
| `formatSms($number, $text)` | `smsto:` payload. |
| `formatGeo($latitude, $longitude)` | `geo:` payload. |
| `formatWifi($network, $password, $type)` | WiFi credentials; authentication type defaults to WPA. |
| `formatCard($fields)` | Compact MECARD contact payload. |
| `formatVcard($fields)` | Contact payload using vCard 4.0 headers, escaping and CRLF lines. |

For `formatText()`, supported types are `text`, `url`, `tel`, `email` and `market`. Other types leave the string unchanged.
Use the separate SMS, geo and WiFi methods for those payloads; `types()` lists UI labels and is not a dispatch API.

```php
$formatter = new \QrCode\Utility\Formatter();
$payload = $formatter->formatVcard([
    'name' => 'Alex Example',
    'email' => ['alex@example.com'],
    'tel' => ['+4312345678'],
    'url' => ['https://example.com'],
]);
echo $this->QrCode->image($payload, ['margin' => 4]);
```

## Contact fields

Both card methods recognize `name`, `nickname`, `note`, `birthday`, `tel`, `video`, `address`, `org`, `role`, `email` and `url`. MECARD also recognizes `sound`. Unknown keys are ignored.
The repeated fields `tel`, `video`, `address`, `org`, `role`, `email` and `url` accept a scalar or an array of values.

For vCard, `name` becomes FN. Addresses are strings placed in the street portion of ADR. Structured names, structured addresses and TITLE are not generated.
Include a name in contact examples: the formatter does not enforce required vCard fields or fold long lines, so it is not a complete RFC 6350 validator or serializer.
MECARD URLs are resolved through CakePHP's router; vCard URLs remain as supplied.

Birthdays accept `YYYYMMDD`, `YYYY-MM-DD`, or an array with `year`, `month` and `day`. Supply zero-padded month and day strings in arrays.
The formatter checks the digit pattern, not calendar validity.
WiFi and contact fields escape their reserved separators. SMS text and geo coordinates are concatenated without semantic validation; validate those values in your application.

## Custom formatters

Configure the helper with a formatter class implementing `FormatterInterface`:

```php
$this->addHelper('QrCode.QrCode', ['formatter' => \App\Utility\QrFormatter::class]);
```

For controller actions, set the separate configuration:

```php
\Cake\Core\Configure::write('QrCode.formatter', \App\Utility\QrFormatter::class);
```

The interface requires `formatText()`, `formatCard()`, `formatSms()` and `formatGeo()`. WiFi and vCard methods belong to the default formatter; custom implementations are not required to provide them.
`formatter()` creates an instance each time. Inline `image()` does not call `formatText()` automatically; controller image actions do.
