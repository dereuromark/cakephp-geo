# Geocoder Class

Note: This class is very low level.
If you want to geocode addresses on save(), you should look into the behavior instead.

## Basic Usage

```php
use Geo\Geocoder\Geocoder;

$Geocoder = new Geocoder(['allowInconclusive' => true, 'minAccuracy' => Geocoder::TYPE_POSTAL]);
$addresses = $Geocoder->geocode($address);

if (!empty($addresses)) {
   $address = $addresses->first();
}
```

Since it is using GoogleMaps geocoding service underneath by default, make sure to set the corresponding app config:
```php
    'Geocoder' => [
        'apiKey' => '...',
    ],
```

## Reverse Geocoding

```php
$result = $Geocoder->reverse($lat, $lng);
```

## Providers

The Geocoder supports multiple geocoding providers out of the box. Use provider constants to specify which provider to use:

| Provider | Constant | API Key | Notes |
|----------|----------|---------|-------|
| Google Maps | `Geocoder::PROVIDER_GOOGLE` | Required | Default, most reliable |
| Nominatim | `Geocoder::PROVIDER_NOMINATIM` | No | Free, OpenStreetMap-based |
| Geoapify | `Geocoder::PROVIDER_GEOAPIFY` | Required (free tier) | Good alternative |
| Null | `Geocoder::PROVIDER_NULL` | No | For testing |

### Using String Provider Names

```php
use Geo\Geocoder\Geocoder;

// Use Nominatim (OpenStreetMap)
$geocoder = new Geocoder([
    'provider' => Geocoder::PROVIDER_NOMINATIM,
]);

// Use Geoapify
$geocoder = new Geocoder([
    'provider' => Geocoder::PROVIDER_GEOAPIFY,
    'apiKey' => 'your-geoapify-key',
]);
```

### Provider-Specific Configuration

Configure providers with their specific settings:

```php
// config/app_local.php
'Geocoder' => [
    'provider' => 'nominatim',
    'allowInconclusive' => true,
    'minAccuracy' => Geocoder::TYPE_COUNTRY,
    'google' => [
        'apiKey' => env('GOOGLE_MAPS_API_KEY'),
        'region' => 'us',
    ],
    'nominatim' => [
        'userAgent' => 'MyApp/1.0',  // Required by OSM policy
    ],
    'geoapify' => [
        'apiKey' => env('GEOAPIFY_API_KEY'),
    ],
],
```

### Using a Callable (Legacy/Advanced)

For advanced use cases or custom providers from geocoder-php:

```php
'Geocoder' => [
    'provider' => function () {
        return \Geocoder\Provider\Nominatim\Nominatim::withOpenStreetMapServer(
            new \Http\Adapter\Cake\Client(),
            'User-Agent'
        );
    },
],
```

## Creating Custom Providers

You can create custom providers by implementing `GeocodingProviderInterface`:

```php
namespace App\Geocoder\Provider;

use Geo\Geocoder\Provider\AbstractGeocodingProvider;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;

class MyCustomProvider extends AbstractGeocodingProvider {

    protected array $_defaultConfig = [
        'apiKey' => null,
        'locale' => 'en',
        'baseUrl' => 'https://api.myservice.com/geocode',
    ];

    public function getName(): string {
        return 'mycustom';
    }

    public function requiresApiKey(): bool {
        return true;
    }

    protected function buildProvider(): Provider {
        // Return a geocoder-php provider instance
        // or throw UnsupportedOperation and override geocode/reverse methods
    }
}
```

Register your custom provider:

```php
use Geo\Geocoder\Geocoder;
use App\Geocoder\Provider\MyCustomProvider;

// Register the provider
Geocoder::registerProvider('mycustom', MyCustomProvider::class);

// Use it
$geocoder = new Geocoder(['provider' => 'mycustom']);
```

Or use it directly via callable:

```php
'Geocoder' => [
    'provider' => fn () => new MyCustomProvider(['apiKey' => '...']),
],
```
