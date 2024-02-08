# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

```
composer require dereuromark/cakephp-geo
```
Details @ https://packagist.org/packages/dereuromark/cakephp-geo


This will load the plugin (within your bootstrap file):
```php
Plugin::load('Geo');
```
or
```php
Plugin::loadAll(...);
```
There is a handy shell command you can use to automatically add the load snippet to your bootstrap:
```
bin/cake plugin load Geo
```

In case you want the Geo plugin bootstrap file included (recommended), you can do that in your `ROOT/config/bootstrap.php` with

```php
Plugin::load('Geo', ['bootstrap' => true]);
```

or

```php
Plugin::loadAll([
        'Geo' => ['bootstrap' => true]
]);
```

## Optional packages

Depending on what tools you use (e.g. geocoding), you might need additional packages.

For using GoogleMaps as concrete adapter, you might also need for example:

        "geocoder-php/provider-implementation": "^1.0",
        "geocoder-php/google-maps-provider": "^4.4.0",

Alternative packages that are compatible can be found on [Packagist](https://packagist.org/providers/geocoder-php/provider-implementation).
