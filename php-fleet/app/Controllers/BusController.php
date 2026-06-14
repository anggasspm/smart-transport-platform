<?php

namespace App\Controllers;

use App\Models\BusModel;
use App\Models\GpsLogModel;
use App\Libraries\RabbitMQPublisher;

/**
 * BusController
 *
 * GET    /api/buses
 * POST   /api/buses
 * GET    /api/buses/{id}
 * PATCH  /api/buses/{id}
 * DELETE /api/buses/{id}
 * GET    /api/buses/{id}/location
 */
class BusController extends BaseController
{
    private BusModel $busModel;

    public function __construct()
    {
        $this->busModel = new BusModel();
    }

    // ─── GET /api/buses ──────────────────────────────────────────

    public function index(): \CodeIgniter\HTTP\Response
    {
        // Filter opsional: ?status=active&route_id=1
        $status  = $this->request->getGet('status');
        $routeId = $this->request->getGet('route_id');

        $builder = $this->busModel->orderBy('id', 'ASC');

        if ($status) {
            $allowed = ['active', 'inactive', 'maintenance', 'incident'];
            if (! in_array($status, $allowed, true)) {
                return fleet_error('Filter status tidak valid.', 400);
            }
            $builder->where('status', $status);
        }

        if ($routeId && is_numeric($routeId)) {
            $builder->where('route_id', (int) $routeId);
        }

        $buses = $builder->findAll();

        return fleet_success($buses, 'Daftar bus berhasil diambil.', 200, [
            'total' => count($buses),
        ]);
    }

    // ─── POST /api/buses ─────────────────────────────────────────

    public function create(): \CodeIgniter\HTTP\Response
    {
        $data = $this->getJsonBody();

        // Set default status jika tidak ada
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }

        if (! $this->busModel->validate($data)) {
            return fleet_validation_error($this->busModel->errors());
        }

        $id = $this->busModel->insert($data, true);
        if ($id === false) {
            return fleet_server_error('Gagal menyimpan bus.');
        }

        $bus = $this->busModel->find($id);

        return fleet_success($bus, 'Bus berhasil didaftarkan.', 201);
    }

    // ─── GET /api/buses/{id} ─────────────────────────────────────

    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return fleet_not_found('Bus');
        }

        return fleet_success($bus, 'Detail bus berhasil diambil.');
    }

    // ─── PATCH /api/buses/{id} ───────────────────────────────────

    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return fleet_not_found('Bus');
        }

        $data = $this->getJsonBody();
        if (empty($data)) {
            return fleet_error('Request body tidak boleh kosong.', 400);
        }

        // Validasi manual untuk field yang dikirim (partial update)
        $validation = \Config\Services::validation();

        $rules = [];
        if (array_key_exists('plate_number', $data)) {
            // Exclude current bus ID dari unique check
            $rules['plate_number'] = "required|max_length[20]|is_unique[fleet_buses.plate_number,id,{$id}]";
        }
        if (array_key_exists('route_id', $data)) {
            $rules['route_id'] = 'permit_empty|integer|greater_than[0]';
        }
        if (array_key_exists('capacity', $data)) {
            $rules['capacity'] = 'required|integer|greater_than[0]';
        }
        if (array_key_exists('status', $data)) {
            $rules['status'] = 'required|in_list[active,inactive,maintenance,incident]';
        }
        if (array_key_exists('driver_name', $data)) {
            $rules['driver_name'] = 'permit_empty|max_length[100]';
        }

        if (! empty($rules)) {
            $validation->setRules($rules);
            if (! $validation->run($data)) {
                return fleet_validation_error($validation->getErrors());
            }
        }

        // Deteksi perubahan status untuk publish RabbitMQ
        $oldStatus = $bus['status'];
        $newStatus = $data['status'] ?? null;
        $statusChanged = $newStatus !== null && $newStatus !== $oldStatus;

        $this->busModel->skipValidation(true)->update($id, $data);

        $updatedBus = $this->busModel->find($id);

        // Publish bus.status.updated jika status berubah
        $warning = null;
        if ($statusChanged) {
            try {
                (new RabbitMQPublisher())->publishBusStatusUpdated($id, $oldStatus, $newStatus);
            } catch (\Throwable $e) {
                log_message('warning', "[Fleet] bus.status.updated publish gagal bus_id={$id}: " . $e->getMessage());
                $warning = 'Event bus.status.updated gagal dipublish ke RabbitMQ.';
            }
        }

        $extra = $warning ? ['warning' => $warning] : [];

        return fleet_success($updatedBus, 'Bus berhasil diperbarui.', 200, $extra);
    }

    // ─── DELETE /api/buses/{id} ──────────────────────────────────

    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return fleet_not_found('Bus');
        }

        $this->busModel->delete($id);

        return fleet_success(null, 'Bus berhasil dihapus.');
    }

    // ─── GET /api/buses/{id}/location ───────────────────────────

    public function location(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return fleet_not_found('Bus');
        }

        $gpsModel = new GpsLogModel();
        $lastLog  = $gpsModel->getLatest($id);

        if (! $lastLog) {
            return fleet_success(null, "Bus #{$id} belum memiliki data GPS.", 200, [
                'bus' => $bus,
            ]);
        }

        return fleet_success([
            'bus'      => $bus,
            'location' => [
                'lat'             => (float) $lastLog['lat'],
                'lng'             => (float) $lastLog['lng'],
                'speed_kmh'       => (float) $lastLog['speed_kmh'],
                'heading'         => (float) $lastLog['heading'],
                'passenger_count' => (int) $lastLog['passenger_count'],
                'engine_temp'     => $lastLog['engine_temp'] !== null ? (float) $lastLog['engine_temp'] : null,
                'recorded_at'     => $lastLog['recorded_at'],
            ],
        ], "Lokasi terakhir bus #{$id}.");
    }
}
