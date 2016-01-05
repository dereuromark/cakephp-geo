# CakePHP Geo Plugin

[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-geo.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-geo)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-geo/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)
[![Coverage Status](https://coveralls.io/repos/dereuromark/cakephp-geo/badge.svg)](https://coveralls.io/r/dereuromark/cakephp-geo)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-geo/license.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-geo/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-geo)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

A CakePHP 3.x Plugin to
- geocode locations and save the information (lat/lng) along with the records
- display (google) maps

This plugin requires PHP 5.4+

## Installation & Docs

- [Documentation](docs/README.md)


## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
Some classes are still from 1.2 (and are merely upgraded to 2.x) and might still need some serious refactoring.
If you are able to help on that one, that would be awesome.

### Branching strategy
The master branch is the currently active and maintained one and works with the current 3.x stable version.
Please see the original [Tools plugin](https://github.com/dereuromark/cakephp-tools) if you need the Geo tools for CakePHP 2.x versions.

### TODOs

* Maybe include https://github.com/Pollenizer/CakePHP-GeoIP-Plugin as 3.x version
* Extend to use https://github.com/geocoder-php/Geocoder as source to allow more data providers for the behavior
