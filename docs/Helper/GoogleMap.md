# GoogleMap (V3) Helper

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

## Display a basic link to a map
```php
$link = $this->GoogleMap->mapLink('<To Munich>!', ['to' => '<Munich>, Germany']);
// Generates: <a href="http://maps.google.com/maps?daddr=%3CMunich%3E%2C+Germany">&lt;To Munich&gt;!</a>
```

## Display a static map
```php
$paths = [
	[
		'path' => ['Berlin', 'Stuttgart'], // Names
		'color' => 'green',
	],
	[
		'path' => ['44.2,11.1', '43.1,12.2', '44.3,11.3', '43.3,12.3'], // Flat array of coordinates
	],
	[
		'path' => [['lat' => '48.1', 'lng' => '11.1'], ['lat' => '48.4', 'lng' => '11.2']], // = 'Frankfurt'
		'color' => 'red',
		'weight' => 10
	]
];

$options = [
	'paths' => $this->GoogleMap->staticPaths($paths)
];
$map = $this->GoogleMap->staticMap($options);
```

### Adding markers:
```php
$addresses = [
	[
		'address' => '44.3,11.2',
	],
	[
		'address' => '44.2,11.1',
	]
];
$options = ['color' => 'red', 'char' => 'C', 'shadow' => 'false'];

$markers = $this->GoogleMap->staticMarkers($addresses, $options);

$options = [
	'markers' => $markers
];
$map = $this->GoogleMap->staticMap($options);
```

## Display a basic dynamic map
```php
$options = [
	'zoom' => 6,
	'type' => 'R',
	'geolocate' => true,
	'div' => ['id' => 'someothers'],
	'map' => ['navOptions' => ['style' => 'SMALL'], 'typeOptions' => ['style' => 'HORIZONTAL_BAR', 'pos' => 'RIGHT_CENTER']]
];
$map = $this->GoogleMap->map($options);

// You can echo it now anywhere, it does not matter if you add markers afterwards
echo $map;

// Let's add some markers
$this->GoogleMap->addMarker(['lat' => 48.69847, 'lng' => 10.9514, 'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>', 'icon' => $this->GoogleMap->iconSet('green', 'E')]);

$this->GoogleMap->addMarker(['lat' => 47.69847, 'lng' => 11.9514, 'title' => 'Marker2', 'content' => 'Some more Html-<b>Content</b>']);

$this->GoogleMap->addMarker(['lat' => 47.19847, 'lng' => 11.1514, 'title' => 'Marker3']);

// Store the final JS in a HtmlHelper script block
$this->GoogleMap->finalize();
```
Don't forget to output the buffered JS at the end of your page, where also the other files are included (after all JS files are included!):
```html
echo $this->fetch('script');
```

### Inline JS
Maybe you need inline JS instead, then you can call script() instead of finalize() directly:
```php
// Initialize
$map = $this->GoogleMap->map();

// Add markers and stuff
$this->GoogleMap->...

// Finalize
$map .= $this->GoogleMap->script();

// Output both together
echo $map;
```
