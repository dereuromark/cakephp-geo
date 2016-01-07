# GoogleMap Helper

## Adding the helper

Either in your View class or at runtime:
```php
$this->loadHelper('Geo.GoogleMap', $config);
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

## Display a basic map
