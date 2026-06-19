<?php

namespace App\Controllers\Api;

use CodeIgniter\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function index()
    {
        $registry = new CollectorRegistry(new InMemory());

        $requestCounter = $registry->getOrRegisterCounter(
            'php_fleet',
            'http_requests_total',
            'Total HTTP requests',
            ['method', 'path', 'status', 'job']
        );
        $requestCounter->inc(['GET', '/api/buses', '200', 'php-fleet']);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return $this->response
            ->setHeader('Content-Type', RenderTextFormat::MIME_TYPE)
            ->setBody($result);
    }
}