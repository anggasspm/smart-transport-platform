<?php

namespace App\Controllers;

use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * HealthController
 *
 * GET /health
 *
 * Mengecek koneksi DB dan status RabbitMQ.
 * Digunakan oleh Docker HEALTHCHECK dan API Gateway.
 */
class HealthController extends BaseController
{
    public function index(): \CodeIgniter\HTTP\Response
    {
        $checks  = [];
        $healthy = true;

        // ─── Database Check ───────────────────────────────────
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            $checks['database'] = [
                'status'   => 'up',
                'driver'   => $db->DBDriver,
                'database' => $db->getDatabase(),
            ];
        } catch (DatabaseException|\Throwable $e) {
            $checks['database'] = [
                'status' => 'down',
                'error'  => $e->getMessage(),
            ];
            $healthy = false;
        }

        // ─── RabbitMQ Check ───────────────────────────────────
        try {
            $host    = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
            $port    = (int) (getenv('RABBITMQ_PORT') ?: 5672);

            // Cek konektivitas dasar dengan socket (ringan, tanpa buka channel)
            $socket = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($socket) {
                fclose($socket);
                $checks['rabbitmq'] = [
                    'status' => 'up',
                    'host'   => $host,
                    'port'   => $port,
                ];
            } else {
                $checks['rabbitmq'] = [
                    'status' => 'down',
                    'host'   => $host,
                    'port'   => $port,
                    'error'  => "Connection refused: {$errstr} ({$errno})",
                ];
                // RabbitMQ down bukan alasan untuk 503 — data masih bisa disimpan
                // tapi flag sebagai degraded
            }
        } catch (\Throwable $e) {
            $checks['rabbitmq'] = [
                'status' => 'down',
                'error'  => $e->getMessage(),
            ];
        }

        // ─── Payload ──────────────────────────────────────────
        $data = [
            'service'   => 'fleet-service',
            'version'   => '1.0.0',
            'status'    => $healthy ? 'healthy' : 'unhealthy',
            'checks'    => $checks,
            'uptime'    => $this->getUptime(),
        ];

        $httpCode = $healthy ? 200 : 503;

        return $healthy
            ? fleet_success($data, 'Fleet Service is running.', $httpCode)
            : fleet_error('Fleet Service is unhealthy.', $httpCode, null, $data);
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Hitung uptime proses PHP dalam detik (tidak tersedia di semua env,
     * fallback ke null).
     */
    private function getUptime(): ?string
    {
        if (function_exists('getrusage')) {
            $ru = getrusage();
            $seconds = (int) ($ru['ru_utime.tv_sec'] ?? 0);
            return "{$seconds}s";
        }
        return null;
    }
}
