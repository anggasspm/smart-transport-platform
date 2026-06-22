<?php
namespace App\Models;
use CodeIgniter\Model;

class AlertModel extends Model
{
    protected $table         = 'stop_alerts';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['stop_id', 'alert_type', 'severity', 'message', 'threshold', 'resolved_at'];
    protected $useTimestamps = false;

    public function getActiveAlerts(): array
    {
        return $this->where('resolved_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    public function createAlert(array $data): array
    {
        $id = $this->insert($data, true);
        return $this->find($id);
    }
}