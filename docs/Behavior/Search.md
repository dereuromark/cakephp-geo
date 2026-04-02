# Geocoder and Search plugin

This is useful if you provide geocoding searches (by distance) in your pagination views or any listing for that matter.
The plugin used here is [friendsofcake/search](https://github.com/FriendsOfCake/search).

## Basic Usage

It is wise to cache the API results in the provided `GeocodedAddresses` Table for performance and API rate limit reasons.
Just make sure you added the table via Migrations plugin.

If we have a search form with a field `locality_search`, we can easily add some filters here for it:

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

The table name is only relevant if the geocoding fields is on a belongsTo relation (here Participants belongsTo Event).
