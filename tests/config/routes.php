<?php

namespace Geo\Test\App\Config;

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::reload();

Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks();
});

Router::prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Geo', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Geo', 'action' => 'index']);

		$routes->fallbacks();
	});
});
