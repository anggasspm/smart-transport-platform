<?php

namespace App\Controllers;

use App\Models\GpsLogModel;
use App\Models\BusModel;
use App\Models\RouteModel;
use App\Libraries\RabbitMQPublisher;

/**
 * GpsController
 *
 * POST /api/gps
 * GET  /api/gps/buses/{bus_id}
 */
class GpsController extends BaseController
{
    private GpsLogModel $gpsModel;

    public function __construct()
    {
        $this->gpsModel = new GpsLogModel();
    }

    // ─── POST /api/gps ───────────────────────────────────────────

    /**
     * Terima data GPS dari IoT Simulator via MQTT -> Node-RED -> API Gateway.
     *
     * Payload minimal:
     *   bus_id, route_id, lat, lng, speed_kmh, heading
     *   timestamp (opsional) - Unix int atau ISO string
     *
     * Payload opsional:
     *   passenger_count (default 0)
     *   engine_temp     (nullable)
     */
    public function store(): \CodeIgniter\HTTP\Response
    {
        $data = $this->getJsonBody();

        // ── 1. Validasi Input ────────────────────────────────────
        $validation = \Config\Services::validation();
        $validation->setRules([
            'bus_id'          => 'required|integer|greater_than[0]',
            'route_id'        => 'required|integer|greater_than[0]',
            'lat'             => 'required|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'lng'             => 'required|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'speed_kmh'       => 'required|decimal|greater_than_equal_to[0]',
            'heading'         => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[360]',
            'passenger_count' => 'permit_empty|integer|greater_than_equal_to[0]',
            'engine_temp'     => 'permit_empty|decimal',
            // timestamp tidak divalidasi tipe ketat karena bisa int atau string
        ]);

        if (! $validation->run($data)) {
            return fleet_validation_error($validation->getErrors());
        }

        $busId   = (int) $data['bus_id'];
        $routeId = (int) $data['route_id'];

        // ── 2. Pastikan bus_id ada di fleet_buses ─────────────────
        $busModel = new BusModel();
        $bus = $busModel->find($busId);
        if (! $bus) {
            return fleet_error("Bus dengan id={$busId} tidak ditemukan.", 404);
        }

        // ── 3. Pastikan route_id ada di fleet_routes ──────────────
        $routeModel = new RouteModel();
        $route = $routeModel->find($routeId);
        if (! $route) {
            return fleet_error("Route dengan id={$routeId} tidak ditemukan.", 404);
        }

        // ── 4. Parse timestamp ────────────────────────────────────
        $recordedAt = GpsLogModel::parseTimestamp($data['timestamp'] ?? null);

        // ── 5. Simpan ke fleet_gps_logs ───────────────────────────
        $logData = [
            'bus_id'          => $busId,
            'route_id'        => $routeId,
            'lat'             => (float) $data['lat'],
            'lng'             => (float) $data['lng'],
            'speed_kmh'       => (float) $data['speed_kmh'],
            'heading'         => (float) $data['heading'],
            'passenger_count' => isset($data['passenger_count']) ? (int) $data['passenger_count'] : 0,
            'engine_temp'     => isset($data['engine_temp']) && $data['engine_temp'] !== '' && $data['engine_temp'] !== null
                                    ? (float) $data['engine_temp']
                                    : null,
            'recorded_at'     => $recordedAt,
        ];

        // Skip model-level validation karena sudah divalidasi manual di atas
        $logId = $this->gpsModel->skipValidation(true)->insert($logData, true);

        if ($logId === false) {
            return fleet_server_error('Gagal menyimpan GPS log.');
        }

        $savedLog = array_merge(['id' => $logId], $logData);

        // ── 6. Publish RabbitMQ gps.update ───────────────────────
        $warning = null;
        try {
            (new RabbitMQPublisher())->publishGpsUpdate($savedLog);
        } catch (\Throwable $e) {
            log_message('warning', "[Fleet] gps.update publish gagal bus_id={$busId}: " . $e->getMessage());
            $warning = 'GPS log tersimpan. Event gps.update gagal dipublish ke RabbitMQ.';
        }

        // ── 7. Response ───────────────────────────────────────────
        $responseData = [
            'id'              => $logId,
            'bus_id'          => $busId,
            'route_id'        => $routeId,
            'lat'             => $logData['lat'],
            'lng'             => $logData['lng'],
            'speed_kmh'       => $logData['speed_kmh'],
            'heading'         => $logData['heading'],
            'passenger_count' => $logData['passenger_count'],
            'engine_temp'     => $logData['engine_temp'],
            'recorded_at'     => $recordedAt,
        ];

        $extra = $warning ? ['warning' => $warning] : [];
        $message = $warning
            ? 'GPS log tersimpan (event publish gagal).'
            : 'GPS log berhasil disimpan dan event dipublish.';

        return fleet_success($responseData, $message, 201, $extra);
    }

    // ─── GET /api/gps/buses/{bus_id} ─────────────────────────────

    /**
     * Ambil riwayat GPS log untuk bus tertentu.
     * Query param: ?limit=50
     */
    public function history(int $busId): \CodeIgniter\HTTP\Response
    {
        // Pastikan bus ada
        $busModel = new BusModel();
        $bus = $busModel->find($busId);
        if (! $bus) {
            return fleet_not_found('Bus');
        }

        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $limit = max(1, min($limit, 200)); // clamp 1-200

        $logs = $this->gpsModel->getHistory($busId, $limit);

        return fleet_success($logs, "Riwayat GPS bus #{$busId}.", 200, [
            'bus'   => $bus,
            'total' => count($logs),
            'limit' => $limit,
        ]);
    }
}
