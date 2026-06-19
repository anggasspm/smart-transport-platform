<?php
namespace App\Controllers;
use App\Models\StopModel;
use App\Models\PassengerCountModel;
use CodeIgniter\HTTP\ResponseInterface;

class StopController extends BaseController
{
    private const CAPACITY = 50;

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
        $model = new StopModel();
        return $this->respond(200, $model->getAllStops(), 'OK');
    }

    public function status(int $id): ResponseInterface
    {
        $stopModel  = new StopModel();
        $countModel = new PassengerCountModel();

        $stop = $stopModel->getStopById($id);
        if (!$stop) return $this->respond(404, null, 'Stop not found');

        $latest = $countModel->getLatestByStop($id);
        $load   = $latest['current_load'] ?? 0;
        $pct    = ($load / self::CAPACITY) * 100;

        if ($pct < 25)      $status = 'Sepi';
        elseif ($pct < 60)  $status = 'Normal';
        elseif ($pct < 90)  $status = 'Padat';
        else                $status = 'Penuh';

        return $this->respond(200, [
            'stop'         => $stop,
            'current_load' => $load,
            'capacity'     => self::CAPACITY,
            'percentage'   => round($pct, 2),
            'status'       => $status
        ], 'OK');
    }
}