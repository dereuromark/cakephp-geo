# StaticMap Helper

The StaticMapHelper generates static map images from various providers without requiring JavaScript.
It supports multiple providers with a unified API.

## Supported Providers

| Provider | API Documentation |
|----------|-------------------|
| Geoapify | [Static Maps API](https://apidocs.geoapify.com/docs/maps/static/) |
| Mapbox | [Static Images API](https://docs.mapbox.com/api/maps/static-images/) |
| Stadia | [Static Maps API](https://docs.stadiamaps.com/static-maps/) |
| Google | [Maps Static API](https://developers.google.com/maps/documentation/maps-static/overview) |

## Adding the helper

In your View class or at runtime:
```php
$this->loadHelper('Geo.StaticMap');
```

## Configuration

Configure globally using Configure (e.g., config/app_local.php):
```php
'StaticMap' => [
    'provider' => 'geoapify',  // default provider
    'size' => '400x300',
    'format' => 'png',
    'scale' => 1,
    'geoapify' => [
        'apiKey' => env('GEOAPIFY_API_KEY'),
        'style' => 'osm-bright',
    ],
    'mapbox' => [
        'apiKey' => env('MAPBOX_ACCESS_TOKEN'),
        'style' => 'streets-v12',
        'username' => 'mapbox',  // for custom styles
    ],
    'stadia' => [
        'apiKey' => env('STADIA_API_KEY'),
        'style' => 'alidade_smooth',
    ],
    'google' => [
        'apiKey' => env('GOOGLE_MAPS_API_KEY'),
        'style' => 'roadmap',
    ],
],
```

## Basic Usage

### Display a static map image
```php
echo $this->StaticMap->image([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'zoom' => 12,
]);
```

### Get just the URL
```php
$url = $this->StaticMap->url([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'zoom' => 12,
]);
```

### Create a link
```php
echo $this->StaticMap->link('View Map', [
    'lat' => 48.2082,
    'lng' => 16.3738,
    'zoom' => 12,
]);
```

## Switching Providers

Switch providers per-call using constants (recommended) or strings:
```php
use Geo\View\Helper\StaticMapHelper;

// Use Mapbox for this specific map
echo $this->StaticMap->image([
    'provider' => StaticMapHelper::PROVIDER_MAPBOX,
    'lat' => 48.2082,
    'lng' => 16.3738,
    'zoom' => 12,
]);

// Use Google for another
echo $this->StaticMap->image([
    'provider' => StaticMapHelper::PROVIDER_GOOGLE,
    'lat' => 51.5074,
    'lng' => -0.1278,
    'zoom' => 10,
]);
```

## Map Options

Common options work across all providers:

| Option | Description | Default |
|--------|-------------|---------|
| lat | Latitude | - |
| lng | Longitude | - |
| zoom | Zoom level | 12 |
| size | Image size (WIDTHxHEIGHT) | 400x300 |
| format | Image format (png, jpg) | png |
| scale | Retina scale (1 or 2) | 1 |
| style | Map style (provider-specific) | varies |
| markers | Array of markers | [] |
| paths | Array of paths | [] |

## Adding Markers

```php
echo $this->StaticMap->image([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'zoom' => 12,
    'markers' => [
        [
            'lat' => 48.2082,
            'lng' => 16.3738,
            'color' => 'red',
            'label' => 'A',
        ],
        [
            'lat' => 48.1951,
            'lng' => 16.3715,
            'color' => 'blue',
            'label' => 'B',
        ],
    ],
]);
```

### Using the markers helper

```php
$positions = [
    ['lat' => 48.2082, 'lng' => 16.3738],
    ['lat' => 48.1951, 'lng' => 16.3715],
    ['lat' => 48.2100, 'lng' => 16.3600],
];

// Apply styling and auto-labels
$markers = $this->StaticMap->markers($positions, [
    'color' => 'red',
    'autoLabel' => true,  // Adds A, B, C, ...
]);

echo $this->StaticMap->image([
    'zoom' => 13,
    'markers' => $markers,
]);
```

### Marker options

| Option | Description |
|--------|-------------|
| lat | Latitude (required) |
| lng | Longitude (required) |
| color | Marker color (name or hex) |
| label | Single character label |
| size | small, medium, large |
| icon | Custom icon (provider-specific) |

## Adding Paths

Draw paths/polylines between points:

```php
echo $this->StaticMap->image([
    'paths' => [
        [
            'points' => [
                ['lat' => 48.2082, 'lng' => 16.3738],
                ['lat' => 47.0707, 'lng' => 15.4395],
                ['lat' => 46.0569, 'lng' => 14.5058],
            ],
            'color' => 'blue',
            'weight' => 3,
        ],
    ],
]);
```

### Using the paths helper

```php
$pathData = [
    [
        'points' => [
            ['lat' => 48.2082, 'lng' => 16.3738],
            ['lat' => 47.0707, 'lng' => 15.4395],
        ],
    ],
];

$paths = $this->StaticMap->paths($pathData, [
    'color' => 'red',
    'weight' => 5,
]);

echo $this->StaticMap->image(['paths' => $paths]);
```

### Path options

| Option | Description |
|--------|-------------|
| points | Array of lat/lng points (required) |
| color | Line color |
| weight | Line thickness |
| fillColor | Fill color (for polygons) |

## Map Styles

Each provider has different available styles.

### Geoapify styles
- osm-bright
- osm-bright-grey
- klokantech-basic
- dark-matter
- positron
- toner
- (and more)

### Mapbox styles
- streets-v12
- outdoors-v12
- light-v11
- dark-v11
- satellite-v9
- satellite-streets-v12

### Stadia styles
- alidade_smooth
- alidade_smooth_dark
- alidade_satellite
- outdoors
- stamen_toner
- stamen_terrain
- stamen_watercolor

### Google styles
- roadmap
- satellite
- terrain
- hybrid

Get supported styles programmatically:
```php
$styles = $this->StaticMap->supportedStyles('geoapify');
```

## Retina/HiDPI Images

For retina displays, use scale 2:
```php
echo $this->StaticMap->image([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'scale' => 2,  // 2x resolution
]);
```

## HTML Attributes

Pass HTML attributes to the image tag:
```php
echo $this->StaticMap->image(
    [
        'lat' => 48.2082,
        'lng' => 16.3738,
    ],
    [
        'class' => 'map-image',
        'alt' => 'Map of Vienna',
        'loading' => 'lazy',
    ]
);
```

## Auto-center with markers

When you provide markers but no center coordinates, some providers automatically center the map:

```php
// No lat/lng specified - map auto-centers on markers
echo $this->StaticMap->image([
    'provider' => 'mapbox',
    'markers' => [
        ['lat' => 48.2082, 'lng' => 16.3738],
        ['lat' => 47.0707, 'lng' => 15.4395],
    ],
]);
```

## Provider Access

Get the underlying provider instance:
```php
$provider = $this->StaticMap->provider('geoapify');
echo $provider->getName(); // 'geoapify'
```

List available providers:
```php
$providers = $this->StaticMap->availableProviders();
// ['geoapify', 'mapbox', 'stadia', 'google']
```
