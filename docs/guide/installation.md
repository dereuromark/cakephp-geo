# Installation

## Using Composer

Installing the plugin via [Composer](https://getcomposer.org/) is as
straightforward as with any other CakePHP plugin. Run this in your project
folder:

```bash
composer require dereuromark/cakephp-geo
```

Details are on [Packagist](https://packagist.org/packages/dereuromark/cakephp-geo).

## Load the plugin

```bash
bin/cake plugin load Geo
```

## Optional packages

Depending on which tools you use (for example geocoding), you might need
additional packages.

For using Google Maps as a concrete adapter, you might also need:

```json
"geocoder-php/provider-implementation": "^1.0",
"geocoder-php/google-maps-provider": "^4.4.0",
```

Alternative compatible packages can be found on
[Packagist](https://packagist.org/providers/geocoder-php/provider-implementation).

::: tip
Most providers need an API key to work. See the
[Geocoder behavior](/behavior/) and [Geocoder class](/geocoder/) pages for how
to select and configure a provider.
:::

## Database setup

Some features — such as the [GeocodedAddresses cache](/model/) — rely on a
database table. Set it up with the
[official migrations plugin for CakePHP](https://github.com/cakephp/migrations):

```bash
bin/cake migrations migrate -p Geo
```

## Next steps

- [Geocoder behavior](/behavior/) — geocode entity data on save.
- [Map helpers](/helpers/google-map) — render maps in your views.
- [GeocodedAddresses cache](/model/) — cache geocoding API calls.
