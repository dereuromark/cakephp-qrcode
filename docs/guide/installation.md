# Installation

Install the plugin in your CakePHP application:

```sh
composer require dereuromark/cakephp-qrcode
```

Composer checks the supported PHP and CakePHP versions from the package requirements.
SVG output needs no image extension. PNG output needs GD; the internal Imagick resource method needs Imagick.
No database tables, migrations or queue workers are required.

Load the plugin in `src/Application.php`, inside `bootstrap()` after the parent call:

```php
$this->addPlugin('QrCode', ['routes' => false]);
```

This setup is enough for inline images. Enable routes only when you need the [image endpoints](./endpoints) or [admin generator](./admin).

Register the helper in `src/View/AppView.php`:

```php
public function initialize(): void
{
    parent::initialize();
    $this->addHelper('QrCode.QrCode');
}
```

Continue with [Getting started](./getting-started) to render your first code.
