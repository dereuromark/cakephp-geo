---
layout: home

hero:
  name: cakephp-geo
  text: Geocoding and Maps for CakePHP
  tagline: Geocode addresses, query records by distance, and render Google, Leaflet, and static maps with GeoJSON value objects.
  image:
    src: /logo.svg
    alt: cakephp-geo
  actions:
    - theme: brand
      text: Installation
      link: /guide/installation
    - theme: alt
      text: Getting started
      link: /guide/getting-started
    - theme: alt
      text: Geocoder Behavior
      link: /behavior/
    - theme: alt
      text: Map Helpers
      link: /helpers/google-map
    - theme: alt
      text: View on GitHub
      link: https://github.com/dereuromark/cakephp-geo

features:
  - icon: 📍
    title: Geocode on Save
    details: The Geocoder behavior turns address fields into latitude/longitude automatically on save; with accuracy thresholds and configurable field names.
  - icon: 🔌
    title: Many Providers
    details: Built on the willdurand/geocoder library; Google Maps, Nominatim, Geoapify, and additional address and IP providers, plus a fallback chain.
  - icon: 📏
    title: Distance Queries
    details: Find records within a radius using the distance finder, or the spatial finder for compatible MySQL or MariaDB installations.
  - icon: 🗺️
    title: Map Helpers
    details: Render dynamic Google Maps and open-source Leaflet maps, or generate JavaScript-free static map images from Geoapify, Mapbox, Stadia, and Google.
  - icon: 🧮
    title: Geo Math and GeoJSON
    details: Calculate distances, blur coordinates for privacy, find central points, and work with lightweight GeoJSON Point, Polygon, and Feature value objects.
  - icon: ⚡
    title: Result Caching
    details: Cache geocoding API calls in the GeocodedAddresses table to reuse stored results for the same address.
---
