# CakePHP Geo Plugin

[![CI](https://github.com/dereuromark/cakephp-geo/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-geo/actions/workflows/ci.yml?query=branch%3Amaster)
[![codecov](https://codecov.io/gh/dereuromark/cakephp-geo/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-geo/tree/master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-geo/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-geo/license.svg)](LICENSE)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-geo/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)

A CakePHP plugin to
- geocode locations/ips and save the information (lat/lng) along with the records
- reverse geocode data
- querying geocoded data by distance (using custom finder)
- display Google maps (dynamic and static)
- display Leaflet maps (open-source alternative)
- display static maps from multiple providers (Geoapify, Mapbox, Stadia, Google)

[Composer](composer.json) defines supported PHP and CakePHP versions. Geocoding needs an optional provider package; map helpers and coordinate calculations can be used separately.

Also:
- MySQL support
- PostgreSQL support
- SQLite support (for easy local testing)

And also:
- GeocodedAddresses Table class for caching of API requests to prevent rate limits and speed up lookups.
- NullProvider for testing without external API calls.


## Demo
See [Sandbox examples](https://sandbox.dereuromark.de/sandbox/geo-examples) for live demos of the map helpers and the Geocoder behavior.

## Documentation

Full documentation lives at **[dereuromark.github.io/cakephp-geo](https://dereuromark.github.io/cakephp-geo/)**.

A few good entry points:

* [Installation](https://dereuromark.github.io/cakephp-geo/guide/installation)
* [Getting started](https://dereuromark.github.io/cakephp-geo/guide/getting-started)
* [Geocoder behavior](https://dereuromark.github.io/cakephp-geo/behavior/)
* [Map helpers](https://dereuromark.github.io/cakephp-geo/helpers/google-map)

The Markdown sources live in the [docs](docs/) directory of this repository.
