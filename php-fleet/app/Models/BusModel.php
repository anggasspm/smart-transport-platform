<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BusModel extends Model
{
    protected $table            = 'fleet_buses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'plate_number',
        'route_id',
        'capacity',
        'status',
        'driver_name',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'plate_number' => 'required|max_length[20]|is_unique[fleet_buses.plate_number,id,{id}]',
        'route_id'     => 'permit_empty|integer',
        'capacity'     => 'permit_empty|integer|greater_than[0]',
        'status'       => 'permit_empty|in_list[active,inactive,maintenance,incident]',
        'driver_name'  => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get the latest GPS log for this bus (last known location).
     */
    public function getLatestLocation(int $busId): ?array
    {
        $row = $this->db
            ->table('fleet_gps_logs')
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Get all buses for a given route.
     */
    public function getByRoute(int $routeId): array
    {
        return $this->where('route_id', $routeId)->findAll();
    }
}
