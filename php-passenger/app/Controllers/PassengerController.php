<?php
namespace App\Controllers;
use App\Models\PassengerModel;
use CodeIgniter\HTTP\ResponseInterface;

class PassengerController extends BaseController
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
        $data = $this->request->getJSON(true) ?? [];

        if (empty($data['name']))     return $this->respond(422, null, 'name is required');
        if (empty($data['email']))    return $this->respond(422, null, 'email is required');
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return $this->respond(422, null, 'email is invalid');
        if (empty($data['password'])) return $this->respond(422, null, 'password is required');

        $model = new PassengerModel();
        if ($model->findByEmail($data['email'])) {
            return $this->respond(400, null, 'Email already registered');
        }

        $passenger = $model->createPassenger($data);
        return $this->respond(201, $passenger, 'Passenger registered successfully');
    }

    public function show(int $id): ResponseInterface
    {
        $model     = new PassengerModel();
        $passenger = $model->findSafe($id);

        if (!$passenger) return $this->respond(404, null, 'Passenger not found');
        return $this->respond(200, $passenger, 'OK');
    }
}