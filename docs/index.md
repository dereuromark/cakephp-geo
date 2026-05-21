---
layout: home

hero:
  name: cakephp-geo
  text: Geocoding and Maps for CakePHP
  tagline: Geocode and reverse-geocode your records, query data by distance, and render Google, Leaflet, and static maps — all the way down to GeoJSON value objects.
  image:
    src: /logo.svg
    alt: cakephp-geo
  actions:
    - theme: brand
      text: Get Started
      link: /guide/
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
    details: The Geocoder behavior turns address fields into latitude/longitude automatically on save — with reverse geocoding, accuracy thresholds, and per-field overrides.
  - icon: 🔌
    title: Many Providers
    details: Built on the willdurand/geocoder library — Google Maps, Nominatim, Geoapify, and 12+ address-based and 10+ IP-based providers, plus a fallback chain.
  - icon: 📏
    title: Distance Queries
    details: Find records within a radius using the distance finder, or the spatial finder for PostGIS and MySQL with bounding-box pre-filtering.
  - icon: 🗺️
    title: Map Helpers
    details: Render dynamic Google Maps and open-source Leaflet maps, or generate JavaScript-free static map images from Geoapify, Mapbox, Stadia, and Google.
  - icon: 🧮
    title: Geo Math and GeoJSON
    details: Calculate distances, blur coordinates for privacy, find central points, and work with lightweight GeoJSON Point, Polygon, and Feature value objects.
  - icon: ⚡
    title: Result Caching
    details: Cache geocoding API calls in the GeocodedAddresses table to dodge rate limits, speed up repeat lookups, and keep a history of every request.
---
