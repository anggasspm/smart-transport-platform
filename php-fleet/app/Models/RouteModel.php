<?php

namespace App\Models;

use CodeIgniter\Model;

class RouteModel extends Model
{
    protected $table         = 'fleet_routes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'name',
        'origin',
        'destination',
        'total_stops',
        'distance_km',
        'est_duration_min',
    ];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = '';   // tabel tidak punya updated_at
    protected $dateFormat     = 'datetime';

    // ─── Validation ──────────────────────────────────────────────

    protected $validationRules = [
        'name'             => 'required|max_length[100]',
        'origin'           => 'required|max_length[100]',
        'destination'      => 'required|max_length[100]',
        'total_stops'      => 'permit_empty|integer|greater_than_equal_to[0]',
        'distance_km'      => 'permit_empty|decimal',
        'est_duration_min' => 'permit_empty|integer|greater_than_equal_to[0]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    // ─── Custom Methods ──────────────────────────────────────────

    /**
     * Kembalikan daftar bus yang terdaftar di rute ini.
     */
    public function getBuses(int $routeId): array
    {
        $busModel = new BusModel();
        return $busModel->where('route_id', $routeId)->findAll();
    }
}
