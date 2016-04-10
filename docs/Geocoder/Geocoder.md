# Geocoder Class

Note: This class is very low level.
If you want to geocode addresses on save(), you should look into the behavior instead.

## Basic Usage

```php
$this->Geocoder->setOptions(['allowInconclusive' => true, 'minAccuracy' => Geocoder::TYPE_POSTAL]);
$addresses = $this->Geocoder->geocode($address);

if (!empty($addresses)) {
   $address = $addresses->first();
}
```

## Reverse Geocoding

```php
$result = $this->Geocode->reverse($lat, $lng);
```
