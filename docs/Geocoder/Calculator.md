# Calculator Class


## Distance calculation

```php
$calculator = new \Geo\Geocoder\Calculator();
$distance = $calculator->distance(array $pointX, array $pointY);
```

Distance in miles instead:
```php
$calculator = new \Geo\Geocoder\Calculator();
$distance = $calculator->distance(array $pointX, array $pointY, Calculator::UNIT_MILES);
```

## Blur coordinates
The idea is to secure the user's exact coordinates and hide them by blurring them to a certain degree.

```php
$calculator = new \Geo\Geocoder\Calculator();
$distance = $calculator->blur($float, 2);
```

## Convert distances

```php
$calculator = new \Geo\Geocoder\Calculator();
$newDistance = $calculator->convert($value, $fromUnit, $toUnit);
```
