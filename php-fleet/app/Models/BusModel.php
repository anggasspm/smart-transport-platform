<?php

namespace App\Models;

use CodeIgniter\Model;

class BusModel extends Model
{
    protected $table         = 'fleet_buses';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'plate_number',
        'route_id',
        'capacity',
        'status',
        'driver_name',
    ];

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = '';   // tabel tidak punya updated_at
    protected $dateFormat     = 'datetime';

    // ─── Validation ──────────────────────────────────────────────

    protected $validationRules = [
        'plate_number' => 'required|max_length[20]|is_unique[fleet_buses.plate_number,id,{id}]',
        'route_id'     => 'permit_empty|integer|greater_than[0]',
        'capacity'     => 'required|integer|greater_than[0]',
        'status'       => 'permit_empty|in_list[active,inactive,maintenance,incident]',
        'driver_name'  => 'permit_empty|max_length[100]',
    ];

    protected $validationMessages = [
        'plate_number' => [
            'is_unique' => 'Plate number sudah terdaftar.',
        ],
    ];

    protected $skipValidation = false;

    // ─── Custom Methods ──────────────────────────────────────────

    /**
     * Ambil lokasi terakhir bus dari fleet_gps_logs.
     */
    public function getLastLocation(int $busId): ?array
    {
        $gpsModel = new GpsLogModel();
        return $gpsModel
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->first();
    }

    /**
     * Update status bus dan return data terbaru.
     */
    public function updateStatus(int $busId, string $status): bool
    {
        return $this->update($busId, ['status' => $status]);
    }
}
