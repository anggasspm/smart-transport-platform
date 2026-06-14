<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BusModel;
use App\Models\GpsLogModel;
use App\Models\RouteModel;
use App\Services\RabbitMQPublisher;
use App\Traits\ApiResponseTrait;

class GpsController extends BaseController
{
    use ApiResponseTrait;

    private GpsLogModel       $gpsModel;
    private BusModel          $busModel;
    private RouteModel        $routeModel;
    private RabbitMQPublisher $mq;

    public function __construct()
    {
        $this->gpsModel   = new GpsLogModel();
        $this->busModel   = new BusModel();
        $this->routeModel = new RouteModel();
        $this->mq         = new RabbitMQPublisher();
    }

    // -------------------------------------------------------------------------
    // POST /api/gps
    // -------------------------------------------------------------------------
    public function create(): \CodeIgniter\HTTP\Response
    {
        $rules = [
            'bus_id'          => 'required|integer',
            'route_id'        => 'required|integer',
            'lat'             => 'required|decimal',
            'lng'             => 'required|decimal',
            'speed_kmh'       => 'permit_empty|decimal',
            'heading'         => 'permit_empty|decimal',
            'passenger_count' => 'permit_empty|integer|greater_than_equal_to[0]',
            'engine_temp'     => 'permit_empty|decimal',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $busId   = (int) $this->request->getVar('bus_id');
        $routeId = (int) $this->request->getVar('route_id');

        // Validate bus exists
        if (! $this->busModel->find($busId)) {
            return $this->notFound("Bus #{$busId} not found in fleet_buses");
        }

        // Validate route exists
        if (! $this->routeModel->find($routeId)) {
            return $this->notFound("Route #{$routeId} not found in fleet_routes");
        }

        // Normalise full payload using model helper
        $raw = [
            'bus_id'          => $busId,
            'route_id'        => $routeId,
            'lat'             => $this->request->getVar('lat'),
            'lng'             => $this->request->getVar('lng'),
            'speed_kmh'       => $this->request->getVar('speed_kmh'),
            'heading'         => $this->request->getVar('heading'),
            'passenger_count' => $this->request->getVar('passenger_count'),
            'engine_temp'     => $this->request->getVar('engine_temp'),
            'timestamp'       => $this->request->getVar('timestamp'),
        ];

        $normalised = $this->gpsModel->normalise($raw);

        $id = $this->gpsModel->insert($normalised, true);
        if ($id === false) {
            return $this->serverError('Failed to save GPS log');
        }

        $log      = $this->gpsModel->find($id);
        $warnings = [];

        // Publish gps.update event
        $mqPayload = [
            'event'           => 'gps.update',
            'bus_id'          => $normalised['bus_id'],
            'route_id'        => $normalised['route_id'],
            'lat'             => $normalised['lat'],
            'lng'             => $normalised['lng'],
            'speed_kmh'       => $normalised['speed_kmh'],
            'heading'         => $normalised['heading'],
            'passenger_count' => $normalised['passenger_count'],
            'engine_temp'     => $normalised['engine_temp'],
            'recorded_at'     => $normalised['recorded_at'],
        ];

        $result = $this->mq->publish('gps.update', $mqPayload);
        if ($result !== true) {
            $warnings[] = 'GPS logged but RabbitMQ publish failed: ' . $result;
        }

        return $this->success(
            $log,
            'GPS log saved',
            201,
            $warnings ? ['warnings' => $warnings] : []
        );
    }

    // -------------------------------------------------------------------------
    // GET /api/gps/buses/{bus_id}
    // -------------------------------------------------------------------------
    public function busHistory(int $busId): \CodeIgniter\HTTP\Response
    {
        if (! $this->busModel->find($busId)) {
            return $this->notFound("Bus #{$busId} not found");
        }

        $limit  = (int) ($this->request->getGet('limit') ?? 100);
        $limit  = max(1, min($limit, 500)); // clamp 1–500
        $logs   = $this->gpsModel->getByBus($busId, $limit);

        return $this->success($logs, "GPS history for bus #{$busId}");
    }
}
