# GeocodedAddresses cache

The optional `Geo.GeocodedAddresses` table stores a result for each address string. The current cache is keyed by that string, not by provider, locale, or accuracy settings. Clear affected records after changing those settings.

## Setup

Run the migration from [Installation](/guide/installation#optional-database-cache), then configure a [Geocoder provider](/geocoder/). The `data` column stores JSON; it does not need a custom object type.

## Retrieve an address

```php
use Cake\ORM\TableRegistry;

$addresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
$address = $addresses->retrieve('Vienna, Austria');
if ($address && $address->lat !== null && $address->lng !== null) {
    $coordinates = ['lat' => $address->lat, 'lng' => $address->lng];
}
```

Zero is a valid coordinate. Test for `null` rather than a truthy latitude or longitude.

`retrieve()` reuses a saved row, including an empty result. New successful lookups store coordinates, country code, formatted address, and provider result data. It returns the saved entity or `false` if saving fails. This cache has no automatic expiry or background refresh.

## Behavior caching

Set `cache` to `true` when adding `Geo.Geocoder` to your table. The behavior also stores misses as `['_miss' => true]` and avoids repeating those lookups. With caching disabled, the behavior does not load the cache table.

`clearEmpty()` deletes rows without a formatted address. `clearAll()` deletes every cache row. Protect these operations and the admin interface through your application access rules.

For distance filtering with a cached lookup, see [Search integration](/behavior/search).
