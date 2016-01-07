# Geocoder Behavior

## Adding the behavior

Either in your Table class or at runtime:
```php
$this->addBehavior('Geo.Geocoder', $config);
```

Possible config options are:
- address: (array|string, optional) set to the field name that contains the string from where to generate the slug, or a set of field names to concatenate for generating the slug.
- real: (boolean, optional) if set to true then field names defined in address must exist in the database table. defaults to false
- expect: (array)postal_code, locality, sublocality, ...
- min_accuracy: see above
- overwrite: lat/lng overwrite on changes, defaults to false
- update: what fields to update (key=>value array pairs)
- on: beforeMarshall/beforeSave (defaults to save) - Set to false if you only want to use the validation rules etc
- unit: defaults to km

## Saving geocodable data

Storing lat/lng on save() is automatically done when the `address` field is defined and found when saving.
```php
// $address contains address with value `Berlin`
$this->Addresses->save($address);

// These should be both set now
$lat = $address->lat;
$lng = $address->lng;
```

You can always manually call `geocode` as well, of course:
```php
$address = $this->Addresses->get($id);
// $address contains address with value `Berlin`
$this->Addresses->geocode($address);

// These should be both set now
$lat = $address->lat;
$lng = $address->lng;
```

## Pagination and distance

We want to find all addresses within a distance of 200 km of the given lat/lng:
```php
$options = ['lat' => 13.3, 'lng' => 19.2, 'distance' => 200];

$query = $this->Addresses->find('distance', $options);
$query->order(['distance' => 'ASC']);

$res = $this->Controller->paginate($query)
```
They will be ordered by `['distance' => 'ASC']`, so all records with the smallest distances first.

Note that you need to first geocode all your data. On the fly geocoding is not an option for pagination and larger data-sets.

## Batch geocoding

You can look into the [Tools.Reset behavior](https://github.com/dereuromark/cakephp-tools/blob/master/src/Model/Behavior/ResetBehavior.php) and wrap the following in a CakePHP Shell command:
```php
// Geocoder behavior must already be added to the Table class object
$addressTable->addBehavior('Tools.Reset', $config);
$addressTable->resetRecords();
```
This will loop over all records as a batch script and update all lat/lng values.
You should set a scope for performance reasons. Maybe update only every record older than x months, or those that have lat/lng values of `null` to avoid
re-geocoding.

## Validate lat/lng

The methods `validateLatitude()` and `validateLongitude()` can be used to validate the range of those input values in your validation rules.
Don't forget to set `'provider' => 'table'` in this case.
