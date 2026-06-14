<?php

namespace App\Models;

use CodeIgniter\Model;

class IncidentModel extends Model
{
    protected $table         = 'fleet_incidents';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'bus_id',
        'type',
        'severity',
        'description',
        'resolved_at',
        'reported_at',
    ];

    // reported_at diisi otomatis DB (DEFAULT CURRENT_TIMESTAMP)
    // resolved_at diisi manual saat resolve
    protected $useTimestamps  = false;

    // ─── Validation ──────────────────────────────────────────────

    protected $validationRules = [
        'bus_id'      => 'required|integer|greater_than[0]',
        'type'        => 'required|in_list[breakdown,accident,traffic,maintenance,anomaly,other]',
        'severity'    => 'required|in_list[low,medium,high,critical]',
        'description' => 'required',
    ];

    protected $validationMessages = [
        'type'     => ['in_list' => 'Type harus salah satu dari: breakdown, accident, traffic, maintenance, anomaly, other.'],
        'severity' => ['in_list' => 'Severity harus salah satu dari: low, medium, high, critical.'],
    ];

    protected $skipValidation = false;

    // ─── Custom Methods ──────────────────────────────────────────

    /**
     * Resolve incident: set resolved_at ke waktu sekarang.
     */
    public function resolve(int $id): bool
    {
        return $this->update($id, [
            'resolved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Ambil incidents dengan filter opsional.
     *
     * @param array<string, mixed> $filters  Key: bus_id, severity, type, resolved
     */
    public function getFiltered(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $builder = $this->orderBy('reported_at', 'DESC');

        if (!empty($filters['bus_id'])) {
            $builder->where('bus_id', (int) $filters['bus_id']);
        }
        if (!empty($filters['severity'])) {
            $builder->where('severity', $filters['severity']);
        }
        if (!empty($filters['type'])) {
            $builder->where('type', $filters['type']);
        }
        if (isset($filters['resolved'])) {
            if ($filters['resolved']) {
                $builder->where('resolved_at IS NOT NULL', null, false);
            } else {
                $builder->where('resolved_at IS NULL', null, false);
            }
        }

        return $builder->findAll($limit, $offset);
    }
}
