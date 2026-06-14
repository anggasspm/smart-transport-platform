<?php
namespace App\Models;
use CodeIgniter\Model;

class PassengerModel extends Model
{
    protected $table         = 'passenger_passengers';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['name', 'email', 'phone', 'card_number', 'balance', 'zone_id', 'password', 'role'];
    protected $useTimestamps = false;

    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function createPassenger(array $data): array
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $id = $this->insert($data, true);
        return $this->findSafe($id);
    }

    public function findSafe(int $id): ?array
    {
        return $this->select('id, name, email, phone, card_number, balance, zone_id, role, created_at')
                    ->find($id);
    }
}