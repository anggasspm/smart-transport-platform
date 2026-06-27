<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RouteModel;
use App\Traits\ApiResponseTrait;

class RouteController extends BaseController
{
    use ApiResponseTrait;

    private RouteModel $routeModel;

    public function __construct()
    {
        $this->routeModel = new RouteModel();
    }

    // -------------------------------------------------------------------------
    // GET /api/routes
    // -------------------------------------------------------------------------
    public function index(): \CodeIgniter\HTTP\Response
    {
        $routes = $this->routeModel->findAll();
        return $this->success($routes, 'Routes retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/routes
    // -------------------------------------------------------------------------
    public function create(): \CodeIgniter\HTTP\Response
    {
        $rules = [
            'name'             => 'required|max_length[100]',
            'origin'           => 'required|max_length[100]',
            'destination'      => 'required|max_length[100]',
            'total_stops'      => 'permit_empty|integer|greater_than_equal_to[0]',
            'distance_km'      => 'permit_empty|decimal',
            'est_duration_min' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $data = [
            'name'             => $this->request->getVar('name'),
            'origin'           => $this->request->getVar('origin'),
            'destination'      => $this->request->getVar('destination'),
            'total_stops'      => (int) ($this->request->getVar('total_stops') ?? 0),
            'distance_km'      => (float) ($this->request->getVar('distance_km') ?? 0),
            'est_duration_min' => (int) ($this->request->getVar('est_duration_min') ?? 0),
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $id = $this->routeModel->insert($data, true);
        if ($id === false) {
            return $this->serverError('Failed to create route');
        }

        $route = $this->routeModel->find($id);
        return $this->created($route, 'Route created');
    }

    // -------------------------------------------------------------------------
    // GET /api/routes/{id}
    // -------------------------------------------------------------------------
    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return $this->notFound("Route #{$id} not found");
        }

        return $this->success($route, 'Route retrieved');
    }

    // -------------------------------------------------------------------------
    // PATCH /api/routes/{id}
    // -------------------------------------------------------------------------
    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return $this->notFound("Route #{$id} not found");
        }

        $rules = [
            'name'             => 'permit_empty|max_length[100]',
            'origin'           => 'permit_empty|max_length[100]',
            'destination'      => 'permit_empty|max_length[100]',
            'total_stops'      => 'permit_empty|integer|greater_than_equal_to[0]',
            'distance_km'      => 'permit_empty|decimal',
            'est_duration_min' => 'permit_empty|integer|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $input = $this->request->getJSON(true);
        if (empty($input)) {
            $input = $this->request->getRawInput();
        }
        if (empty($input)) {
            $input = (array) $this->request->getVar();
        }

        $allowed = ['name', 'origin', 'destination', 'total_stops', 'distance_km', 'est_duration_min'];
        $data    = array_intersect_key($input, array_flip($allowed));

        if (empty($data)) {
            return $this->error('No updatable fields provided');
        }

        $this->routeModel->update($id, $data);
        $updated = $this->routeModel->find($id);

        return $this->success($updated, 'Route updated');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/routes/{id}
    // -------------------------------------------------------------------------
    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return $this->notFound("Route #{$id} not found");
        }

        $this->routeModel->delete($id);
        return $this->success(null, "Route #{$id} deleted");
    }

    // -------------------------------------------------------------------------
    // GET /api/routes/{id}/buses
    // -------------------------------------------------------------------------
    public function buses(int $id): \CodeIgniter\HTTP\Response
    {
        $route = $this->routeModel->find($id);
        if (! $route) {
            return $this->notFound("Route #{$id} not found");
        }

        $buses = $this->routeModel->getBuses($id);
        return $this->success($buses, "Buses for route #{$id} retrieved");
    }
}