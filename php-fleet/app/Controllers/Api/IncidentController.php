<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BusModel;
use App\Models\IncidentModel;
use App\Services\RabbitMQPublisher;
use App\Traits\ApiResponseTrait;

class IncidentController extends BaseController
{
    use ApiResponseTrait;

    private IncidentModel     $incidentModel;
    private BusModel          $busModel;
    private RabbitMQPublisher $mq;

    public function __construct()
    {
        $this->incidentModel = new IncidentModel();
        $this->busModel      = new BusModel();
        $this->mq            = new RabbitMQPublisher();
    }

    // -------------------------------------------------------------------------
    // GET /api/incidents
    // -------------------------------------------------------------------------
    public function index(): \CodeIgniter\HTTP\Response
    {
        $incidents = $this->incidentModel
            ->orderBy('reported_at', 'DESC')
            ->findAll();

        return $this->success($incidents, 'Incidents retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/incidents
    // -------------------------------------------------------------------------
    public function create(): \CodeIgniter\HTTP\Response
    {
        $rules = [
            'bus_id'      => 'required|integer',
            'type'        => 'required|in_list[breakdown,accident,traffic,maintenance,anomaly,other]',
            'severity'    => 'required|in_list[low,medium,high,critical]',
            'description' => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $busId = (int) $this->request->getVar('bus_id');

        $bus = $this->busModel->find($busId);
        if (! $bus) {
            return $this->notFound("Bus #{$busId} not found");
        }

        $data = [
            'bus_id'      => $busId,
            'type'        => $this->request->getVar('type'),
            'severity'    => $this->request->getVar('severity'),
            'description' => $this->request->getVar('description') ?: null,
            'resolved_at' => null,
            'reported_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->incidentModel->insert($data, true);
        if ($id === false) {
            return $this->serverError('Failed to create incident');
        }

        $incident = $this->incidentModel->find($id);
        $warnings = [];

        // Publish fleet.incident.created
        $r1 = $this->mq->publish('fleet.incident.created', [
            'event'       => 'fleet.incident.created',
            'incident_id' => $id,
            'bus_id'      => $busId,
            'type'        => $data['type'],
            'severity'    => $data['severity'],
            'reported_at' => $data['reported_at'],
        ]);
        if ($r1 !== true) {
            $warnings[] = 'RabbitMQ fleet.incident.created failed: ' . $r1;
        }

        // Update bus status to 'incident' and publish bus.status.updated
        $oldStatus = $bus['status'];
        if ($oldStatus !== 'incident') {
            $this->busModel->update($busId, ['status' => 'incident']);

            $r2 = $this->mq->publish('bus.status.updated', [
                'event'      => 'bus.status.updated',
                'bus_id'     => $busId,
                'old_status' => $oldStatus,
                'new_status' => 'incident',
                'timestamp'  => date('Y-m-d H:i:s'),
            ]);
            if ($r2 !== true) {
                $warnings[] = 'RabbitMQ bus.status.updated failed: ' . $r2;
            }
        }

        return $this->created(
            $incident,
            'Incident created',
            $warnings ? ['warnings' => $warnings] : []
        );
    }

    // -------------------------------------------------------------------------
    // GET /api/incidents/{id}
    // -------------------------------------------------------------------------
    public function show(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return $this->notFound("Incident #{$id} not found");
        }

        return $this->success($incident, 'Incident retrieved');
    }

    // -------------------------------------------------------------------------
    // PATCH /api/incidents/{id}
    // -------------------------------------------------------------------------
    public function update(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return $this->notFound("Incident #{$id} not found");
        }

        $rules = [
            'type'        => 'permit_empty|in_list[breakdown,accident,traffic,maintenance,anomaly,other]',
            'severity'    => 'permit_empty|in_list[low,medium,high,critical]',
            'description' => 'permit_empty',
        ];

        if (! $this->validate($rules)) {
            return $this->validationError($this->validator->getErrors());
        }

        $input = $this->request->getRawInput();
        if (empty($input)) {
            $input = (array) $this->request->getVar();
        }

        $allowed = ['type', 'severity', 'description'];
        $data    = array_intersect_key($input, array_flip($allowed));

        if (empty($data)) {
            return $this->error('No updatable fields provided');
        }

        $this->incidentModel->update($id, $data);
        $updated = $this->incidentModel->find($id);

        return $this->success($updated, 'Incident updated');
    }

    // -------------------------------------------------------------------------
    // PATCH /api/incidents/{id}/resolve
    // -------------------------------------------------------------------------
    public function resolve(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return $this->notFound("Incident #{$id} not found");
        }

        if ($incident['resolved_at'] !== null) {
            return $this->error("Incident #{$id} is already resolved", 409);
        }

        $this->incidentModel->resolve($id);
        $resolved = $this->incidentModel->find($id);
        $warnings = [];

        $busId = (int) $incident['bus_id'];
        $bus   = $this->busModel->find($busId);

        if ($bus) {
            // Check for other active (unresolved) incidents on this bus
            $activeIncidents = $this->incidentModel
                ->where('bus_id', $busId)
                ->where('resolved_at IS NULL')
                ->where('id !=', $id)
                ->countAllResults();

            if ($activeIncidents === 0 && $bus['status'] === 'incident') {
                $this->busModel->update($busId, ['status' => 'active']);

                $r = $this->mq->publish('bus.status.updated', [
                    'event'      => 'bus.status.updated',
                    'bus_id'     => $busId,
                    'old_status' => 'incident',
                    'new_status' => 'active',
                    'timestamp'  => date('Y-m-d H:i:s'),
                ]);
                if ($r !== true) {
                    $warnings[] = 'RabbitMQ bus.status.updated failed: ' . $r;
                }
            }
        }

        return $this->success(
            $resolved,
            "Incident #{$id} resolved",
            200,
            $warnings ? ['warnings' => $warnings] : []
        );
    }

    // -------------------------------------------------------------------------
    // DELETE /api/incidents/{id}
    // -------------------------------------------------------------------------
    public function delete(int $id): \CodeIgniter\HTTP\Response
    {
        $incident = $this->incidentModel->find($id);
        if (! $incident) {
            return $this->notFound("Incident #{$id} not found");
        }

        $this->incidentModel->delete($id);
        return $this->success(null, "Incident #{$id} deleted");
    }
}
