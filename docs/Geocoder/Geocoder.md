# Geocoder Class

Note: This class is very low level.
If you want to geocode addresses on save(), you should look into the behavior instead.

## Basic Usage

```php
use Geo\Geocoder\Geocoder;

$Geocoder = new Geocoder(['allowInconclusive' => true, 'minAccuracy' => Geocoder::TYPE_POSTAL]);
$addresses = $Geocoder->geocode($address);

if (!empty($addresses)) {
   $address = $addresses->first();
}
```

Since it is using GoogleMaps geocoding service underneath by default, make sure to set the corresponding app config:
```php
    'Geocoder' => [
        'apiKey' => '...',
    ],
```

## Reverse Geocoding

```php
$result = $Geocoder->reverse($lat, $lng);
```
