---
layout: home
hero:
  name: CakePHP QrCode
  text: QR images in your templates
  tagline: Render inline SVG images, format WiFi and contact payloads, or enable image endpoints.
  image:
    src: /logo.svg
    alt: QR code logo
  actions:
    - theme: brand
      text: Installation
      link: /guide/installation
    - theme: alt
      text: Getting started
      link: /guide/getting-started
    - theme: alt
      text: QR library docs
      link: https://php-qrcode.readthedocs.io/en/v6.0.x/
features:
  - title: Inline images
    details: Generate an SVG data URI without an extra image request or database setup.
  - title: Payload formatters
    details: Build WiFi credentials, contact cards, SMS and geographic links.
  - title: Optional endpoints
    details: Serve SVG or PNG responses with conditional HTTP caching.
---

The plugin wraps [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode).
Use this guide for CakePHP integration and the library manual for advanced output settings.
Composer can install library version 5 or 6; select the [v5 manual](https://php-qrcode.readthedocs.io/en/v5.0.x/) when that version is installed.
