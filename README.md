## Laravel service provider for PHP Console

[![Latest Stable Version](https://poser.pugx.org/php-console/laravel-service-provider/version.png)](https://packagist.org/packages/php-console/laravel-service-provider)

PHP Console allows you to handle PHP errors & exceptions, dump variables, execute PHP code remotely and many other things using [Google Chrome extension PHP Console](https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef) and [PhpConsole server library](https://github.com/barbushin/php-console).

This packages integrates [PHP Console server library](https://github.com/barbushin/php-console) with [Laravel framework](http://laravel.com) as configurable service provider.

## Installation

Require this package in Laravel project `composer.json` and run `composer update`:

    "php-console/laravel-service-provider": "1.*"

After updating composer, add the ServiceProvider to the providers array in app/config/app.php

    'PhpConsole\Laravel\ServiceProvider',

You can also publish the service provider config-file and feel free to edit it.

    php artisan config:publish php-console/laravel-service-provider