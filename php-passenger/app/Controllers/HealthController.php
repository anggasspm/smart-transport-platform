<?php
namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;

class HealthController extends BaseController
{
    public function index(): ResponseInterface
    {
        try {
            db_connect()->query('SELECT 1');
            return $this->response->setJSON([
                'status'    => 'success',
                'code'      => 200,
                'data'      => ['db' => 'connected'],
                'message'   => 'OK',
                'timestamp' => date('c'),
                'service'   => 'passenger-service'
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'DB down: ' . $e->getMessage()
            ]);
        }
    }
}