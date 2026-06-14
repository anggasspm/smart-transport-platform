<?php
namespace App\Controllers;
use App\Models\TicketModel;
use App\Services\RabbitMQPublisher;
use CodeIgniter\HTTP\ResponseInterface;

class TicketController extends BaseController
{
    private function respond(int $code, $data, string $message): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'    => $code < 400 ? 'success' : 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c'),
            'service'   => 'passenger-service'
        ]);
    }

    public function store(): ResponseInterface
    {
        // Gateway inject X-User-Id setelah verifikasi JWT
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $data = $this->request->getJSON(true) ?? [];

        if (empty($data['route_id']))       return $this->respond(422, null, 'route_id is required');
        if (empty($data['origin_stop_id'])) return $this->respond(422, null, 'origin_stop_id is required');
        if (empty($data['dest_stop_id']))   return $this->respond(422, null, 'dest_stop_id is required');
        if (empty($data['price']))          return $this->respond(422, null, 'price is required');

        $model  = new TicketModel();
        $data['passenger_id'] = $passengerId;
        $data['status']       = 'active';
        $id     = $model->insert($data, true);
        $ticket = $model->find($id);

        RabbitMQPublisher::publish('ticket.purchased', [
            'ticket_id'    => $ticket['id'],
            'passenger_id' => $passengerId,
            'route_id'     => $ticket['route_id'],
            'timestamp'    => date('c')
        ]);

        return $this->respond(201, $ticket, 'Ticket purchased successfully');
    }

    public function index(): ResponseInterface
    {
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $model   = new TicketModel();
        $tickets = $model->getByPassenger($passengerId);
        return $this->respond(200, $tickets, 'OK');
    }
}