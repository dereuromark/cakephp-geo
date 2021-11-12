<?php
/**
 * @var \Cake\Routing\RouteBuilder $routes
 */

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

$routes->prefix('Admin', function (RouteBuilder $routes) {
	$routes->plugin('Geo', function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'Geo', 'action' => 'index']);

		$routes->fallbacks();
	});
});
