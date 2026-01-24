# Leaflet Helper
Using [Leaflet.js](https://leafletjs.com/) - an open-source JavaScript library for interactive maps.

## Adding the helper

Either in your View class or at runtime:
```php
$config = [
    'autoScript' => true,
];
$this->loadHelper('Geo.Leaflet', $config);
```

You can easily configure this globally using Configure (e.g. config/app.php):
```php
    'Leaflet' => [
        'zoom' => 10,
        'lat' => 48.2082,
        'lng' => 16.3738,
    ],
```

Possible config options are:
- zoom: Uses the defaultZoom of 5 otherwise
- lat/lng: Default center coordinates
- block: Display defaults to true to append the generated JS to the "scripts" block
- map: Multiple map options (scrollWheelZoom, zoomControl, dragging)
- tileLayer: Tile layer URL and options
- div: Multiple div options (id, width, height, class)
- marker: Multiple marker options
- popup: Multiple popup options
- polyline: Polyline styling options
- circle: Circle styling options
- polygon: Polygon styling options
- autoCenter: Automatically fit bounds to all markers
- autoScript: Auto-include Leaflet JS/CSS from CDN

## Display a basic dynamic map
Make sure you either loaded your helper with autoScript enabled, or you manually include the Leaflet CSS and JS files.

```php
$options = [
    'zoom' => 13,
    'lat' => 48.2082,
    'lng' => 16.3738,
];
$map = $this->Leaflet->map($options);

// You can echo it now anywhere, it does not matter if you add markers afterwards
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

// Store the final JS in a HtmlHelper script block
$this->Leaflet->finalize();
```
Don't forget to output the buffered JS at the end of your page, where also the other files are included (after all JS files are included!):
```php
echo $this->fetch('script');
```
This code snippet is usually already in your `layout.php` at the end of the body tag.

### Inline JS
Maybe you need inline JS instead, then you can call script() instead of finalize() directly:
```php
// Initialize
$map = $this->Leaflet->map();

// Add markers and stuff
$this->Leaflet->addMarker([...]);

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

## Tile Providers
The helper comes with built-in tile provider presets.
See [Leaflet Tile Providers](LeafletTileProviders.md) for a comprehensive list of available providers.

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
To use a custom tile provider instead of the default, pass the `tileLayer` option to `map()`:
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
Use `addTileLayer()` to add an *additional* layer on top of the existing one (e.g., for overlays):
```php
$this->Leaflet->map();
// This adds a second layer ON TOP of the default OSM layer
$this->Leaflet->addTileLayer(
    'https://{s}.custom-tiles.com/{z}/{x}/{y}.png',
    ['attribution' => '&copy; Custom Tiles']
);
```
Note: This does not replace the default tile layer, it adds another one on top.

## Drawing shapes

### Polyline
```php
$this->Leaflet->addPolyline(
    ['lat' => 48.2082, 'lng' => 16.3738],
    ['lat' => 47.0707, 'lng' => 15.4395],
    ['color' => '#ff0000', 'weight' => 5]
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
    'radius' => 5000,  // meters
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

## Multiple maps per page
The helper automatically resets when you call `map()`. Each map gets a unique counter:

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
With `->addCustom($js)` you can inject any custom JS to work alongside the Leaflet helper code:

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
        'scrollWheelZoom' => false,  // Disable scroll wheel zoom
        'zoomControl' => true,        // Show zoom controls
        'dragging' => true,           // Allow map dragging
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
