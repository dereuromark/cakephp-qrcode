# Admin generator

With [plugin routes enabled](./endpoints), open `/admin/qr-code` to enter text and generate an inline SVG image.
The form accepts a nonempty string up to 2953 bytes. The helper defaults to error correction level L. A configured higher helper level reduces the available payload capacity.

The admin controller inherits your application's `AppController`. Protect the admin prefix through your existing authentication and authorization setup.
There is no plugin role check, standalone mode or separate user table.

The admin `image` action also accepts a `content` query parameter and an optional `.png` extension. It uses the configured formatter and the helper's default rendering options.
It does not implement the public action's `level` query option or conditional cache headers.
