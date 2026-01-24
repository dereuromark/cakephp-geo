# Leaflet Tile Provider Alternatives

A comprehensive list of tile providers for use with LeafletHelper.

## Free Tile Providers (No API Key Required)

### OpenStreetMap (Default)
The default tile provider, community-maintained.

```php
// Already the default, but can be set explicitly:
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_OSM);
```

| Property | Value |
|----------|-------|
| URL | `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png` |
| Attribution | `© OpenStreetMap contributors` |
| Max Zoom | 19 |
| Rate Limit | Heavy use requires own tile server |
| Terms | https://operations.osmfoundation.org/policies/tiles/ |

---

### CartoDB / CARTO
Modern, clean map styles. Good for data visualization.

```php
// Light theme
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_CARTO_LIGHT);

// Dark theme
$this->Leaflet->useTilePreset(\Geo\View\Helper\LeafletHelper::TILES_CARTO_DARK);

// Or manually for Voyager (colorful):
$this->Leaflet->addTileLayer(
    'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
    [
        'attribution' => '© OpenStreetMap contributors © CARTO',
        'subdomains' => 'abcd',
        'maxZoom' => 20,
    ]
);
```

| Variant | URL Pattern |
|---------|-------------|
| Positron (Light) | `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png` |
| Dark Matter | `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png` |
| Voyager | `https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png` |

| Property | Value |
|----------|-------|
| Max Zoom | 20 |
| Rate Limit | 75,000 requests/month free |
| Terms | https://carto.com/legal/ |

---

### Stadia Maps (formerly Stamen)
Artistic and thematic map styles. **Requires free API key since 2023.**

```php
// Register at https://stadiamaps.com/ for free API key
$this->Leaflet->addTileLayer(
    'https://tiles.stadiamaps.com/tiles/stamen_toner/{z}/{x}/{y}{r}.png?api_key=YOUR_KEY',
    [
        'attribution' => '© Stadia Maps © Stamen Design © OpenStreetMap contributors',
        'maxZoom' => 20,
    ]
);
```

| Variant | Style |
|---------|-------|
| Toner | High-contrast B&W, good for overlays |
| Toner Lite | Lighter version of Toner |
| Terrain | Terrain with labels |
| Watercolor | Artistic watercolor style |

| Property | Value |
|----------|-------|
| Free Tier | 200,000 requests/month |
| Sign Up | https://stadiamaps.com/ |

---

### OpenTopoMap
Topographic maps with elevation contours.

```php
$this->Leaflet->addTileLayer(
    'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
    [
        'attribution' => '© OpenTopoMap (CC-BY-SA)',
        'maxZoom' => 17,
    ]
);
```

| Property | Value |
|----------|-------|
| Max Zoom | 17 |
| Best For | Hiking, outdoor activities |
| Terms | https://opentopomap.org/about |

---

### Esri (ArcGIS)
Professional map styles from Esri.

```php
// World Street Map
$this->Leaflet->addTileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
    [
        'attribution' => 'Tiles © Esri',
        'maxZoom' => 18,
    ]
);

// World Imagery (Satellite)
$this->Leaflet->addTileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
    [
        'attribution' => 'Tiles © Esri',
        'maxZoom' => 18,
    ]
);

// World Topo Map
$this->Leaflet->addTileLayer(
    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
    [
        'attribution' => 'Tiles © Esri',
        'maxZoom' => 18,
    ]
);
```

| Variant | Description |
|---------|-------------|
| World_Street_Map | Standard street map |
| World_Imagery | Satellite imagery |
| World_Topo_Map | Topographic |
| World_Terrain_Base | Terrain shading |
| World_Gray_Canvas | Neutral gray base |

| Property | Value |
|----------|-------|
| Max Zoom | 18-19 |
| Terms | https://www.esri.com/en-us/legal/terms/full-master-agreement |

---

### CyclOSM
Bicycle-focused map with cycling infrastructure.

```php
$this->Leaflet->addTileLayer(
    'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
    [
        'attribution' => '© CyclOSM © OpenStreetMap contributors',
        'maxZoom' => 20,
    ]
);
```

---

### Humanitarian OpenStreetMap (HOT)
Humanitarian-focused styling.

```php
$this->Leaflet->addTileLayer(
    'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
    [
        'attribution' => '© OpenStreetMap contributors, Tiles style by HOT',
        'maxZoom' => 19,
    ]
);
```

---

## Providers Requiring API Keys

### Mapbox
High-quality custom map styles. Very popular.

```php
$mapboxToken = 'YOUR_MAPBOX_ACCESS_TOKEN';
$this->Leaflet->addTileLayer(
    "https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={$mapboxToken}",
    [
        'attribution' => '© Mapbox © OpenStreetMap contributors',
        'id' => 'mapbox/streets-v11', // or satellite-v9, outdoors-v11, light-v10, dark-v10
        'tileSize' => 512,
        'zoomOffset' => -1,
        'maxZoom' => 18,
    ]
);
```

| Property | Value |
|----------|-------|
| Free Tier | 50,000 map loads/month |
| Sign Up | https://www.mapbox.com/ |
| Styles | streets, satellite, outdoors, light, dark, + custom |

---

### Thunderforest
Outdoor and transport focused maps.

```php
$apiKey = 'YOUR_THUNDERFOREST_API_KEY';
$this->Leaflet->addTileLayer(
    "https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png?apikey={$apiKey}",
    [
        'attribution' => '© Thunderforest © OpenStreetMap contributors',
        'maxZoom' => 22,
    ]
);
```

