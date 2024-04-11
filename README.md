#  CakePHP QrCode Plugin

[![CI](https://github.com/dereuromark/cakephp-qrcode/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-qrcode/actions?query=workflow%3ACI+branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-qrcode/master.svg)](https://app.codecov.io/github/dereuromark/cakephp-qrcode/tree/master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-qrcode/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-qrcode)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-qrcode/license.png)](https://packagist.org/packages/dereuromark/cakephp-qrcode)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-qrcode/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-qrcode)

QrCode plugin for CakePHP applications.

This branch is for use with **CakePHP 5.0+**. For details see [version map](https://github.com/dereuromark/cakephp-qrcode/wiki#cakephp-version-map).

## Motivation

Wraps [chillerlan/php-qrcode/](https://github.com/chillerlan/php-qrcode/) library to have an easy to use
out-of-the-box interface for most common QR codes.

## Features

Supports:
- base64encoded (default)
- svg/png as controller action generated on-the-fly image

## Install/Setup
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

### Usage
See the **[Docs](docs/README.md)** for details.

## Demo
See https://sandbox.dereuromark.de/sandbox/qr-code-examples
