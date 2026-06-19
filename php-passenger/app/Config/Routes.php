<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
// Passenger Service Routes
$routes->post('api/passengers', 'PassengerController::store');
$routes->get('api/passengers/(:num)', 'PassengerController::show/$1');
$routes->post('api/tickets', 'TicketController::store');
$routes->get('api/tickets', 'TicketController::index');
$routes->get('api/notifications', 'NotifController::index');
$routes->patch('api/notifications/(:num)/read', 'NotifController::markRead/$1');
$routes->get('health', 'HealthController::index');

// iot
$routes->get('api/passengers/by-card/(:any)', 'PassengerController::byCard/$1');
$routes->get('api/tickets/active/(:num)', 'TicketController::hasActiveTicket/$1');
$routes->post('api/tickets/check-and-create', 'TicketController::checkAndCreate');
$routes->post('api/tickets/checkout', 'TicketController::checkout');

// metrics
$routes->get('/metrics', 'Api\MetricsController::index');