| Variant | Description |
|---------|-------------|
| cycle | OpenCycleMap |
| transport | Public transport |
| landscape | Landscape |
| outdoors | Outdoor activities |
| atlas | Atlas style |
| spinal-map | High contrast |

| Property | Value |
|----------|-------|
| Free Tier | 150,000 tiles/month |
| Sign Up | https://www.thunderforest.com/ |

---

### HERE Maps
Enterprise-grade mapping.

```php
$hereApiKey = 'YOUR_HERE_API_KEY';
$this->Leaflet->addTileLayer(
    "https://2.base.maps.ls.hereapi.com/maptile/2.1/maptile/newest/normal.day/{z}/{x}/{y}/256/png8?apiKey={$hereApiKey}",
    [
        'attribution' => '© HERE',
        'maxZoom' => 20,
    ]
);
```

| Property | Value |
|----------|-------|
| Free Tier | 250,000 transactions/month |
| Sign Up | https://developer.here.com/ |

---

### TomTom
Navigation-focused maps.

```php
$tomtomKey = 'YOUR_TOMTOM_API_KEY';
$this->Leaflet->addTileLayer(
    "https://api.tomtom.com/map/1/tile/basic/main/{z}/{x}/{y}.png?key={$tomtomKey}",
    [
        'attribution' => '© TomTom',
        'maxZoom' => 22,
    ]
);
```

| Property | Value |
|----------|-------|
| Free Tier | 2,500 transactions/day |
| Sign Up | https://developer.tomtom.com/ |

---

### Jawg Maps
Modern vector and raster tiles.

```php
$jawgToken = 'YOUR_JAWG_ACCESS_TOKEN';
$this->Leaflet->addTileLayer(
    "https://{s}-tiles.jawg.io/jawg-streets/{z}/{x}/{y}{r}.png?access-token={$jawgToken}",
    [
        'attribution' => '© Jawg © OpenStreetMap contributors',
        'maxZoom' => 22,
        'subdomains' => 'abcd',
    ]
);
```

| Variant | Description |
|---------|-------------|
| jawg-streets | Street map |
| jawg-terrain | Terrain |
| jawg-sunny | Bright style |
| jawg-dark | Dark mode |
| jawg-light | Light mode |

| Property | Value |
|----------|-------|
| Free Tier | 50,000 tiles/month |
| Sign Up | https://www.jawg.io/ |

---

## Adding Custom Presets to LeafletHelper

You can extend the helper to add your own presets:

```php
// In your AppView or a custom helper extending LeafletHelper
namespace App\View\Helper;

use Geo\View\Helper\LeafletHelper;

class MyLeafletHelper extends LeafletHelper {

    public const TILES_ESRI_SATELLITE = 'esri_satellite';
    public const TILES_MAPBOX_STREETS = 'mapbox_streets';

    protected array $tilePresets = [
        // Inherit parent presets
        'osm' => [
            'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'options' => [
                'attribution' => '© OpenStreetMap contributors',
                'maxZoom' => 19,
            ],
        ],
        // Add custom presets
        'esri_satellite' => [
            'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'options' => [
                'attribution' => 'Tiles © Esri',
                'maxZoom' => 18,
            ],
        ],
    ];
}
```

---

## Using leaflet-providers Plugin

For easy access to many providers, use the leaflet-providers plugin:

### Installation

Add to your layout or view:
```html
<script src="https://unpkg.com/leaflet-providers@latest/leaflet-providers.js"></script>
```

### Usage with LeafletHelper

```php
$this->Leaflet->map();
$this->Leaflet->addCustom("
    L.tileLayer.provider('CartoDB.Positron').addTo(map0);
");
$this->Leaflet->finalize();
```

### Available Providers via leaflet-providers

See full list: https://leaflet-extras.github.io/leaflet-providers/preview/

Popular ones:
- `OpenStreetMap.Mapnik`
- `CartoDB.Positron`
- `CartoDB.DarkMatter`
- `CartoDB.Voyager`
- `Stadia.StamenToner`
- `Stadia.StamenWatercolor`
- `Esri.WorldStreetMap`
- `Esri.WorldImagery`
- `OpenTopoMap`
- `CyclOSM`

---

## Comparison Table

| Provider | Free Tier | API Key | Best For |
|----------|-----------|---------|----------|
| OpenStreetMap | Unlimited* | No | General use |
| CartoDB | 75k/month | No | Data viz, clean design |
| Stadia/Stamen | 200k/month | Yes (free) | Artistic styles |
| OpenTopoMap | Unlimited* | No | Outdoor/hiking |
| Esri | Unlimited* | No | Professional maps |
| Mapbox | 50k/month | Yes (free) | Custom styles |
| Thunderforest | 150k/month | Yes (free) | Cycling, transport |
| HERE | 250k/month | Yes (free) | Enterprise |

*Fair use policy applies

---

## Recommendations by Use Case

| Use Case | Recommended Provider |
|----------|---------------------|
| General purpose | OpenStreetMap, CartoDB Voyager |
| Dark theme UI | CartoDB Dark Matter |
| Light/minimal UI | CartoDB Positron |
| Outdoor/hiking | OpenTopoMap, Thunderforest Outdoors |
| Cycling | CyclOSM, Thunderforest Cycle |
| Satellite imagery | Esri World Imagery, Mapbox Satellite |
| Custom branding | Mapbox (custom styles) |
| High contrast | Stadia Toner |
| Artistic | Stadia Watercolor |
