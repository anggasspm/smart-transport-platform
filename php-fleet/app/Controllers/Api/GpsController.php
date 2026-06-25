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

    /** Earth radius in KM, used by the Haversine formula. */
    private const EARTH_RADIUS_KM = 6371;

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
            'stop_id'         => 'permit_empty|integer',
            'next_stop_id'    => 'permit_empty|integer',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $busId   = (int) $this->request->getVar('bus_id');
        $routeId = (int) $this->request->getVar('route_id');
        $busLat  = (float) $this->request->getVar('lat');
        $busLng  = (float) $this->request->getVar('lng');

        // Validate bus exists
        if (! $this->busModel->find($busId)) {
            return $this->notFound("Bus #{$busId} not found in fleet_buses");
        }

        // Validate route exists
        if (! $this->routeModel->find($routeId)) {
            return $this->notFound("Route #{$routeId} not found in fleet_routes");
        }

        // -----------------------------------------------------------------------
        // Resolve target stop & calculate distance_to_stop
        // -----------------------------------------------------------------------
        $warnings       = [];
        $distanceToStop = null;
        $closestStopId  = null;
        $resolvedStopId = null;

        // Prefer stop_id, fall back to next_stop_id
        $requestedStopId = null;
        if ($this->request->getVar('stop_id') !== null && $this->request->getVar('stop_id') !== '') {
            $requestedStopId = (int) $this->request->getVar('stop_id');
        } elseif ($this->request->getVar('next_stop_id') !== null && $this->request->getVar('next_stop_id') !== '') {
            $requestedStopId = (int) $this->request->getVar('next_stop_id');
        }

        try {
            $stopResult = $this->resolveTargetStop($routeId, $busLat, $busLng, $requestedStopId);

            if ($stopResult !== null) {
                $resolvedStopId = $stopResult['id'];
                $closestStopId  = $stopResult['id'];
                $distanceToStop = $this->calculateDistanceKm(
                    $busLat,
                    $busLng,
                    (float) $stopResult['lat'],
                    (float) $stopResult['lng']
                );
            } else {
                $warnings[] = 'No stops found for route_id=' . $routeId . '. distance_to_stop set to null.';
            }
        } catch (\Throwable $e) {
            log_message('warning', '[GpsController] resolveTargetStop failed: ' . $e->getMessage());
            $warnings[] = 'Stop lookup failed: ' . $e->getMessage() . '. distance_to_stop set to null.';
        }

        // -----------------------------------------------------------------------
        // Normalise & persist GPS log (schema unchanged — no stop_id column)
        // -----------------------------------------------------------------------
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

        $log = $this->gpsModel->find($id);

        // -----------------------------------------------------------------------
        // Publish gps.update event (enriched with stop & distance fields)
        // -----------------------------------------------------------------------
        $mqPayload = [
            'event'            => 'gps.update',
            'bus_id'           => $normalised['bus_id'],
            'route_id'         => $normalised['route_id'],
            'stop_id'          => $resolvedStopId,
            'closest_stop_id'  => $closestStopId,
            'lat'              => $normalised['lat'],
            'lng'              => $normalised['lng'],
            'speed_kmh'        => $normalised['speed_kmh'],
            'heading'          => $normalised['heading'],
            'passenger_count'  => $normalised['passenger_count'],
            'engine_temp'      => $normalised['engine_temp'],
            'distance_to_stop' => $distanceToStop,
            'recorded_at'      => $normalised['recorded_at'],
        ];

        $result = $this->mq->publish('gps.update', $mqPayload);
        if ($result !== true) {
            $warnings[] = 'GPS logged but RabbitMQ publish failed: ' . $result;
        }

        // Build API response data (include distance fields for transparency)
        $responseData = array_merge($log, [
            'stop_id'          => $resolvedStopId,
            'closest_stop_id'  => $closestStopId,
            'distance_to_stop' => $distanceToStop,
        ]);

        return $this->success(
            $responseData,
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

        $limit = (int) ($this->request->getGet('limit') ?? 100);
        $limit = max(1, min($limit, 500)); // clamp 1–500
        $logs  = $this->gpsModel->getByBus($busId, $limit);

        return $this->success($logs, "GPS history for bus #{$busId}");
    }

    // =========================================================================
    // Helper: Haversine distance formula
    // =========================================================================

    /**
     * Calculate the great-circle distance between two lat/lng points.
     *
     * Uses the Haversine formula.
     * Returns the distance in **KM** (float, rounded to 2 decimal places).
     *
     * @param float $lat1 Latitude  of point 1 (bus position)
     * @param float $lng1 Longitude of point 1
     * @param float $lat2 Latitude  of point 2 (stop position)
     * @param float $lng2 Longitude of point 2
     */

    private function calculateDistanceKm(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2
    ): float {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 2);
    }

    // =========================================================================
    // Helper: Resolve target stop
    // =========================================================================

    /**
     * Determine the target stop for distance calculation.
     *
     * Strategy:
     *   1. If $requestedStopId is provided → validate it belongs to $routeId,
     *      then return that stop's coordinates.
     *   2. Fallback → find the geographically closest stop in stop_stops
     *      for the given $routeId.
     *
     * Returns an associative array with at least: ['id', 'lat', 'lng']
     * Returns null when no matching stop exists at all.
     *
     * @param int        $routeId         The bus's current route.
     * @param float      $busLat          Current bus latitude.
     * @param float      $busLng          Current bus longitude.
     * @param int|null   $requestedStopId Optional explicit stop_id / next_stop_id.
     *
     * @throws \RuntimeException If the explicit stop_id does not belong to the route.
     */
    private function resolveTargetStop(
        int   $routeId,
        float $busLat,
        float $busLng,
        ?int  $requestedStopId = null
    ): ?array {
        $db = \Config\Database::connect();

        // ------------------------------------------------------------------
        // Branch A: Explicit stop_id provided
        // ------------------------------------------------------------------
        if ($requestedStopId !== null) {
            $stop = $db->table('stop_stops')
                ->where('id', $requestedStopId)
                ->where('route_id', $routeId)
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($stop === null) {
                throw new \RuntimeException(
                    "stop_id={$requestedStopId} does not exist or does not belong to route_id={$routeId}"
                );
            }

            return $stop;
        }

        // ------------------------------------------------------------------
        // Branch B: No stop_id — find closest stop by Haversine
        // ------------------------------------------------------------------
        $stops = $db->table('stop_stops')
            ->where('route_id', $routeId)
            ->get()
            ->getResultArray();

        if (empty($stops)) {
            return null;
        }

        $closest    = null;
        $minDist    = PHP_FLOAT_MAX;

        foreach ($stops as $stop) {
            $dist = $this->calculateDistanceKm(
                $busLat,
                $busLng,
                (float) $stop['lat'],
                (float) $stop['lng']
            );

            if ($dist < $minDist) {
                $minDist = $dist;
                $closest = $stop;
            }
        }

        return $closest;
    }
}
