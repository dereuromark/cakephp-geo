# Geocoder Class

::: info
This class is very low level. If you want to geocode addresses on `save()`, look
into the [Geocoder behavior](/behavior/) instead.
:::

## Basic usage

```php
use Geo\Geocoder\Geocoder;

$Geocoder = new Geocoder(['allowInconclusive' => true, 'minAccuracy' => Geocoder::TYPE_POSTAL]);
$addresses = $Geocoder->geocode($address);

if (!empty($addresses)) {
    $address = $addresses->first();
}
```

Since it uses the Google Maps geocoding service by default, make sure to set the
corresponding app config:

```php
'Geocoder' => [
    'apiKey' => '...',
],
```

## Reverse geocoding

```php
$result = $Geocoder->reverse($lat, $lng);
```

## Providers

The Geocoder supports multiple geocoding providers out of the box. Use provider
constants to specify which one to use:

| Provider | API Key | Notes |
|----------|---------|-------|
| Google Maps | Required | Default, most reliable |
| Nominatim | No | Free, OpenStreetMap-based |
| Geoapify | Required (free tier) | Good alternative |
| Null | No | For testing |

### Using string provider names

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

### Provider-specific configuration

Configure providers with their specific settings:

```php
// config/app_local.php
use Geo\Geocoder\Geocoder;

'Geocoder' => [
    'provider' => Geocoder::PROVIDER_NOMINATIM,
    'allowInconclusive' => true,
    'minAccuracy' => Geocoder::TYPE_COUNTRY,
    'google' => [
        'apiKey' => env('GOOGLE_MAPS_API_KEY'),
        'region' => 'us',
    ],
    'nominatim' => [
        'userAgent' => 'MyApp/1.0', // Required by OSM policy
    ],
    'geoapify' => [
        'apiKey' => env('GEOAPIFY_API_KEY'),
    ],
],
```

### Provider fallback chain

Use multiple providers with automatic fallback. If one provider fails due to
rate limiting or server errors, the next provider in the chain is tried:

```php
use Geo\Geocoder\Geocoder;

'Geocoder' => [
    'providers' => [
        Geocoder::PROVIDER_GOOGLE,    // Try Google first
        Geocoder::PROVIDER_NOMINATIM, // Fall back to Nominatim
        Geocoder::PROVIDER_GEOAPIFY,  // Then Geoapify
    ],
    'google' => [
        'apiKey' => env('GOOGLE_MAPS_API_KEY'),
    ],
    'nominatim' => [
        'userAgent' => 'MyApp/1.0',
    ],
    'geoapify' => [
        'apiKey' => env('GEOAPIFY_API_KEY'),
    ],
],
```

The chain automatically handles:

- `QuotaExceeded` — API rate limit reached.
- `InvalidServerResponse` — server errors or timeouts.

Other exceptions are thrown immediately without trying the next provider.

### Using a callable (advanced)

For advanced use cases or custom providers from geocoder-php, use
`Cake\Http\Client` directly (it implements PSR-18):

```php
use Cake\Http\Client;

'Geocoder' => [
    'provider' => function () {
        return \Geocoder\Provider\Nominatim\Nominatim::withOpenStreetMapServer(
            new Client(),
            'MyApp/1.0',
        );
    },
],
```

## Creating custom providers

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
        // or throw UnsupportedOperation and override the geocode/reverse methods
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

Or use it directly via a callable:

```php
'Geocoder' => [
    'provider' => fn () => new MyCustomProvider(['apiKey' => '...']),
],
```

## See also

- [Geocoder behavior](/behavior/) — geocode entity data on save.
- [Calculator](./calculator) — distance math and coordinate utilities.
