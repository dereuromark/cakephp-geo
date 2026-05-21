import { defineConfig } from 'vitepress'

function guideSidebar() {
  return [
    {
      text: 'Guide',
      items: [
        { text: 'Overview', link: '/guide/' },
        { text: 'Installation', link: '/guide/installation' },
        { text: 'Contributing', link: '/guide/contributing' },
      ],
    },
    {
      text: 'Behavior',
      items: [
        { text: 'Geocoder Behavior', link: '/behavior/' },
        { text: 'Search Integration', link: '/behavior/search' },
      ],
    },
    {
      text: 'Geocoding Libraries',
      items: [
        { text: 'Geocoder Class', link: '/geocoder/' },
        { text: 'Calculator', link: '/geocoder/calculator' },
        { text: 'Geo Calculator', link: '/geocoder/geo-calculator' },
      ],
    },
    {
      text: 'Geometry',
      items: [
        { text: 'GeoJSON', link: '/geometry/' },
      ],
    },
    {
      text: 'Helpers',
      items: [
        { text: 'Google Map', link: '/helpers/google-map' },
        { text: 'Leaflet', link: '/helpers/leaflet' },
        { text: 'Leaflet Tile Providers', link: '/helpers/leaflet-tile-providers' },
        { text: 'Static Map', link: '/helpers/static-map' },
      ],
    },
    {
      text: 'Model',
      items: [
        { text: 'GeocodedAddresses Cache', link: '/model/' },
      ],
    },
  ]
}

export default defineConfig({
  title: 'cakephp-geo',
  description: 'Geocoding, distance queries, and map helpers for CakePHP — multiple geocoder providers, Google Maps, Leaflet, and static maps.',
  base: '/cakephp-geo/',
  lastUpdated: true,
  cleanUrls: true,
  sitemap: {
    hostname: 'https://dereuromark.github.io/cakephp-geo/',
  },
  head: [
    ['link', { rel: 'icon', href: '/cakephp-geo/favicon.svg', type: 'image/svg+xml' }],
  ],
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/', activeMatch: '/guide/' },
      { text: 'Behavior', link: '/behavior/', activeMatch: '/behavior/' },
      { text: 'Geocoding', link: '/geocoder/', activeMatch: '/(geocoder|geometry|model)/' },
      { text: 'Helpers', link: '/helpers/google-map', activeMatch: '/helpers/' },
      {
        text: 'Links',
        items: [
          { text: 'GitHub', link: 'https://github.com/dereuromark/cakephp-geo' },
          { text: 'Packagist', link: 'https://packagist.org/packages/dereuromark/cakephp-geo' },
          { text: 'Issues', link: 'https://github.com/dereuromark/cakephp-geo/issues' },
        ],
      },
    ],
    sidebar: {
      '/guide/': guideSidebar(),
      '/behavior/': guideSidebar(),
      '/geocoder/': guideSidebar(),
      '/geometry/': guideSidebar(),
      '/helpers/': guideSidebar(),
      '/model/': guideSidebar(),
    },
    socialLinks: [
      { icon: 'github', link: 'https://github.com/dereuromark/cakephp-geo' },
    ],
    search: {
      provider: 'local',
    },
    editLink: {
      pattern: 'https://github.com/dereuromark/cakephp-geo/edit/master/docs/:path',
      text: 'Edit this page on GitHub',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright Mark Scherer',
    },
  },
})
