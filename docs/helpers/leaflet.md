# Leaflet Helper

Using [Leaflet.js](https://leafletjs.com/) — an open-source JavaScript library
for interactive maps.

## Adding the helper

Either in your View class or at runtime:

```php
$config = [
    'autoScript' => true,
];
$this->loadHelper('Geo.Leaflet', $config);
```

You can configure this globally using Configure (for example `config/app.php`):

```php
'Leaflet' => [
    'zoom' => 10,
    'lat' => 48.2082,
    'lng' => 16.3738,
],
```

Possible config options are:

- `zoom` — uses the `defaultZoom` of 5 otherwise.
- `lat` / `lng` — default center coordinates.
- `block` — defaults to `true`, appending the generated JS to the `script`
  block.
- `map` — multiple map options (`scrollWheelZoom`, `zoomControl`, `dragging`).
- `tileLayer` — tile-layer URL and options.
- `div` — multiple div options (`id`, `width`, `height`, `class`).
- `marker` — multiple marker options.
- `popup` — multiple popup options.
- `polyline` — polyline styling options.
- `circle` — circle styling options.
- `polygon` — polygon styling options.
- `autoCenter` — automatically fit bounds to all markers.
- `autoScript` — auto-include Leaflet JS/CSS from a CDN.

## Display a basic dynamic map

Make sure you either loaded your helper with `autoScript` enabled, or you
manually include the Leaflet CSS and JS files.

```php
$options = [
    'zoom' => 13,
    'lat' => 48.2082,
    'lng' => 16.3738,
];
$map = $this->Leaflet->map($options);

// You can echo it now anywhere; it does not matter if you add markers afterwards
echo $map;

// Let's add some markers
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'title' => 'Vienna',
    'content' => 'Welcome to <b>Vienna</b>!',
]);

$this->Leaflet->addMarker([
    'lat' => 48.1951,
    'lng' => 16.3715,
    'title' => 'Belvedere',
    'content' => 'The Belvedere Palace',
]);

// Store the final JS in an HtmlHelper script block
$this->Leaflet->finalize();
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
$map = $this->Leaflet->map();

// Add markers and stuff
$this->Leaflet->addMarker([/* ... */]);

// Finalize
$map .= $this->Leaflet->script();

// Output both together
echo $map;
```

## Adding markers with popups

```php
// Simple marker
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'title' => 'Vienna',
]);

// Marker with popup content
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'content' => '<b>Vienna</b><br>Capital of Austria',
]);

// Marker with popup open by default
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'content' => 'This popup is open!',
    'open' => true,
]);

// Draggable marker
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'draggable' => true,
]);
```

## Tile providers

The helper comes with built-in tile-provider presets. See
[Leaflet tile providers](./leaflet-tile-providers) for a comprehensive list.

```php
// OpenStreetMap (default)
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_OSM);

// CartoDB Light
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_CARTO_LIGHT);

// CartoDB Dark
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_CARTO_DARK);

// Then create your map
$map = $this->Leaflet->map();
```

### Custom tile layer via map options (recommended)

To use a custom tile provider instead of the default, pass the `tileLayer`
option to `map()`:

```php
echo $this->Leaflet->map([
    'zoom' => 10,
    'lat' => 48.2082,
    'lng' => 16.3738,
    'tileLayer' => [
        'url' => 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        'options' => [
            'attribution' => '&copy; OpenStreetMap, &copy; OpenTopoMap',
            'maxZoom' => 17,
        ],
    ],
]);
```

### Adding additional tile layers

Use `addTileLayer()` to add an *additional* layer on top of the existing one (for
example, overlays):

```php
$this->Leaflet->map();
// This adds a second layer ON TOP of the default OSM layer
$this->Leaflet->addTileLayer(
    'https://{s}.custom-tiles.com/{z}/{x}/{y}.png',
    ['attribution' => '&copy; Custom Tiles'],
);
```

::: info
This does not replace the default tile layer — it adds another one on top.
:::

## Drawing shapes

### Polyline

```php
$this->Leaflet->addPolyline(
    ['lat' => 48.2082, 'lng' => 16.3738],
    ['lat' => 47.0707, 'lng' => 15.4395],
    ['color' => '#ff0000', 'weight' => 5],
);

// Or from multiple points
$points = [
    ['lat' => 48.2082, 'lng' => 16.3738],
    ['lat' => 47.0707, 'lng' => 15.4395],
    ['lat' => 46.0569, 'lng' => 14.5058],
];
$this->Leaflet->addPolylineFromPoints($points, ['color' => '#00ff00']);
```

### Circle

```php
$this->Leaflet->addCircle([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'radius' => 5000, // meters
    'color' => '#3388ff',
    'fillColor' => '#3388ff',
    'fillOpacity' => 0.2,
]);
```

### Polygon

```php
$points = [
    ['lat' => 48.2, 'lng' => 16.3],
    ['lat' => 48.3, 'lng' => 16.4],
    ['lat' => 48.1, 'lng' => 16.5],
];
$this->Leaflet->addPolygon($points, [
    'color' => '#ff0000',
    'fillOpacity' => 0.5,
]);
```

## GeoJSON support

```php
use Geo\Geometry\Feature;
use Geo\Geometry\FeatureCollection;
use Geo\Geometry\Point;

$geoJson = [
    'type' => 'FeatureCollection',
    'features' => [
        [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [16.3738, 48.2082],
            ],
            'properties' => [
                'name' => 'Vienna',
            ],
        ],
    ],
];
$this->Leaflet->addGeoJson($geoJson);
```

You can also pass the plugin's [GeoJSON value objects](/geometry/) directly:

```php
$collection = new FeatureCollection([
    new Feature(new Point(16.3738, 48.2082), ['name' => 'Vienna']),
]);

$this->Leaflet->addGeoJson($collection);
```

And `addPolygon()` accepts a `Geo\Geometry\Polygon` object:

```php
use Geo\Geometry\Polygon;

$polygon = Polygon::fromLatLngPoints([
    ['lat' => 48.2, 'lng' => 16.3],
    ['lat' => 48.3, 'lng' => 16.4],
    ['lat' => 48.1, 'lng' => 16.5],
]);

$this->Leaflet->addPolygon($polygon, [
    'color' => '#ff0000',
    'fillOpacity' => 0.5,
]);
```

## Multiple maps per page

The helper automatically resets when you call `map()`. Each map gets a unique
counter:

```php
// First map
echo $this->Leaflet->map(['div' => ['id' => 'map1']]);
$this->Leaflet->addMarker(['lat' => 48.2, 'lng' => 16.3]);
$this->Leaflet->finalize();

// Second map
echo $this->Leaflet->map(['div' => ['id' => 'map2']]);
$this->Leaflet->addMarker(['lat' => 47.0, 'lng' => 15.4]);
$this->Leaflet->finalize();
```

## Auto-centering

To automatically fit the map bounds to include all markers:

```php
$map = $this->Leaflet->map(['autoCenter' => true]);

$this->Leaflet->addMarker(['lat' => 48.2, 'lng' => 16.3]);
$this->Leaflet->addMarker(['lat' => 47.0, 'lng' => 15.4]);
$this->Leaflet->addMarker(['lat' => 46.0, 'lng' => 14.5]);

$this->Leaflet->finalize();
```

## Custom JS

With `->addCustom($js)` you can inject any custom JS to work alongside the
Leaflet helper code:

```php
$this->Leaflet->map();
$this->Leaflet->addCustom('
    map0.on("click", function(e) {
        alert("You clicked at " + e.latlng);
    });
');
$this->Leaflet->finalize();
```

## Map options

You can configure various Leaflet map options:

```php
$options = [
    'zoom' => 13,
    'lat' => 48.2082,
    'lng' => 16.3738,
    'map' => [
        'scrollWheelZoom' => false, // Disable scroll-wheel zoom
        'zoomControl' => true,      // Show zoom controls
        'dragging' => true,         // Allow map dragging
    ],
    'div' => [
        'id' => 'my-map',
        'width' => '800px',
        'height' => '600px',
        'class' => 'custom-map-class',
    ],
];
$map = $this->Leaflet->map($options);
```

## See also

- [Leaflet tile providers](./leaflet-tile-providers) — a catalog of tile sources.
- [GeoJSON geometry](/geometry/) — the value objects used above.
