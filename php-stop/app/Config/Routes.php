<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Stop Service Routes
$routes->get('api/stops', 'StopController::index');
$routes->get('api/stops/(:num)/status', 'StopController::status/$1');
$routes->post('api/passengers/count', 'CountController::store');
$routes->get('api/alerts', 'AlertController::index');
$routes->get('health', 'HealthController::index');