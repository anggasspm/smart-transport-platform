<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── Health Check ─────────────────────────────────────────────
$routes->get('health', 'HealthController::index');

// ─── Bus Routes ───────────────────────────────────────────────
$routes->group('api', function ($routes) {

    // Buses
    $routes->get('buses',              'BusController::index');
    $routes->post('buses',             'BusController::create');
    $routes->get('buses/(:num)',       'BusController::show/$1');
    $routes->patch('buses/(:num)',     'BusController::update/$1');
    $routes->delete('buses/(:num)',    'BusController::delete/$1');
    $routes->get('buses/(:num)/location', 'BusController::location/$1');

    // Routes
    $routes->get('routes',             'RouteController::index');
    $routes->post('routes',            'RouteController::create');
    $routes->get('routes/(:num)',      'RouteController::show/$1');
    $routes->patch('routes/(:num)',    'RouteController::update/$1');
    $routes->delete('routes/(:num)',   'RouteController::delete/$1');
    $routes->get('routes/(:num)/buses', 'RouteController::buses/$1');

    // GPS
    $routes->post('gps',               'GpsController::store');
    $routes->get('gps/buses/(:num)',   'GpsController::history/$1');

    // Incidents
    $routes->get('incidents',          'IncidentController::index');
    $routes->post('incidents',         'IncidentController::create');
    $routes->get('incidents/(:num)',   'IncidentController::show/$1');
    $routes->patch('incidents/(:num)', 'IncidentController::update/$1');
    $routes->patch('incidents/(:num)/resolve', 'IncidentController::resolve/$1');
    $routes->delete('incidents/(:num)', 'IncidentController::delete/$1');
});
