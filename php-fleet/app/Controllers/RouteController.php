<?php

namespace App\Controllers;

use App\Models\RouteModel;
use App\Models\BusModel;

/**
 * RouteController
 *
 * GET    /api/routes
 * POST   /api/routes
 * GET    /api/routes/{id}
 * PATCH  /api/routes/{id}
 * DELETE /api/routes/{id}
 * GET    /api/routes/{id}/buses
 */
class RouteController extends BaseController
{
    private RouteModel $routeModel;

    public function __construct()
    {
        $this->routeModel = new RouteModel();
    }

    // ─── GET /api/routes ─────────────────────────────────────────

    public function index(): \CodeIgniter\HTTP\Response
    {
        $routes = $this->routeModel->orderBy('id', 'ASC')->findAll();

        return fleet_success($routes, 'Daftar rute berhasil diambil.', 200, [
            'total' => count($routes),
        ]);
    }

    // ─── POST /api/routes ────────────────────────────────────────

    public function create(): \CodeIgniter\HTTP\Response
    {
        $data = $this->getJsonBody();

        if (! $this->routeModel->validate($data)) {
            return fleet_validation_error($this->routeModel->errors());
        }

        $id = $this->routeModel->insert($data, true);
        if ($id === false) {
            return fleet_server_error('Gagal menyimpan rute.');
        }

        $route = $this->routeModel->find($id);

        return fleet_success($route, 'Rute berhasil dibuat.', 201);
    }

    // ─── GET /api/routes/{id} ────────────────────────────────────

    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return fleet_not_found('Route');
        }

        return fleet_success($route, 'Detail rute berhasil diambil.');
    }

    // ─── PATCH /api/routes/{id} ──────────────────────────────────

    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return fleet_not_found('Route');
        }

        $data = $this->getJsonBody();
        if (empty($data)) {
            return fleet_error('Request body tidak boleh kosong.', 400);
        }

        // Skip unique validation saat update (tidak ada field unique di route)
        $this->routeModel->skipValidation(false);

        if (! $this->routeModel->update($id, $data)) {
            $errors = $this->routeModel->errors();
            if (! empty($errors)) {
                return fleet_validation_error($errors);
            }
            return fleet_server_error('Gagal memperbarui rute.');
        }

        return fleet_success($this->routeModel->find($id), 'Rute berhasil diperbarui.');
    }

    // ─── DELETE /api/routes/{id} ─────────────────────────────────

    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return fleet_not_found('Route');
        }

        // Cek apakah masih ada bus di rute ini
        $busModel = new BusModel();
        $busCount = $busModel->where('route_id', $id)->countAllResults();
        if ($busCount > 0) {
            return fleet_error(
                "Rute tidak dapat dihapus. Masih ada {$busCount} bus terdaftar.",
                409
            );
        }

        $this->routeModel->delete($id);

        return fleet_success(null, 'Rute berhasil dihapus.');
    }

    // ─── GET /api/routes/{id}/buses ──────────────────────────────

    public function buses(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return fleet_not_found('Route');
        }

        $busModel = new BusModel();
        $buses = $busModel->where('route_id', $id)->orderBy('id', 'ASC')->findAll();

        return fleet_success($buses, "Daftar bus di rute #{$id}.", 200, [
            'route'  => $route,
            'total'  => count($buses),
        ]);
    }
}
