<?php
namespace App\Models;
use CodeIgniter\Model;

class PassengerCountModel extends Model
{
    protected $table         = 'stop_passenger_counts';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['stop_id', 'bus_id', 'boarded', 'alighted', 'current_load', 'recorded_at'];
    protected $useTimestamps = false;

    public function getLatestByStop(int $stopId): ?array
    {
        return $this->where('stop_id', $stopId)
                    ->orderBy('recorded_at', 'DESC')
                    ->first();
    }

    public function createCount(array $data): array
    {
        $id = $this->insert($data, true);
        return $this->find($id);
    }
}