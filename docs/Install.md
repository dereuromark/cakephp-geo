# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

```
composer require dereuromark/cakephp-geo
```
Details @ https://packagist.org/packages/dereuromark/cakephp-geo

Then load the plugin:
```
bin/cake plugin load Geo
```

## Optional packages

Depending on what tools you use (e.g. geocoding), you might need additional packages.
For using GoogleMaps as concrete adapter, you might also need for example:

        "geocoder-php/provider-implementation": "^1.0",
        "geocoder-php/google-maps-provider": "^4.4.0",

Alternative packages that are compatible can be found on [Packagist](https://packagist.org/providers/geocoder-php/provider-implementation).
