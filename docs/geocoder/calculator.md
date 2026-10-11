# Calculator

`Geo\Geocoder\Calculator` provides coordinate distance calculations and unit conversion. These operations do not make external requests.

## Distance

```php
use Geo\Geocoder\Calculator;

$calculator = new Calculator();
$vienna = ['lat' => 48.2082, 'lng' => 16.3738];
$berlin = ['lat' => 52.5200, 'lng' => 13.4050];
$kilometers = $calculator->distance($vienna, $berlin);
$miles = $calculator->distance($vienna, $berlin, Calculator::UNIT_MILES);
```

The result is rounded up to an integer. The default unit is kilometers; pass another unit explicitly when needed. Both points use `lat` and `lng` keys.

## Blur coordinates

```php
$blurredLatitude = Calculator::blur(48.2082, 2);
```

Blurring adds random displacement according to the selected level. It does not establish anonymity or guarantee a particular privacy boundary. Check whether the displacement is suitable for your application.

## Convert units

```php
$kilometers = $calculator->convert(10.0, Calculator::UNIT_MILES, Calculator::UNIT_KM);
```

Supported constants include kilometers, miles, nautical miles, feet, and inches. Unsupported units throw a calculator exception.

For the center of multiple coordinates, see [Geo Calculator](./geo-calculator).
