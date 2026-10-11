# Geocoder Behavior

Geocode your entity data automatically on save.

## Adding the behavior

For a quick start, see the [optional packages](/guide/installation#optional-geocoding-packages)
install tip.

Either in your Table class or at runtime:

```php
$this->addBehavior('Geo.Geocoder', $config);
```

Possible config options are:

- `apiKey`; mandatory for some providers.
- `locale`; for example `DE`.
- `region`; for some providers.
- `ssl`; for some providers.
- `address`; (array|string, optional) the field name that contains the address
  string, or a set of field names to concatenate.
- `overwrite`; lat/lng overwrite existing coordinates; defaults to `true`.
- `update`; which fields to update (key/value array pairs).
- `on`; `beforeMarshal` / `afterMarshal` / `beforeSave` (defaults to
  `beforeSave`). Set to `false` if you only want to use the validation rules.
- `unit`; defaults to km.
- `allowInconclusive`; `false` to throw an exception.
- `minAccuracy`; one of the `Geocoder::TYPE_*` constants.
- `expect`; (array) `postal_code`, `locality`, `sublocality`, ...
- `addressFormat`; defaults to `'%S %n, %z %L'`.
- `lat` / `lng`; to customize these field names.

::: tip
It is usually better to set global configs in your `app.php` using the
`Geocoder` key.
:::

## Configure your geocoder

By default the behavior uses the Google Maps provider. You can switch providers
using the built-in provider constants.

### Available providers

| Provider | API Key | Notes |
|----------|---------|-------|
| Google Maps | Required | Default |
| Nominatim | No for the public OSM endpoint | Follow the [service usage policy](https://operations.osmfoundation.org/policies/nominatim/) |
| Geoapify | Required | Configure your account credentials |
| Null | No | For testing |

### Using provider constants

Switch to a different provider easily:

```php
use Geo\Geocoder\Geocoder;

// in your app.php config
'Geocoder' => [
    'provider' => Geocoder::PROVIDER_NOMINATIM,
    'nominatim' => [
        'userAgent' => 'MyApp/1.0', // Required by OSM usage policy
    ],
],
```

Or using Geoapify:

```php
use Geo\Geocoder\Geocoder;

'Geocoder' => [
    'provider' => Geocoder::PROVIDER_GEOAPIFY,
    'geoapify' => [
        'apiKey' => env('GEOAPIFY_API_KEY'),
    ],
],
```

### Using a callable (advanced)

For advanced use cases or custom providers from the geocoder-php library, use
`Cake\Http\Client` directly (it implements PSR-18):

```php
// in your app.php config
use Cake\Http\Client;

'Geocoder' => [
    'provider' => function () {
        return \Geocoder\Provider\Nominatim\Nominatim::withOpenStreetMapServer(
            new Client(),
            'MyApp/1.0',
        );
    },
],
```

See the [geocoder-php/Geocoder](https://github.com/geocoder-php/Geocoder)
library for the other providers you can use. You can choose from:

- address-based geocoder providers
- IP-based geocoder providers

::: warning
Most providers need an API key to work.
:::

## Saving geocodable data

Storing lat/lng on `save()` happens automatically when the `address` field is
defined and found when saving:

```php
// $address contains an address with the value `Berlin`
$this->Addresses->save($address);

// These should both be set now
$lat = $address->lat;
$lng = $address->lng;
```

You can always call `geocode()` manually as well:

```php
$address = $this->Addresses->get($id);
// $address contains an address with the value `Berlin`
$this->Addresses->getBehavior('Geocoder')->geocode($address);

// These should both be set now
$lat = $address->lat;
$lng = $address->lng;
```

## Pagination and distance

To find all addresses within a distance of 200 km of a given lat/lng:

```php
// In a controller action
$options = ['lat' => 13.3, 'lng' => 19.2, 'distance' => 200];

$query = $this->Addresses->find('distance', ...$options);
$query->orderByAsc('distance');

$addresses = $this->paginate($query);
```

They are ordered by `['distance' => 'ASC']`, so the records with the smallest
distances come first.

::: warning
You need to geocode all your data first. On-the-fly geocoding is not an option
for pagination and larger datasets. This non-spatial finder uses the configured `unit`.
:::

### Using the coordinates value object

You can also use `coordinates` as a `Geocoder\Model\Coordinates` instance:

```php
use Geocoder\Model\Coordinates;

$coordinates = new Coordinates(13.3, 19.2);
$options = ['coordinates' => $coordinates, 'distance' => 200];

$query = $this->Addresses->find('distance', ...$options);
```

When using the plugin's native `GeoCoordinate` value object:

```php
use Geo\Geocoder\GeoCoordinate;

$geoCoordinate = new GeoCoordinate(13.3, 19.2);
$coordinates = $geoCoordinate->toGeocoderCoordinates();
$options = ['coordinates' => $coordinates, 'distance' => 200];
```

### Address elements as a closure

Sometimes you need more logic for a specific address field. In that case you can
use a closure to do dynamic lookups where needed.

Example: cities and their countries when saving a city (`cities/add` or
`cities/edit/ID`):

```php
$this->addBehavior('Geo.Geocoder', [
    'address' => ['street', 'postal_code', 'city', function (City $entity) {
        // If there is a matching relation
        if ($entity->country && $entity->country->id && $entity->country_id) {
            return $entity->country->name;
        }
        // If there is a virtual or tmp field
        if ($entity->get('country_name')) {
            return $entity->get('country_name');
        }
        // Do an actual DB lookup with the ID given in the form
        if ($entity->country_id) {
            $country = $this->Countries->get($entity->country_id);

            return $country->name;
        }

        return null;
    }],
]);
```

## Batch geocoding

You can use the
[Tools.Reset behavior](https://github.com/dereuromark/cakephp-tools/blob/master/src/Model/Behavior/ResetBehavior.php)
and wrap the following in a CakePHP console command:

```php
// Geocoder behavior must already be added to the Table class object
$addressTable->addBehavior('Tools.Reset', $config);
$addressTable->getBehavior('Reset')->resetRecords();
```

This loops over all records as a batch and updates all lat/lng values.

::: tip
Set a scope for performance reasons; maybe update only records older than a few
months, or those with `null` lat/lng to avoid re-geocoding. Throttle your batch
runs too: most providers only allow a limited number of requests per minute.
Don't over-request, to avoid penalties.
:::

## Validate lat/lng

The methods `validateLatitude()` and `validateLongitude()` can be used to
validate the range of those input values. Register the behavior instance as a
validation provider, as shown in [CakePHP 6 validation providers](#cakephp-6-validation-providers).

## Backend

If you include the routes, you get an admin backend for the geocoding as well as
the stored geocoded addresses (cache):

```
/admin/geo
```

The backend requires the cache migration and provider configuration. Protect the admin prefix through application authentication and authorization; the plugin does not supply these access rules.

## Testing

To avoid API calls in your test suite, use the built-in `NullProvider`, which
returns empty results.

Configure it globally in your test bootstrap:

```php
// tests/bootstrap.php
use Cake\Core\Configure;
use Geo\Geocoder\Provider\NullProvider;

Configure::write('Geocoder.provider', fn () => new NullProvider());
```

This ensures all tests use the `NullProvider` instead of making real API calls.
The behavior handles empty results when `allowEmpty` is `true` (the default). Standalone geocoder calls with the default accuracy threshold can throw for an empty result; disable `minAccuracy` for an explicit empty-result test.

## Providers

See the full list of existing providers
[here](https://github.com/geocoder-php/Geocoder#providers).

## Spatial

You can also use the `spatial` finder, treating coordinates as a POINT instead
of lat/lng:

```php
$query = $this->Addresses->find('spatial', lat: 13.3, lng: 19.2, distance: 100);
```

::: info
The current SQL uses MySQL/MariaDB spatial functions and a column named `coordinates`. It is not compatible with PostGIS: PostgreSQL uses different function names and signatures. Create the geometry column and its index yourself; the plugin migration does not add them. The generated points use SRID 0 with X as longitude and Y as latitude, so the column and stored points must use the same SRID.
:::

The finder uses a bounding-box pre-filter with `ST_Within()` to leverage spatial
indexes, then refines results with `ST_Distance_Sphere()` for accurate distance
calculations. Check the query plan for your data and indexes. The spatial finder reports kilometers regardless of the non-spatial `unit` setting. Its bounding box is an approximation and does not wrap the antimeridian; use another query strategy near that boundary.

## See also

- [Search integration](./search); wire distance searches into FriendsOfCake/search.
- [GeocodedAddresses cache](/model/); cache geocoding API calls.

## CakePHP 6 validation providers

CakePHP 6 no longer delegates arbitrary table methods to behaviors. Call utility methods on `$table->getBehavior('Geocoder')`. Finder calls continue to use `$table->find('distance', ...)`.

Register the behavior directly when using its coordinate validators:

```php
$behavior = $table->getBehavior('Geocoder');
$validator->setProvider('geocoder', $behavior);
$validator->add('lat', 'latitude', [
    'rule' => 'validateLatitude',
    'provider' => 'geocoder',
]);
```

Provider and helper subclasses use `$defaultConfig` and `$config` for the inherited configuration properties. Behavior subclasses access their table through `$table`.
