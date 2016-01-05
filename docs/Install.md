# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

```
composer require dereuromark/cakephp-geo:dev-master
```

or manually via

```
"require": {
	"dereuromark/cakephp-geo": "dev-master"
}
```
and

	composer update

Details @ https://packagist.org/packages/dereuromark/cakephp-geo

This will load the plugin (within your bootstrap file):
```php
Plugin::load('Geo');
```
or
```php
Plugin::loadAll(...);
```

In case you want the Geo bootstrap file included (recommended), you can do that in your `ROOT/config/bootstrap.php` with

```php
Plugin::load('Geo', ['bootstrap' => true]);
```

or

```php
Plugin::loadAll([
		'Geo' => ['bootstrap' => true]
]);
```
