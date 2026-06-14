<?php
namespace App\Models;
use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table         = 'passenger_tickets';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['passenger_id', 'route_id', 'origin_stop_id', 'dest_stop_id', 'price', 'status'];
    protected $useTimestamps = false;

    public function getByPassenger(int $passengerId): array
    {
        return $this->where('passenger_id', $passengerId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}