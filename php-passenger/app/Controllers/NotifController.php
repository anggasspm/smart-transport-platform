<?php
namespace App\Controllers;
use App\Models\NotificationModel;
use CodeIgniter\HTTP\ResponseInterface;

class NotifController extends BaseController
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

    public function index(): ResponseInterface
    {
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $model = new NotificationModel();
        return $this->respond(200, $model->getByPassenger($passengerId), 'OK');
    }

    public function markRead(int $id): ResponseInterface
    {
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $model   = new NotificationModel();
        $updated = $model->markAsRead($id);
        if (!$updated) return $this->respond(404, null, 'Notification not found');
        return $this->respond(200, null, 'Notification marked as read');
    }
}