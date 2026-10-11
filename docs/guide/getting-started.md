# Getting started

Render a map with known coordinates first. This needs no geocoding credentials or database table. Complete [Installation](./installation), then check your layout includes the CSS and script blocks shown below.

## Load the helper

In `src/View/AppView.php`, inside `initialize()` after `parent::initialize()`:

```php
$this->addHelper('Geo.Leaflet', ['autoScript' => true]);
```

`autoScript` includes the configured Leaflet CSS and JavaScript. The helper's initialization script also uses jQuery; it does not include jQuery for you.

## Render a map and marker

Put this in a template such as `templates/Pages/map.php`:

```php
<?= $this->Leaflet->map(['lat' => 48.2082, 'lng' => 16.3738, 'zoom' => 13]) ?>
<?php
$this->Leaflet->addMarker([
    'lat' => 48.2082,
    'lng' => 16.3738,
    'title' => 'Vienna',
    'content' => 'A first map marker',
]);
$this->Leaflet->finalize();
?>
```

The default map container is 400 pixels tall. Coordinates passed to helpers are latitude then longitude; GeoJSON coordinate pairs use the opposite order.

## Output the CSS and script blocks

With the default `block => true` setting, Leaflet CSS goes into the `css` block and JavaScript goes into the `script` block. Inside the layout’s `<head>`, use its existing CSS block or add:

```php
<?= $this->fetch('css') ?>
```

Near the end of the layout body, load jQuery before the script block. If your layout already loads jQuery, keep that copy:

```php
<?= $this->Html->script('https://code.jquery.com/jquery-3.7.1.min.js') ?>
```

Then output the helper's scripts:

```php
<?= $this->fetch('script') ?>
```

Use the layout's existing script block once. Opening the page should display a map centered on Vienna, with a marker. Click the marker to open its popup. If it is blank, check the browser console for missing `jQuery` or `L`, check the map container height, and confirm that Leaflet assets and map tiles are allowed by your Content Security Policy.

The default tiles come from OpenStreetMap. Keep attribution visible and follow the [tile usage policy](https://operations.osmfoundation.org/policies/tiles/); public tiles are not an unrestricted hosting service for every workload.

## Add address lookup later

The map above uses fixed coordinates. To turn address text into coordinates, configure an optional [Geocoder provider](/geocoder/) and add the [Geocoder behavior](/behavior/) to your own table. To store results for reuse, enable the separate [database cache](/model/).

## Admin backend

Keep plugin routes disabled unless your application protects the admin prefix. The controllers inherit `AppController`, but the plugin does not implement authentication or role checks. Replace the earlier registration with `$this->addPlugin('Geo')` only after access rules are in place. The admin geocoding/cache interface needs the migration and provider configuration.
