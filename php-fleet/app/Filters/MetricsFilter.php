<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;

class MetricsFilter implements FilterInterface
{
    private static float $startTime = 0.0;

    public function before(RequestInterface $request, $arguments = null)
    {
        self::$startTime = microtime(true);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $registry = new CollectorRegistry(new APC());
        
        $SERVICE = 'php_fleet';
        $JOB = 'php-fleet';

        $counter = $registry->getOrRegisterCounter(
            $SERVICE,
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'path', 'status', 'job']
        );
        $counter->inc([
            $request->getMethod(),
            '/' . $request->getUri()->getPath(),
            (string)$response->getStatusCode(),
            $JOB
        ]);

        $histogram = $registry->getOrRegisterHistogram(
            $SERVICE,
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'path', 'job'],
            [0.05, 0.1, 0.3, 0.5, 1, 2, 5]
        );
        $duration = self::$startTime > 0 
            ? microtime(true) - self::$startTime 
            : 0.0;
        $histogram->observe($duration, [
            $request->getMethod(),
            '/' . $request->getUri()->getPath(),
            $JOB
        ]);
    }
}