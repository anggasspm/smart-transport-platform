<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BusModel;
use App\Services\RabbitMQPublisher;
use App\Traits\ApiResponseTrait;

class BusController extends BaseController
{
    use ApiResponseTrait;

    private BusModel          $busModel;
    private RabbitMQPublisher $mq;

    public function __construct()
    {
        $this->busModel = new BusModel();
        $this->mq       = new RabbitMQPublisher();
    }

    // -------------------------------------------------------------------------
    // GET /api/buses
    // -------------------------------------------------------------------------
    public function index(): \CodeIgniter\HTTP\Response
    {
        $buses = $this->busModel->findAll();
        return $this->success($buses, 'Buses retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/buses
    // -------------------------------------------------------------------------
    public function create(): \CodeIgniter\HTTP\Response
    {
        $rules = [
            'plate_number' => 'required|max_length[20]|is_unique[fleet_buses.plate_number]',
            'route_id'     => 'permit_empty|integer',
            'capacity'     => 'permit_empty|integer|greater_than[0]',
            'status'       => 'permit_empty|in_list[active,inactive,maintenance,incident]',
            'driver_name'  => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $data = [
            'plate_number' => $this->request->getVar('plate_number'),
            'route_id'     => $this->request->getVar('route_id') ?: null,
            'capacity'     => (int) ($this->request->getVar('capacity') ?? 40),
            'status'       => $this->request->getVar('status') ?? 'active',
            'driver_name'  => $this->request->getVar('driver_name') ?: null,
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $id = $this->busModel->insert($data, true);
        if ($id === false) {
            return $this->serverError('Failed to create bus');
        }

        $bus = $this->busModel->find($id);
        return $this->created($bus, 'Bus created');
    }

    // -------------------------------------------------------------------------
    // GET /api/buses/{id}
    // -------------------------------------------------------------------------
    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return $this->notFound("Bus #{$id} not found");
        }

        return $this->success($bus, 'Bus retrieved');
    }

    // -------------------------------------------------------------------------
    // PATCH /api/buses/{id}
    // -------------------------------------------------------------------------
    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return $this->notFound("Bus #{$id} not found");
        }

        $rules = [
            'plate_number' => "permit_empty|max_length[20]|is_unique[fleet_buses.plate_number,id,{$id}]",
            'route_id'     => 'permit_empty|integer',
            'capacity'     => 'permit_empty|integer|greater_than[0]',
            'status'       => 'permit_empty|in_list[active,inactive,maintenance,incident]',
            'driver_name'  => 'permit_empty|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $input = $this->request->getRawInput();
        // Support both JSON body and form-encoded PATCH
        if (empty($input)) {
            $input = (array) $this->request->getVar();
        }

        $allowed  = ['plate_number', 'route_id', 'capacity', 'status', 'driver_name'];
        $data     = array_intersect_key($input, array_flip($allowed));

        if (empty($data)) {
            return $this->error('No updatable fields provided');
        }

        $oldStatus = $bus['status'];
        $this->busModel->update($id, $data);

        $updated  = $this->busModel->find($id);
        $warnings = [];

        // Publish RabbitMQ event if status changed
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $result = $this->mq->publish('bus.status.updated', [
                'event'      => 'bus.status.updated',
                'bus_id'     => $id,
                'old_status' => $oldStatus,
                'new_status' => $data['status'],
                'timestamp'  => date('Y-m-d H:i:s'),
            ]);
            if ($result !== true) {
                $warnings[] = 'Bus updated but RabbitMQ publish failed: ' . $result;
            }
        }

        return $this->success($updated, 'Bus updated', 200, $warnings ? ['warnings' => $warnings] : []);
    }

    // -------------------------------------------------------------------------
    // DELETE /api/buses/{id}
    // -------------------------------------------------------------------------
    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return $this->notFound("Bus #{$id} not found");
        }

        $this->busModel->delete($id);
        return $this->success(null, "Bus #{$id} deleted");
    }

    // -------------------------------------------------------------------------
    // GET /api/buses/{id}/location
    // -------------------------------------------------------------------------
    public function location(int $id): \CodeIgniter\HTTP\Response
    {
        $bus = $this->busModel->find($id);
        if (! $bus) {
            return $this->notFound("Bus #{$id} not found");
        }

        $location = $this->busModel->getLatestLocation($id);
        if (! $location) {
            return $this->success(null, "No GPS data available for bus #{$id}");
        }

        return $this->success($location, 'Last known location retrieved');
    }
}
