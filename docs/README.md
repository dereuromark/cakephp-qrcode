# QrCode Plugin docs

## Helper usage

Add the helper in your `AppView` class:
```php
$this->loadHelper('QrCode.QrCode');
```
Then you can use it in your views to display QR codes:
```php
echo $this->QrCode->image($text, $optionalOptions);
```

By default, it uses base64encoded images, so no 2nd request is required.

### Formatter
You can use the built-in formatter for most common QR code types:
- Text
- Url
- Phone Number (Call)
- Text message (SMS)
- eMail
- Geo Coordinates (Maps)
- Wifi Network
- Market
- Vcard (MECARD compact format)
- vCard 4.0 (RFC 6350 standard format)

```php
$text = $this->Qrcode->formatter()->formatText($text);
$geo = $this->Qrcode->formatter()->formatGeo($lat, $lng);
$sms = $this->Qrcode->formatter()->formatSms($number, $text);
...

echo $this->QrCode->image(...);
```
It will help to normalize user input coming from forms or alike.

#### MECARD vs vCard 4.0

Two contact-card formats are supported:

- `formatCard($content)` produces a **MECARD** payload — the compact, single-line format originally pushed by NTT DoCoMo. Smaller QR codes, but several fields (notably middle name, job title, multiple emails) are not part of the spec.
- `formatVcard($content)` produces a **vCard 4.0** payload per [RFC 6350](https://datatracker.ietf.org/doc/html/rfc6350). Larger QR codes but supported by every modern phone OS contact-import flow, with first-class fields for full structured name (`N` with prefix/given/middle/family/suffix), formatted name (`FN`), title, organization, multiple emails / phones, address, URL, birthday, and note.

Both methods accept the same input keys. Choose `formatVcard()` when the payload will be scanned by phones and you need a clean contact-import; pick `formatCard()` when QR density / scan reliability matters more (printed small, partially obscured).

Both card payloads default to ECC level Q (~25% recovery) because they're commonly printed small or partially covered by a logo overlay — see [Default ECC level](#default-ecc-level) below.

### Logo overlay

The helper can composite a centred logo on top of the rendered code:

```php
echo $this->QrCode->image($url, [
    'logo' => WWW_ROOT . 'img/logo.svg', // path or data: URI
    'logoSize' => 0.2, // fraction of QR width, default 0.2 (20%)
]);
```

When `logo` is set the helper automatically bumps the ECC level to **H** (~30% recovery) regardless of the payload default, so the QR code still scans reliably with up to ~25% of its module area replaced by the logo. Keep `logoSize` ≤ 0.25 to stay within recoverable bounds; values above 0.30 are likely to break scanning.

The `logo` value may be either an absolute filesystem path or a `data:` URI. SVG logos are embedded inline; raster logos (PNG/JPG) are base64-encoded into a `data:` URI before injection.

### Default ECC level

The helper picks a sensible ECC (error correction) level per payload:

- Short URLs and plain text default to **L** (~7%) for the densest possible code.
- WiFi credentials, MECARD, and vCard payloads default to **Q** (~25%) — they're commonly printed small or partially obscured by a logo.
- When a `logo` is passed, the level is force-bumped to **H** (~30%) regardless of payload.

Override the default for all payloads via:

```php
Configure::write('QrCode.defaultLevel', 'M'); // L, M, Q, or H
```

Or per call by passing `'level' => 'H'` in the options array. Note: higher ECC levels reduce byte-mode capacity — a level-H QR fits roughly half the characters a level-L QR of the same version does.

### Controller rendering
If you want more control over the image, as well as type (png, svg), you
can also let the controller action render it and use it as a generated
on-the-fly image.

```php
echo $this->QrCode->svg($content, $options);
// or
echo $this->QrCode->png($content, $options);
```

#### HTTP caching

Rendered responses from the QrCode controller carry an `ETag` (sha256 of the encoded payload + relevant options) and a `Cache-Control` header. A repeat request with a matching `If-None-Match` header short-circuits to a `304 Not Modified` without re-rendering — useful when QR codes are embedded into pages and re-fetched on every render.

The default `Cache-Control` is `public, max-age=31536000, immutable` — one year, immutable because the URL is keyed by content hash. Override it for the whole plugin:

```php
Configure::write('QrCode.cacheControl', 'public, max-age=86400'); // 1 day
```

Set it to a `private`/`no-store` directive if the QR codes contain personalized payloads that should not be cached by intermediaries.

### Advanced usage

See also https://php-qrcode.readthedocs.io/en/v5.0.x/Usage/Advanced-usage.html

## Admin Backend
Go to `/admin/qr-code`.

Make sure you set up ACL to only have admins access this part.
