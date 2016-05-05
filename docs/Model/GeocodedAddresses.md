# GeocodedAddresses

This is an optional part of the plugin, allowing you to internally cache the
API calls to GoogleMaps.

The main benefits are:
- Caching can prevent reaching API rate limits too fast
- Allows faster (because internal) results on follow up searches
- History of all calls
- Statistical operations on the collected data possible

## Basic Usage

You need to enable `GeocodedAddresses` Table. Just make sure you added the table via Migrations plugin:
```
bin/cake migrations migrate -p Geo
```

Then instead of using the Geocoder class directly, you go through this Table:
```php
$GeocodedAddresses = TableRegistry::get('Geo.GeocodedAddresses');
$address = $GeocodedAddresses->retrieve($args['locality_search']);
if ($address && $address->lat && $address->lng) {
	// Do something with it
}
```
If you need more complex solutions, you can also manually put the Geocoder and the GeocodedAddresses Table classes together.
