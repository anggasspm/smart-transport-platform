<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class GpsLogModel extends Model
{
    protected $table            = 'fleet_gps_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

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

    protected $useTimestamps = false;

    protected $validationRules = [
        'bus_id'          => 'required|integer',
        'route_id'        => 'required|integer',
        'lat'             => 'required|decimal',
        'lng'             => 'required|decimal',
        'speed_kmh'       => 'permit_empty|decimal',
        'heading'         => 'permit_empty|decimal',
        'passenger_count' => 'permit_empty|integer|greater_than_equal_to[0]',
        'engine_temp'     => 'permit_empty|decimal',
        'recorded_at'     => 'required',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Get GPS history for a specific bus, newest first.
     *
     * @param int $busId
     * @param int $limit
     */
    public function getByBus(int $busId, int $limit = 100): array
    {
        return $this
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get the latest GPS entry for a bus.
     */
    public function getLatest(int $busId): ?array
    {
        $row = $this
            ->where('bus_id', $busId)
            ->orderBy('recorded_at', 'DESC')
            ->first();

        return $row ?: null;
    }

    /**
     * Normalise a GPS payload from the IoT simulator.
     * Accepts Unix int or ISO-8601 string for timestamp.
     *
     * @param array $payload Raw incoming payload
     */
    public function normalise(array $payload): array
    {
        $ts = $payload['timestamp'] ?? null;

        if (is_int($ts) || (is_string($ts) && ctype_digit($ts))) {
            $recordedAt = date('Y-m-d H:i:s', (int) $ts);
        } elseif (is_string($ts) && $ts !== '') {
            $recordedAt = date('Y-m-d H:i:s', strtotime($ts));
        } else {
            $recordedAt = date('Y-m-d H:i:s');
        }

        return [
            'bus_id'          => (int) ($payload['bus_id'] ?? 0),
            'route_id'        => (int) ($payload['route_id'] ?? 0),
            'lat'             => (float) ($payload['lat'] ?? 0),
            'lng'             => (float) ($payload['lng'] ?? 0),
            'speed_kmh'       => (float) ($payload['speed_kmh'] ?? 0),
            'heading'         => (float) ($payload['heading'] ?? 0),
            'passenger_count' => (int) ($payload['passenger_count'] ?? 0),
            'engine_temp'     => isset($payload['engine_temp']) ? (float) $payload['engine_temp'] : null,
            'recorded_at'     => $recordedAt,
        ];
    }
}
