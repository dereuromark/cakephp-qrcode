# Upgrading and contributing

The `cake6` development branch targets CakePHP 6. Composer declares the supported PHP, CakePHP and QR library versions.
Use the [version map](https://github.com/dereuromark/cakephp-qrcode/wiki#cakephp-version-map) for older CakePHP applications.
Application view and helper overrides use `$defaultConfig` for inherited defaults. Dependency injection types now live in `Cake\Container`. The `master` branch remains available for CakePHP 5.

The helper uses the upstream `outputBase64` option. Old `imageBase64` examples do not control current library output.
Configure `defaultLevel` on the helper rather than as a global `QrCode.defaultLevel` key.
A per-call level takes precedence over the automatic H level for logos.
Controller image options are limited to the documented query parameters; advanced inline options do not become controller options automatically.

The public image action now accepts only GET and HEAD; other methods return 405.
`image()` rejects non-image output with `InvalidArgumentException` instead of returning an invalid HTML image.

Custom helpers can continue overriding `normalizeOptions(array $options): array`. Rendering still calls that hook. Logo defaults apply when the override calls the parent without setting `level`. An override that supplies its own level keeps that choice, including for logos.

## Development

```sh
composer install
composer test
composer cs-check
composer stan-setup
composer stan
cd docs
npm ci
npm run docs:build
```

The test suite skips Imagick-specific coverage when the extension is unavailable. Install GD to run PNG tests.
Library-specific behavior should be checked against the version installed by Composer, including the lowest supported dependency combination.

Serve the built site with `npm run docs:preview`. The site uses the `/cakephp-qrcode/` base path.
