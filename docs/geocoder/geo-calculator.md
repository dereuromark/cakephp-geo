# Geo Calculator

The `GeoCalculator` class works with sets of coordinates.

## Center calculation

Find the central point of several coordinates:

```php
use Geo\Geocoder\GeoCoordinate;

$geo = new \Geo\Geocoder\GeoCalculator();
$coordinates = [
    new GeoCoordinate(48.1, 17.2),
    new GeoCoordinate(48.5, 17.1),
    new GeoCoordinate(48.8, 16.8),
];
$centralCoordinate = $geo->getCentralGeoCoordinate($coordinates);
```

## See also

- [Calculator](./calculator) — distance math and coordinate utilities.
