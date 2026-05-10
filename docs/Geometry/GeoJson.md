# GeoJSON Geometry

The plugin now provides lightweight GeoJSON value objects you can use instead of passing raw arrays everywhere.

Available classes:
- `Geo\Geometry\Point`
- `Geo\Geometry\Polygon`
- `Geo\Geometry\Feature`
- `Geo\Geometry\FeatureCollection`

## Point

```php
use Geo\Geometry\Point;

$point = new Point(16.3738, 48.2082); // lng, lat

$point->toGeoJsonArray();
// ['type' => 'Point', 'coordinates' => [16.3738, 48.2082]]

$point->toLatLngArray();
// ['lat' => 48.2082, 'lng' => 16.3738]
```

You can also construct it from the more common Leaflet-style ordering:

```php
$point = Point::fromLatLng(48.2082, 16.3738);
```

## Polygon

GeoJSON uses `[lng, lat]` coordinate pairs and supports one outer ring plus optional inner rings (holes).

```php
use Geo\Geometry\Polygon;

$polygon = new Polygon([
    [
        [16.3, 48.2],
        [16.4, 48.3],
        [16.5, 48.1],
        [16.3, 48.2],
    ],
]);
```

If your application already works with `lat`/`lng` arrays, use the convenience constructor:

```php
$polygon = Polygon::fromLatLngPoints([
    ['lat' => 48.2, 'lng' => 16.3],
    ['lat' => 48.3, 'lng' => 16.4],
    ['lat' => 48.1, 'lng' => 16.5],
]);
```

That helper automatically closes the outer ring if the last point is missing.

## Feature / FeatureCollection

```php
use Geo\Geometry\Feature;
use Geo\Geometry\FeatureCollection;
use Geo\Geometry\Point;

$collection = new FeatureCollection([
    new Feature(new Point(16.3738, 48.2082), ['name' => 'Vienna']),
]);
```

## Using the objects with Leaflet

`LeafletHelper::addGeoJson()` now accepts:
- raw GeoJSON arrays
- GeoJSON strings
- any `GeoJsonInterface` implementation

```php
$this->Leaflet->addGeoJson($collection);
```

`LeafletHelper::addPolygon()` also accepts a `Polygon` object directly:

```php
$this->Leaflet->addPolygon($polygon, [
    'color' => '#ff0000',
    'fillOpacity' => 0.4,
]);
```
