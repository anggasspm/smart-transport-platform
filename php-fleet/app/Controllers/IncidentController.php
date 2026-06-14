<?php

namespace App\Controllers;

use App\Models\IncidentModel;
use App\Models\BusModel;
use App\Libraries\RabbitMQPublisher;

/**
 * IncidentController
 *
 * GET    /api/incidents
 * POST   /api/incidents
 * GET    /api/incidents/{id}
 * PATCH  /api/incidents/{id}
 * PATCH  /api/incidents/{id}/resolve
 * DELETE /api/incidents/{id}
 */
class IncidentController extends BaseController
{
    private IncidentModel $incidentModel;

    public function __construct()
    {
        $this->incidentModel = new IncidentModel();
    }

    // ─── GET /api/incidents ──────────────────────────────────────

    /**
     * Ambil daftar incidents dengan filter opsional.
     * Query params: ?bus_id=1&severity=high&type=breakdown&resolved=0&limit=50
     */
    public function index(): \CodeIgniter\HTTP\Response
    {
        $filters = [
            'bus_id'   => $this->request->getGet('bus_id'),
            'severity' => $this->request->getGet('severity'),
            'type'     => $this->request->getGet('type'),
        ];

        // resolved: 0 = belum resolved, 1 = sudah resolved, null = semua
        $resolvedParam = $this->request->getGet('resolved');
        if ($resolvedParam !== null) {
            $filters['resolved'] = (bool) (int) $resolvedParam;
        }

        // Bersihkan filter null
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '');

        $limit  = (int) ($this->request->getGet('limit') ?? 50);
        $limit  = max(1, min($limit, 200));
        $offset = (int) ($this->request->getGet('offset') ?? 0);

        $incidents = $this->incidentModel->getFiltered($filters, $limit, $offset);

        return fleet_success($incidents, 'Daftar incident berhasil diambil.', 200, [
            'total'  => count($incidents),
            'limit'  => $limit,
            'offset' => $offset,
        ]);
    }

    // ─── POST /api/incidents ─────────────────────────────────────

    public function create(): \CodeIgniter\HTTP\Response
    {
        $data = $this->getJsonBody();

        // ── Validasi ─────────────────────────────────────────────
        if (! $this->incidentModel->validate($data)) {
            return fleet_validation_error($this->incidentModel->errors());
        }

        $busId = (int) $data['bus_id'];

        // Pastikan bus ada
        $busModel = new BusModel();
        $bus = $busModel->find($busId);
        if (! $bus) {
            return fleet_error("Bus dengan id={$busId} tidak ditemukan.", 404);
        }

        // ── Simpan ───────────────────────────────────────────────
        $insertData = [
            'bus_id'      => $busId,
            'type'        => $data['type'],
            'severity'    => $data['severity'],
            'description' => $data['description'],
        ];

        $id = $this->incidentModel->insert($insertData, true);
        if ($id === false) {
            return fleet_server_error('Gagal menyimpan incident.');
        }

        $incident = $this->incidentModel->find($id);

        // ── Publish fleet.incident.created ───────────────────────
        $warning = null;
        try {
            (new RabbitMQPublisher())->publishIncidentCreated($incident);
        } catch (\Throwable $e) {
            log_message('warning', "[Fleet] fleet.incident.created publish gagal id={$id}: " . $e->getMessage());
            $warning = 'Incident tersimpan. Event fleet.incident.created gagal dipublish ke RabbitMQ.';
        }

        $extra = $warning ? ['warning' => $warning] : [];
        $message = $warning
            ? 'Incident tersimpan (event publish gagal).'
            : 'Incident berhasil dilaporkan dan event dipublish.';

        return fleet_success($incident, $message, 201, $extra);
    }

    // ─── GET /api/incidents/{id} ─────────────────────────────────

    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return fleet_not_found('Incident');
        }

        return fleet_success($incident, 'Detail incident berhasil diambil.');
    }

    // ─── PATCH /api/incidents/{id} ───────────────────────────────

    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return fleet_not_found('Incident');
        }

        $data = $this->getJsonBody();
        if (empty($data)) {
            return fleet_error('Request body tidak boleh kosong.', 400);
        }

        // Partial validation
        $validation = \Config\Services::validation();
        $rules = [];

        if (array_key_exists('type', $data)) {
            $rules['type'] = 'required|in_list[breakdown,accident,traffic,maintenance,anomaly,other]';
        }
        if (array_key_exists('severity', $data)) {
            $rules['severity'] = 'required|in_list[low,medium,high,critical]';
        }
        if (array_key_exists('description', $data)) {
            $rules['description'] = 'required';
        }
        if (array_key_exists('bus_id', $data)) {
            $rules['bus_id'] = 'required|integer|greater_than[0]';
        }

        if (! empty($rules)) {
            $validation->setRules($rules);
            if (! $validation->run($data)) {
                return fleet_validation_error($validation->getErrors());
            }
        }

        // Hapus resolved_at dari payload update biasa (gunakan endpoint /resolve)
        unset($data['resolved_at'], $data['reported_at']);

        $this->incidentModel->update($id, $data);

        return fleet_success($this->incidentModel->find($id), 'Incident berhasil diperbarui.');
    }

    // ─── PATCH /api/incidents/{id}/resolve ───────────────────────

    public function resolve(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return fleet_not_found('Incident');
        }

        // Sudah resolved?
        if ($incident['resolved_at'] !== null) {
            return fleet_error('Incident sudah di-resolve sebelumnya.', 409, null, [
                'resolved_at' => $incident['resolved_at'],
            ]);
        }

        $this->incidentModel->resolve($id);

        $updatedIncident = $this->incidentModel->find($id);

        return fleet_success($updatedIncident, "Incident #{$id} berhasil di-resolve.");
    }

    // ─── DELETE /api/incidents/{id} ──────────────────────────────

    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return fleet_not_found('Incident');
        }

        $this->incidentModel->delete($id);

        return fleet_success(null, 'Incident berhasil dihapus.');
    }
}
