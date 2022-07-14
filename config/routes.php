<?php

use Cake\Routing\RouteBuilder;

$routes->prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Geo', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Geo', 'action' => 'index']);

		$routes->fallbacks();
	});
});
