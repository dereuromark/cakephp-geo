# GeocodedAddresses Cache

This is an optional part of the plugin that lets you internally cache the API
calls to your geocoding provider.

The main benefits are:

- Caching prevents reaching API rate limits too quickly.
- Faster (because internal) results on follow-up searches.
- A history of all calls.
- Statistical operations on the collected data become possible.

## Basic usage

You need to enable the `Geo.GeocodedAddresses` table. Make sure you added the
table via the Migrations plugin:

```bash
bin/cake migrations migrate -p Geo
```

Then, instead of using the [Geocoder class](/geocoder/) directly, go through this
table:

```php
$GeocodedAddresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
$address = $GeocodedAddresses->retrieve($args['locality_search']);
if ($address && $address->lat && $address->lng) {
    // Do something with it
}
```

Remember to add the type mapping of `Geo\Database\Type\ObjectType` in your
`bootstrap.php`:

```php
TypeFactory::map('object', 'Geo\Database\Type\ObjectType');
```

See the
[cookbook](https://book.cakephp.org/5/en/orm/database-basics.html#adding-custom-types)
for details on custom types.

::: tip
If you need a more complex solution, you can also manually combine the
[Geocoder class](/geocoder/) and the `GeocodedAddresses` table.
:::

## Search usage

If you use the [Search](https://github.com/FriendsOfCake/search) plugin, the
following callback might be handy:

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

## See also

- [Search integration](/behavior/search) — a fuller search example.
- [Geocoder behavior](/behavior/) — the distance finder used above.
