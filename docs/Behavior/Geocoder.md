# Geocoder Behavior

## Pagination and distance

We want to find all addresses within a distance of 200 km of the given lat/lng:
```php
$options = ['lat' => 13.3, 'lng' => 19.2, 'distance' => 200];

$query = $this->Addresses->find('distance', $options);
$query->order(['distance' => 'ASC']);

$res = $this->Controller->paginate($query)
```
They will be ordered by `['distance' => 'ASC']`, so the smallest distances first.
