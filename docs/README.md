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

```php
$text = $this->Qrcode->formatter()->formatText($text);
$geo = $this->Qrcode->formatter()->formatGeo($lat, $lng);
$sms = $this->Qrcode->formatter()->formatSms($number, $text);
...

echo $this->QrCode->image(...);
```
It will help to normalize user input coming from forms or alike.

### Controller rendering
If you want more control over the image, as well as type (png, svg), you
can also let the controller action render it and use it as a generated
on-the-fly image.

```php
echo $this->QrCode->svg($content, $options);
// or
echo $this->QrCode->png($content, $options);
```

### Advanced usage

See also https://php-qrcode.readthedocs.io/en/v5.0.x/Usage/Advanced-usage.html

## Admin Backend
Go to `/admin/qr-code`.

Make sure you set up ACL to only have admins access this part.
