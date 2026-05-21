# Search Integration

This is useful if you provide geocoding searches (by distance) in your
pagination views or any other listing. The plugin used here is
[friendsofcake/search](https://github.com/FriendsOfCake/search).

## Basic usage

It is wise to cache the API results in the provided
[`GeocodedAddresses`](/model/) table for performance and API rate-limit reasons.
Just make sure you added the table via the Migrations plugin.

If you have a search form with a field `locality_search`, you can add a filter
for it:

```php
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

->callback('locality_search', [
    'callback' => function (SelectQuery $query, array $args, $filter): bool {
        if (empty($args['locality_search'])) {
            return false;
        }
        $GeocodedAddresses = TableRegistry::getTableLocator()->get('Geo.GeocodedAddresses');
        $address = $GeocodedAddresses->retrieve($args['locality_search']);
        if ($address && $address->lat && $address->lng) {
            $query->find('distance', ...[
                'lat' => $address->lat,
                'lng' => $address->lng,
                'tableName' => 'Events',
                'distance' => 100,
                'sort' => false,
            ]);

            return true;
        }

        return false;
    },
]);
```

The `tableName` is only relevant if the geocoding fields live on a `belongsTo`
relation (here `Participants belongsTo Event`).

::: tip
Callback filters must return a `bool` — modify the query in place and return
`true` when the filter was applied, `false` otherwise.
:::

## See also

- [Geocoder behavior](/behavior/) — the distance finder used above.
- [GeocodedAddresses cache](/model/) — the `retrieve()` lookup used here.
