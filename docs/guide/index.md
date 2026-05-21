# Overview

The **Geo** plugin provides geocoding tools, distance queries, and map helpers
for CakePHP. It lets you:

- Geocode locations and IPs and store the resulting coordinates (lat/lng)
  alongside your records.
- Reverse-geocode coordinates back into address data.
- Query geocoded data by distance using custom finders.
- Display dynamic Google Maps and open-source Leaflet maps.
- Render JavaScript-free static maps from multiple providers (Geoapify, Mapbox,
  Stadia, Google).

::: info CakePHP version
This documentation is for the branch that supports **CakePHP 5.1+**. See the
[version map](https://github.com/dereuromark/cakephp-geo/wiki#cakephp-version-map)
for older releases.
:::

## How it works

Under the hood the plugin uses the
[willdurand/geocoder](https://github.com/geocoder-php/Geocoder) library, so it
supports a wide range of providers:

- 12+ address-based geocoder providers
- 10+ IP-based geocoder providers

Most of them also support reverse geocoding, and you can always write your own
provider on top.

The plugin works across the common database engines:

- MySQL
- PostgreSQL
- SQLite (handy for quick local testing)

## The pieces

| Concept | Responsibility |
|---------|----------------|
| [Geocoder behavior](/behavior/) | Geocodes entity address fields on save and adds distance finders to your table. |
| [Geocoder class](/geocoder/) | Low-level geocoding and reverse geocoding against a configurable provider. |
| [Calculator](/geocoder/calculator) / [Geo calculator](/geocoder/geo-calculator) | Distance math, coordinate blurring, unit conversion, and central-point calculation. |
| [GeoJSON value objects](/geometry/) | Lightweight `Point`, `Polygon`, `Feature`, and `FeatureCollection` types. |
| [Map helpers](/helpers/google-map) | Google Maps, Leaflet, and static-map rendering for your views. |
| [GeocodedAddresses cache](/model/) | Optional table that caches geocoding API calls to avoid rate limits. |

## Demo

See the [Sandbox examples](https://sandbox.dereuromark.de/sandbox/geo-examples)
for live demos of the map helpers and the Geocoder behavior.

## Next steps

- [Installation](./installation) — install the plugin and load it.
- [Geocoder behavior](/behavior/) — geocode your entity data automatically.
- [Map helpers](/helpers/google-map) — render maps in your views.
