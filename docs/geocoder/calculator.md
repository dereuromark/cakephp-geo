# Calculator

The `Calculator` class provides distance math and coordinate utilities.

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

The idea is to protect the user's exact coordinates by blurring them to a
certain degree.

```php
$calculator = new \Geo\Geocoder\Calculator();
$blurred = $calculator->blur($float, 2);
```

## Convert distances

```php
$calculator = new \Geo\Geocoder\Calculator();
$newDistance = $calculator->convert($value, $fromUnit, $toUnit);
```

## See also

- [Geo calculator](./geo-calculator) — central-point calculation across many coordinates.
