<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class RouteModel extends Model
{
    protected $table            = 'fleet_routes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'name',
        'origin',
        'destination',
        'total_stops',
        'distance_km',
        'est_duration_min',
        'created_at',
    ];

    // Timestamps
    protected $useTimestamps = false; // managed manually via created_at

    // Validation
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

    /**
     * Get all buses assigned to a specific route.
     */
    public function getBuses(int $routeId): array
    {
        return $this->db
            ->table('fleet_buses')
            ->where('route_id', $routeId)
            ->get()
            ->getResultArray();
    }
}
