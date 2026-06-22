<?php
namespace App\Controllers;
use App\Models\PassengerCountModel;
use App\Services\RabbitMQPublisher;
use CodeIgniter\HTTP\ResponseInterface;

class CountController extends BaseController
{
    private function respond(int $code, $data, string $message): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'    => $code < 400 ? 'success' : 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c'),
            'service'   => 'stop-service'
        ]);
    }

    public function store(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        if (empty($data['stop_id'])) return $this->respond(422, null, 'stop_id is required');
        if (empty($data['bus_id']))  return $this->respond(422, null, 'bus_id is required');

        $payload = [
            'stop_id'      => $data['stop_id'],
            'bus_id'       => $data['bus_id'],
            'boarded'      => $data['boarded']      ?? 0,
            'alighted'     => $data['alighted']     ?? 0,
            'current_load' => $data['current_load'] ?? 0,
            'recorded_at'  => date('Y-m-d H:i:s'),
        ];

        $model = new PassengerCountModel();
        $count = $model->createCount($payload);

        RabbitMQPublisher::publish('passenger.boarded', [
            'stop_id'      => $count['stop_id'],
            'bus_id'       => $count['bus_id'],
            'boarded'      => $count['boarded'],
            'current_load' => $count['current_load'],
            'timestamp'    => date('c')
        ]);

        return $this->respond(201, $count, 'Passenger count recorded');
    }
}