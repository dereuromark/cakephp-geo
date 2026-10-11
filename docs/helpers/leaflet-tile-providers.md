# Leaflet tile providers

The [Leaflet helper](./leaflet) provides three tile presets. Presets configure URLs and attribution; they do not grant permission to use the provider's servers. Check the provider's current terms, credentials and usage limits before deploying a map.

## Built-in presets

Choose a preset before calling `map()`:

```php
use Geo\View\Helper\LeafletHelper;

$this->Leaflet->useTilePreset(LeafletHelper::TILES_OSM);
echo $this->Leaflet->map(['lat' => 48.2082, 'lng' => 16.3738]);
$this->Leaflet->finalize();
```

| Constant | Configured tiles | Maximum zoom |
| --- | --- | --- |
| `TILES_OSM` | OpenStreetMap Standard | 19 |
| `TILES_CARTO_LIGHT` | CARTO Positron (`light_all`) | 20 |
| `TILES_CARTO_DARK` | CARTO Dark Matter (`dark_all`) | 20 |

`TILES_OSM` is the default. It uses `https://tile.openstreetmap.org/{z}/{x}/{y}.png`. Follow the [OpenStreetMap tile usage policy](https://operations.osmfoundation.org/policies/tiles/), including visible attribution, browser caching and a valid referrer. Public tiles have no availability guarantee and do not permit bulk downloading or offline prefetching.

The CARTO presets contain the existing CDN URL patterns without credentials. For a deployment that requires a CARTO key, configure the current raster endpoint explicitly using the provider's [basemap documentation](https://carto.com/basemaps/). Do not assume that a preset implies unlimited or anonymous access.

## Configure a custom base layer

Set `tileLayer` in the helper configuration or the options passed to `map()`. This configures the initial base layer. For example, with a browser-visible CARTO key obtained for your application:

```php
$tileKey = 'YOUR_CARTO_KEY';

echo $this->Leaflet->map([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'tileLayer' => [
        'url' => 'https://basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png?key=' . rawurlencode($tileKey),
        'options' => [
            'attribution' => '&copy; OpenStreetMap contributors &copy; CARTO',
            'maxZoom' => 20,
        ],
    ],
]);
$this->Leaflet->finalize();
```

Tile URLs and keys appear in the rendered page. Use the provider's browser key restrictions and the attribution it requires for the selected layer. Configure the URL and options from the provider's documentation rather than copying old pricing tables or discontinued endpoints.

Per-map `tileLayer` configuration merges with the helper defaults. Unspecified options remain in place; override attribution, zoom limits and subdomains for your provider.

`addTileLayer($url, $options)` adds another layer to an initialized map; it does not remove the base layer. Use it for overlays, or configure `tileLayer` above when replacing the map background.

## Other providers

The helper accepts raster tile URL templates supported by Leaflet. Consult the provider's current integration instructions for URL patterns, required attribution, account permissions and usage limits. Vector styles need their own client integration; a vector style JSON URL is not a raster tile URL.

For supported third-party URL templates, see the [leaflet-providers project](https://github.com/leaflet-extras/leaflet-providers). Adding that JavaScript package is separate from loading this CakePHP helper. Keep its version pinned and use the helper's generated map name when adding custom JavaScript.
