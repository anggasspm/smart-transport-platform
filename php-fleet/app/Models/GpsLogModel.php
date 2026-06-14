<?php

namespace App\Models;

use CodeIgniter\Model;

class GpsLogModel extends Model
{
    protected $table         = 'fleet_gps_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'bus_id',
        'route_id',
        'lat',
        'lng',
        'speed_kmh',
        'heading',
        'passenger_count',
        'engine_temp',
        'recorded_at',
    ];

    // recorded_at diisi manual dari parsed timestamp, bukan auto
    protected $useTimestamps = false;

    // ─── Validation ──────────────────────────────────────────────

    protected $validationRules = [
        'bus_id'   => 'required|integer|greater_than[0]',
        'route_id' => 'required|integer|greater_than[0]',
        'lat'      => 'required|decimal',
        'lng'      => 'required|decimal',
        'speed_kmh'=> 'required|decimal',
        'heading'  => 'required|decimal',
        // passenger_count & engine_temp divalidasi di GpsController
        // karena butuh permit_empty dengan nilai default
    ];

    protected $skipValidation = false;

    // ─── Custom Methods ──────────────────────────────────────────

    /**
     * Parse timestamp dari berbagai format menjadi string 'Y-m-d H:i:s'.
     *
     * @param int|string|null $timestamp
     */
    public static function parseTimestamp(mixed $timestamp): string
    {
        if (empty($timestamp)) {
            return date('Y-m-d H:i:s');
        }

        // Numeric: Unix epoch
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', (int) $timestamp);
        }

        // String: coba parse ISO 8601 / strtotime
        $parsed = strtotime((string) $timestamp);
        if ($parsed === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $parsed);
    }

    /**
     * Ambil riwayat GPS log untuk bus tertentu.
     * Default 50 record terakhir.
     */
    public function getHistory(int $busId, int $limit = 50): array
    {
        return $this
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Ambil posisi terakhir bus.
     */
    public function getLatest(int $busId): ?array
    {
        return $this
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->first();
    }
}
