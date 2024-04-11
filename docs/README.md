# QrCode Plugin docs

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Install using Packagist/Composer:
```
composer require dereuromark/cakephp-qrcode
```

The following command can enable the plugin:
```
bin/cake plugin load QrCode
```
or manually add it to your `Application` class.

## Helper usage

Add the helper in your `AppView` class:
```php
$this->loadHelper('QrCode.QrCode');
```
Then you can use it in your views to display QR codes:
```php
echo $this->QrCode->image($text, $optionalOptions);
```

By default it uses base64encoded images, so no 2nd request is required.

## Controller rendering
If you want more control over the image, as well as type (png, svg), you
can also let the controller action render it and use it as a generated
on-the-fly image.


## Admin Backend
Go to `/admin/qr-code`.

Make sure you set up ACL to only have admins access this part.
