# Geo Class

## Center calculation

```php
$geo = new \Geo\Geocoder\GeoCalculator();
$coordinates = [
    new GeoCoordinate(48.1, 17.2),
    new GeoCoordinate(48.5, 17.1),
    new GeoCoordinate(48.8, 16.8),
];
$centralCoordinate = $geo->getCentralGeoCoordinate(array $coordinates);
```
