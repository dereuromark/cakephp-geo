# GeocodedAddresses

This is an optional part of the plugin, allowing you to internally cache the
API calls to GoogleMaps.

The main benefits are:
- Caching can prevent reaching API rate limits too fast
- Allows faster (because internal) results on follow up searches
- History of all calls
- Statistical operations on the collected data possible

## Basic Usage

You need to enable `Geo.GeocodedAddresses` Table. Just make sure you added the table via Migrations plugin:
```
bin/cake migrations migrate -p Geo
```

Then instead of using the Geocoder class directly, you go through this Table:
```php
$GeocodedAddresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
$address = $GeocodedAddresses->retrieve($args['locality_search']);
if ($address && $address->lat && $address->lng) {
    // Do something with it
}
```

Remember to add the Type mapping of `Geo\Database\Type\ObjectType` in your bootstrap.php.
```php
TypeFactory::map('object', 'Geo\Database\Type\ObjectType');
```
(see cookbook: https://book.cakephp.org/3.0/en/orm/database-basics.html#adding-custom-types)

If you need more complex solutions, you can also manually put the Geocoder and the GeocodedAddresses Table classes together.

## Search usage
If you use [Search](https://github.com/FriendsOfCake/search) plugin, the following callback might be handy here:

```php
->callback('distance', [
    'callback' => function (Query $query, array $args, Callback $manager) {
        if (!empty($args['location'])) {
            $GeocodedAddresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
            $address = $GeocodedAddresses->retrieve($args['location']);
            if ($address && $address->lat && $address->lng) {
                $query->find('distance', ...['lat' => $address->lat, 'lng' => $address->lng, 'tableName' => 'MyTableName', 'distance' => 100, 'sort' => false]);
            }
        }
    },
])
```
