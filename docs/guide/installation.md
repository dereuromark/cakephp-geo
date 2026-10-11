# Installation

For this development branch, the application's root `composer.json` must allow development dependencies with `"minimum-stability": "dev"`. Set `"prefer-stable": true` to prefer stable packages when their constraints permit it.

Install the plugin in your application:

```sh
composer require dereuromark/cakephp-geo:dev-cake6
```

[Composer](https://github.com/dereuromark/cakephp-geo/blob/cake6/composer.json) defines supported PHP and CakePHP versions.

## Load the plugin

In `src/Application.php`, inside `bootstrap()` after `parent::bootstrap()`:

```php
$this->addPlugin('Geo', ['routes' => false]);
```

This loads plugin bootstrap configuration while leaving the admin backend disabled. Map helpers and coordinate calculations do not need admin routes or a database migration.

## Optional geocoding packages

Address lookup needs a provider package in addition to the plugin. Select a package matching your provider. For Google:

```sh
composer require geocoder-php/google-maps-provider
```

For Nominatim:

```sh
composer require geocoder-php/nominatim-provider
```

These packages install their geocoder dependencies. `geocoder-php/provider-implementation` is a virtual package fulfilled by concrete providers; it is not an adapter you instantiate.

The built-in Geoapify and Null providers use geocoder interfaces and address models. If using them without another provider package, install the core library:

```sh
composer require willdurand/geocoder
```

Configure the chosen provider in the [Geocoder reference](/geocoder/). Google and Geoapify need credentials. For the public Nominatim service, use an identifying User-Agent and follow its [usage policy](https://operations.osmfoundation.org/policies/nominatim/).

## Optional database cache

Only caching and the admin cache interface need the plugin table. With CakePHP Migrations installed and loaded, run:

```sh
bin/cake migrations migrate -p Geo
```

The cache's `data` column uses JSON. Normal plugin bootstrap also registers the legacy `object` type, but the current cache does not require an additional manual type mapping.

## Next step

[Getting started](./getting-started) renders a Leaflet map with fixed coordinates. Add geocoding or caching once that works.

