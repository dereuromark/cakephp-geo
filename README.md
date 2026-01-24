# CakePHP Geo Plugin

[![CI](https://github.com/dereuromark/cakephp-geo/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-geo/actions/workflows/ci.yml?query=branch%3Amaster)
[![codecov](https://codecov.io/gh/dereuromark/cakephp-geo/branch/master/graph/badge.svg)](https://codecov.io/gh/dereuromark/cakephp-geo)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-geo/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-geo/license.svg)](LICENSE)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-geo/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)

A CakePHP plugin to
- geocode locations/ips and save the information (lat/lng) along with the records
- reverse geocode data
- querying geocoded data by distance (using custom finder)
- display Google maps (dynamic and static)
- display Leaflet maps (open-source alternative)

This branch is for **CakePHP 5.1+**. See [version map](https://github.com/dereuromark/cakephp-geo/wiki#cakephp-version-map) for details.

Note that it uses the [willdurand/geocoder](https://github.com/geocoder-php/Geocoder) library and therefore supports
- 12+ address-based Geocoder providers
- 10+ IP-based Geocoder providers

Most of them also support reverse geocoding. And of course you can write your own providers on top.

Also:
- MySQL support
- PostgreSQL support
- SQLite support (for easy local testing)

And also:
- GeocodedAddresses Table class for caching of API requests to prevent rate limits and speed up lookups.
- NullProvider for testing without external API calls.


## Demo
See [Sandbox examples](https://sandbox.dereuromark.de/sandbox/geo-examples) for live demos of the map helpers and the Geocoder behavior.

## Installation & Docs

- [Documentation](docs/README.md)
