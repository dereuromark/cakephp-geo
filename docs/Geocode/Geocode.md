# Geocode Class

Note: This class is very low level.
If you want to geocode addresses on save(), you should look into the behavior instead.

## Basic Usage

```php
$this->Geocode->setOptions(['allow_inconclusive' => true, 'min_accuracy' => Geocode::ACC_POSTAL]);
$result = $this->Geocode->geocode($address);

// Optionally, e.g. when the above fails because of min accuracy but the data was fetched successfully
$result = $this->Geocode->getResult();
// And more debug info
$debug = $this->Geocode->debug();
$error = $this->Geocode->error();
```

## Reverse Geocoding

```php
$result = $this->Geocode->reverseGeocode($lat, $lng);
```

## Distance calculation

```php
$distance = this->Geocode->distance($lat, $lng);
```

## Blur coordinates

```php
$distance = this->Geocode->blur($lat, $lng);
```

## Convert distances

```php
$newDistance = this->Geocode->convert($oldDistance, $from, $to);
```
