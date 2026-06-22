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

// iot
$routes->get('api/stops/route/(:num)/except/(:num)', 'StopController::getStopsByRouteExcept/$1/$2');
$routes->get('api/stops/(:num)/bus-status', 'StopController::getBusStatus/$1');
$routes->put('api/stops/(:num)/bus-arrival', 'StopController::busArrival/$1');
$routes->put('api/stops/(:num)/bus-departure', 'StopController::busDeparture/$1');
$routes->post('api/stops/passenger-count', 'StopController::updateCurrentLoad');

// metrics
$routes->get('/metrics', 'Api\MetricsController::index');