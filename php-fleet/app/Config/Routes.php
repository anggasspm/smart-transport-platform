<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Fleet Service — Route Definitions
 *
 * All controllers live under App\Controllers\Api namespace.
 * Controllers will be implemented in Tahap 2.
 *
 * @var RouteCollection $routes
 */

// -----------------------------------------------------------------------
// Health check
// -----------------------------------------------------------------------
$routes->get('/health', 'Api\HealthController::index');

// Metrics
$routes->get('/metrics', 'Api\MetricsController::index');

// -----------------------------------------------------------------------
// Bus endpoints
// -----------------------------------------------------------------------
$routes->group('api', function ($routes) {

    // Bus CRUD
    $routes->get('buses',              'Api\BusController::index');
    $routes->post('buses',             'Api\BusController::create', ['filter' => 'role:admin']);
    $routes->get('buses/(:num)',       'Api\BusController::show/$1');
    $routes->patch('buses/(:num)',     'Api\BusController::update/$1', ['filter' => 'role:admin']);
    $routes->delete('buses/(:num)',    'Api\BusController::delete/$1', ['filter' => 'role:admin']);
    $routes->get('buses/(:num)/location', 'Api\BusController::location/$1');

    // Route CRUD
    $routes->get('routes',             'Api\RouteController::index');
    $routes->post('routes',            'Api\RouteController::create', ['filter' => 'role:admin']);
    $routes->get('routes/(:num)',      'Api\RouteController::show/$1');
    $routes->patch('routes/(:num)',    'Api\RouteController::update/$1', ['filter' => 'role:admin']);
    $routes->delete('routes/(:num)',   'Api\RouteController::delete/$1', ['filter' => 'role:admin']);
    $routes->get('routes/(:num)/buses', 'Api\RouteController::buses/$1');

    // GPS log endpoints
    $routes->post('gps',                    'Api\GpsController::create');
    $routes->get('gps/buses/(:num)',        'Api\GpsController::busHistory/$1');

    // Incident endpoints
    $routes->get('incidents',               'Api\IncidentController::index');
    $routes->post('incidents',              'Api\IncidentController::create');
    $routes->get('incidents/(:num)',        'Api\IncidentController::show/$1');
    $routes->patch('incidents/(:num)',      'Api\IncidentController::update/$1');
    $routes->patch('incidents/(:num)/resolve', 'Api\IncidentController::resolve/$1');
    $routes->delete('incidents/(:num)',     'Api\IncidentController::delete/$1');
});
