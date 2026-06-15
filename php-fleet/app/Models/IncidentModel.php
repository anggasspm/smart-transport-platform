<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class IncidentModel extends Model
{
    protected $table            = 'fleet_incidents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'bus_id',
        'type',
        'severity',
        'description',
        'resolved_at',
        'reported_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'bus_id'      => 'required|integer',
        'type'        => 'required|in_list[breakdown,accident,traffic,maintenance,anomaly,other]',
        'severity'    => 'required|in_list[low,medium,high,critical]',
        'description' => 'permit_empty',
        'resolved_at' => 'permit_empty',
        'reported_at' => 'permit_empty',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Mark an incident as resolved by setting resolved_at to now.
     */
    public function resolve(int $id): bool
    {
        return $this->update($id, [
            'resolved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get all unresolved incidents, newest first.
     */
    public function getUnresolved(): array
    {
        return $this
            ->where('resolved_at IS NULL')
            ->orderBy('reported_at', 'DESC')
            ->findAll();
    }

    /**
     * Get incidents for a specific bus.
     */
    public function getByBus(int $busId): array
    {
        return $this
            ->where('bus_id', $busId)
            ->orderBy('reported_at', 'DESC')
            ->findAll();
    }
}
