# Google Map Helper

Using the Google Maps
[API V3](https://developers.google.com/maps/documentation/javascript/reference/3.exp/).

## Adding the helper

Either in your View class or at runtime:

```php
$config = [
    'autoScript' => true,
];
$this->loadHelper('Geo.GoogleMap', $config);
```

Required (global) config:

- `key`

You can configure this globally using Configure (for example
`config/app_local.php`):

```php
'GoogleMap' => [
    'key' => 'your-api-key-here',
],
```

::: tip
Use a non-committed config file (hence the `_local` suffix) — keys and passwords
should not be version controlled.
:::

Possible config options are:

- `api` — uses v3 currently.
- `zoom` — uses the `defaultZoom` of 5 otherwise.
- `type` — defaults to roadmap.
- `block` — defaults to `true`, appending the generated JS to the `script`
  block.
- `https` — leave empty for auto-detect.
- `map` — multiple map options.
- `staticMap` — multiple static-map options.
- `div` — multiple div options.
- `marker` — multiple marker options.
- `infoWindow` — multiple info-window options.
- `directions` — multiple directions options.
- `language`
- `geolocate`
- `libraries` — Google Maps API libraries to load (for example `places` or
  `['places', 'geometry']`).

## Display a basic link to a map

```php
$link = $this->GoogleMap->mapLink('<To Munich>!', ['to' => '<Munich>, Germany']);
// Generates: <a href="https://maps.google.com/maps?daddr=%3CMunich%3E%2C+Germany">&lt;To Munich&gt;!</a>
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
        'path' => [['lat' => '48.1', 'lng' => '11.1'], ['lat' => '48.4', 'lng' => '11.2']],
        'color' => 'red',
        'weight' => 10,
    ],
];

$options = [
    'paths' => $this->GoogleMap->staticPaths($paths),
];
$map = $this->GoogleMap->staticMap($options);
```

### Adding markers

```php
$addresses = [
    [
        'address' => '44.3,11.2',
    ],
    [
        'address' => '44.2,11.1',
    ],
];
$options = ['color' => 'red', 'char' => 'C', 'shadow' => 'false'];

$markers = $this->GoogleMap->staticMarkers($addresses, $options);

$options = [
    'markers' => $markers,
];
$map = $this->GoogleMap->staticMap($options);
```

### Display a static-map link

If you want to use JS to pop up the image:

```php
$config = [
    'markers' => $mapMarkers,
    'escape' => false,
];
$url = $this->GoogleMap->staticMapUrl($config);
echo $this->Html->link(__('Map'), $url, ['title' => __('Map')]);
```

::: warning
Set `'escape' => false` here to avoid double-encoding — the HtmlHelper already
does the encoding.
:::

## Display a basic dynamic map

Make sure you either loaded your helper with `autoScript` enabled, or you
manually add the `apiUrl()` to your scripts.

```php
$options = [
    'zoom' => 6,
    'type' => 'R',
    'geolocate' => true,
    'div' => ['id' => 'someothers'],
    'map' => ['navOptions' => ['style' => 'SMALL'], 'typeOptions' => ['style' => 'HORIZONTAL_BAR', 'pos' => 'RIGHT_CENTER']],
];
$map = $this->GoogleMap->map($options);

// You can echo it now anywhere; it does not matter if you add markers afterwards
echo $map;

// Let's add some markers
$this->GoogleMap->addMarker(['lat' => 48.69847, 'lng' => 10.9514, 'title' => 'Marker', 'content' => 'Some Html-<b>Content</b>', 'icon' => $this->GoogleMap->iconSet('green', 'E')]);

$this->GoogleMap->addMarker(['lat' => 47.69847, 'lng' => 11.9514, 'title' => 'Marker2', 'content' => 'Some more Html-<b>Content</b>']);

$this->GoogleMap->addMarker(['lat' => 47.19847, 'lng' => 11.1514, 'title' => 'Marker3']);

// Store the final JS in an HtmlHelper script block
$this->GoogleMap->finalize();
```

Don't forget to output the buffered JS at the end of your page, where the other
files are included (after all JS files):

```php
echo $this->fetch('script');
```

This snippet is usually already in your `layout.php` at the end of the body tag.

### Inline JS

If you need inline JS instead, call `script()` instead of `finalize()`:

```php
// Initialize
$map = $this->GoogleMap->map();

// Add markers and stuff
// $this->GoogleMap->...

// Finalize
$map .= $this->GoogleMap->script();

// Output both together
echo $map;
```

In general it is advised to defer JS execution by putting it at the end of the
HTML (body tag), though.

### Custom JS

With `->addCustom($js)` you can inject any custom JS to work alongside the Google
Map helper code.

## Places autocomplete

The helper supports Google Places Autocomplete for address input fields. This
creates an autocomplete-enabled input with hidden fields to store the selected
place's latitude and longitude.

### Basic usage

First, load the helper with the `places` library:

```php
$this->loadHelper('Geo.GoogleMap', [
    'key' => 'your-api-key',
    'libraries' => 'places',
    'autoScript' => true,
]);
```

Then use the `placesAutocomplete()` method in your form:

```php
echo $this->GoogleMap->placesAutocomplete('location');
```

This generates:

- A text input for the autocomplete.
- Hidden fields `location_lat` and `location_lng` that are populated
  automatically when a place is selected.

### Custom options

You can customize the field and autocomplete behavior:

```php
echo $this->GoogleMap->placesAutocomplete('address', [
    'field' => [
        'label' => 'Enter your address',
        'class' => 'form-control',
        'placeholder' => 'Start typing...',
    ],
    'lat' => '_latitude',  // Custom suffix for the lat field
    'lng' => '_longitude', // Custom suffix for the lng field
    'autocomplete' => [
        'types' => ['geocode'],
        'componentRestrictions' => ['country' => 'de'],
    ],
]);
```

### Custom callbacks

You can add custom JavaScript to execute when a place is selected:

```php
echo $this->GoogleMap->placesAutocomplete('location', [
    'callbacks' => [
        'placeChanged' => 'console.log("Selected: " + place.formatted_address);',
    ],
]);
```

The callback has access to these variables:

- `place` — the selected
  [PlaceResult](https://developers.google.com/maps/documentation/javascript/reference/places-service#PlaceResult)
  object.
- `autocomplete` — the Autocomplete instance.
- `inputElement` — the input DOM element.
- `latField` — the latitude hidden field.
- `lngField` — the longitude hidden field.

## See also

- [Leaflet helper](./leaflet) — an open-source map alternative.
- [Static map helper](./static-map) — JavaScript-free static map images.
