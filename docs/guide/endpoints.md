# Image endpoints

Inline `image()` output works without plugin routes. The `svg()` and `png()` methods instead return image tags pointing to controller actions.

To enable the plugin's routes, replace its registration with:

```php
$this->addPlugin('QrCode');
```

This also enables the [admin routes](./admin). Apply your application's access policy to both route groups before exposing them.
The controllers extend your `AppController`; the plugin does not configure authentication or authorization.

From a template outside an admin prefix:

```php
echo $this->QrCode->svg('https://example.com/about', ['level' => 'M']);
echo $this->QrCode->png('https://example.com/about', ['level' => 'M']);
```

With the standard application `DashedRoute` class, the public URL is `/qr-code/qr-code/image.svg?content=...`, or the equivalent `.png` URL.
The public action accepts GET and HEAD. `content` must be a nonempty string; `0` is valid.
Only the `level` rendering option is read from the query string. Inline options such as `logo`, `scale` and `margin` do not configure this action.
The public action defaults to level L, including for structured payloads.

| Level | Maximum input bytes |
| --- | ---: |
| L | 2953 |
| M | 2331 |
| Q | 1663 |
| H | 1273 |

These limits check input bytes before the configured formatter runs. They do not guarantee capacity for arbitrary custom formatter output.
The action uppercases the requested level. Valid lowercase letters work; other values fall back to L.

The helper's generated URLs retain the current routing prefix. In admin templates they target the admin action, whose options and caching differ.
Use inline `image()` when you need the same rendering options in every context.
See [HTTP caching](../reference/http) for the public response headers.
