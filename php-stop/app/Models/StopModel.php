<?php
namespace App\Models;
use CodeIgniter\Model;

class StopModel extends Model
{
    protected $table         = 'stop_stops';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'route_id', 'lat', 'lng', 'zone_id', 'sequence_order'];
    protected $useTimestamps = false;

    public function getAllStops(): array
    {
        return $this->findAll();
    }

    public function getStopById(int $id): ?array
    {
        return $this->find($id);
    }
}