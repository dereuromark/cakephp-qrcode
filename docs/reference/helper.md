# Helper options

`image(string $content, array $options = [])` returns an HTML image tag. SVG is the default output; the payload is encoded unchanged.

| Option | Default | Behavior |
| --- | --- | --- |
| `level` | Payload-dependent | L, M, Q or H; other values resolve to L. |
| `version` | Automatic | Selects a QR version through the underlying library. |
| `scale` | 3 | Rendering scale; output-specific behavior comes from the library. |
| `margin` | 0 | Positive values enable the quiet zone; this is an enable switch, not its width. |
| `quietzoneSize` | Library default, 4 | Quiet-zone width in modules when enabled. |
| `transparent` | false | Maps to the library's `imageTransparent` option. |
| `connectPaths` | true | Connects compatible SVG paths. |
| `logo` | None | Adds a centered logo to SVG output only. |
| `logoSize` | 0.2 | Fraction of image width, clamped between 0.05 and 0.35. |

Without an explicit `level` or helper `defaultLevel`, payloads use L, preserving the existing rendering capacity. Use Q explicitly for contact or WiFi payloads when preferred.
A logo selects H unless the call supplies `level`. Set a helper-wide default during registration:

```php
$this->addHelper('QrCode.QrCode', ['defaultLevel' => 'M']);
```

`Configure::write('QrCode.defaultLevel', ...)` does not configure this helper.
Per-call `level` overrides the helper default, and a logo still selects H when there is no per-call level.

## Logos

```php
echo $this->QrCode->image('https://example.com/about', [
    'margin' => 4,
    'logo' => WWW_ROOT . 'img/logo.svg',
    'logoSize' => 0.2,
]);
```

Create the referenced logo file first. Readable local files, including raster logos, are embedded as data URIs inside the SVG image. Relative filesystem paths are resolved against `WWW_ROOT`; a supplied data URI is used directly.
URL references and unreadable paths are left as references. A logo URL inside an SVG image is subject to the browser's external-resource restrictions, so prefer a local file or data URI.
PNG and Imagick output receive no logo overlay.

`logoSize` measures width, not covered module area. The overlay can obscure required patterns, and error correction does not guarantee that a logo will scan. Test the final image on target scanners.

## Advanced output

Options are passed to the installed library's `QROptions` after the plugin mappings above.
Use the [v6 library manual](https://php-qrcode.readthedocs.io/en/v6.0.x/) or [v5 manual](https://php-qrcode.readthedocs.io/en/v5.0.x/) for output interfaces and version-specific settings.
`image()` always wraps raw SVG or raster bytes as an image data URI, including when `outputBase64` is false. Non-image output is rejected.

`raw()` returns image bytes and `resource()` returns an Imagick resource. Both are marked internal and require the corresponding output dependencies.
`svg()` and `png()` use [controller endpoints](../guide/endpoints); they do not apply these inline options locally.
