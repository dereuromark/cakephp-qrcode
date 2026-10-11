# Getting started

Complete [Installation](./installation), then put this in a template rendered by your application:

```php
<?= $this->QrCode->image('https://example.com/about', ['margin' => 4]) ?>
```

The helper returns an HTML image with an SVG data URI. It generates the code during template rendering, so the browser needs no second image request.
A positive `margin` enables a quiet zone around the code; its default width is four modules.
Keep that space clear when adding styles or printing the image.

Replace the sample URL with your destination. For an application route, build the absolute URL first:

```php
$url = $this->Url->build([
    'plugin' => null,
    'controller' => 'Pages',
    'action' => 'display',
    'about',
], ['fullBase' => true]);
echo $this->QrCode->image($url, ['margin' => 4]);
```

Use a route that exists in your application and configure its public base URL correctly.
The payload is encoded as supplied; `image()` does not turn relative text into an absolute URL.

For WiFi, format the payload before rendering:

```php
$formatter = new \QrCode\Utility\Formatter();
$payload = $formatter->formatWifi('Guest network', 'example-password');
echo $this->QrCode->image($payload, ['margin' => 4]);
```

Scan the result with the devices your users will use, especially after resizing it, printing it or adding a logo.
See [Helper options](../reference/helper) and [Payload formatters](../reference/formatters) for the next steps.
