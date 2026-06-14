<?php
namespace App\Models;
use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'passenger_notifications';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['passenger_id', 'title', 'body', 'type', 'is_read'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getByPassenger(int $passengerId): array
    {
        return $this->where('passenger_id', $passengerId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    public function markAsRead(int $id): bool
    {
        // Cek dulu apakah notifikasi ada
        $notif = $this->find($id);
        if (!$notif) return false;

        return $this->update($id, ['is_read' => 1]);
    }
}