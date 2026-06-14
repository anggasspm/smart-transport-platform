<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\RabbitMQPublisher;
use App\Traits\ApiResponseTrait;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Throwable;

/**
 * HealthController
 *
 * GET /health
 *
 * Checks:
 *  1. Service is up (always true if this code runs).
 *  2. Database connectivity — returns 503 if down.
 *  3. RabbitMQ connectivity — best-effort, degraded 200 if down.
 */
class HealthController extends BaseController
{
    use ApiResponseTrait;

    public function index(): \CodeIgniter\HTTP\Response
    {
        $dbStatus  = $this->checkDatabase();
        $mqStatus  = $this->checkRabbitMQ();
        $timestamp = date('c'); // ISO 8601

        // DB down → 503
        if (! $dbStatus['connected']) {
            return $this->serviceUnavailable('Database unavailable', [
                'data' => [
                    'service'   => 'fleet-service',
                    'status'    => 'degraded',
                    'timestamp' => $timestamp,
                    'checks'    => [
                        'database' => $dbStatus,
                        'rabbitmq' => $mqStatus,
                    ],
                ],
            ]);
        }

        // DB up — return 200 regardless of MQ
        $overallStatus = $mqStatus['connected'] ? 'healthy' : 'degraded';

        $warnings = [];
        if (! $mqStatus['connected']) {
            $warnings[] = 'RabbitMQ is unavailable; event publishing is disabled.';
        }

        $body = [
            'service'   => 'fleet-service',
            'status'    => $overallStatus,
            'timestamp' => $timestamp,
            'checks'    => [
                'database' => $dbStatus,
                'rabbitmq' => $mqStatus,
            ],
        ];

        if ($warnings) {
            $body['warnings'] = $warnings;
        }

        return $this->success($body, 'Fleet Service is running');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Attempt a lightweight DB query to verify connectivity.
     *
     * @return array{connected: bool, latency_ms: float|null, error: string|null}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');

            return [
                'connected'  => true,
                'latency_ms' => round((microtime(true) - $start) * 1000, 2),
                'driver'     => $db->DBDriver,
                'error'      => null,
            ];
        } catch (DatabaseException $e) {
            log_message('error', '[HealthController] DB check failed: ' . $e->getMessage());
            return [
                'connected'  => false,
                'latency_ms' => null,
                'driver'     => null,
                'error'      => 'Database connection failed',
            ];
        } catch (Throwable $e) {
            log_message('error', '[HealthController] DB check unexpected error: ' . $e->getMessage());
            return [
                'connected'  => false,
                'latency_ms' => null,
                'driver'     => null,
                'error'      => 'Unexpected database error',
            ];
        }
    }

    /**
     * Best-effort RabbitMQ connectivity check.
     * Never throws — always returns a status array.
     *
     * @return array{connected: bool, host: string, port: int, error: string|null}
     */
    private function checkRabbitMQ(): array
    {
        $host = (string) env('RABBITMQ_HOST', 'rabbitmq');
        $port = (int)    env('RABBITMQ_PORT', 5672);

        try {
            $publisher = new RabbitMQPublisher();
            $connected = $publisher->isConnected();
            $publisher->close();

            return [
                'connected' => $connected,
                'host'      => $host,
                'port'      => $port,
                'error'     => $connected ? null : 'Could not connect to RabbitMQ',
            ];
        } catch (Throwable $e) {
            log_message('notice', '[HealthController] RabbitMQ check failed: ' . $e->getMessage());
            return [
                'connected' => false,
                'host'      => $host,
                'port'      => $port,
                'error'     => 'RabbitMQ check failed',
            ];
        }
    }
}
