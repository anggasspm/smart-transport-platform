<?php
namespace App\Controllers;
use App\Models\AlertModel;
use CodeIgniter\HTTP\ResponseInterface;

class AlertController extends BaseController
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

    public function index(): ResponseInterface
    {
        $model = new AlertModel();
        return $this->respond(200, $model->getActiveAlerts(), 'OK');
    }
}