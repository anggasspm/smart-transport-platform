<?php
namespace App\Controllers\Api;

use CodeIgniter\Controller;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    public function index()
    {
        $registry = new CollectorRegistry(new APC());
        $db = \Config\Database::connect();

        $totalBuses = $db->table('fleet_buses')->countAll();
        $activeBuses = $db->table('fleet_buses')->where('status', 'active')->countAll();
        $totalRoutes = $db->table('fleet_routes')->countAll();
        $totalGpsLogs = $db->table('fleet_gps_logs')->countAll();

        $g1 = $registry->getOrRegisterGauge('php_fleet', 'total_buses', 'Total buses');
        $g1->set($totalBuses);

        $g2 = $registry->getOrRegisterGauge('php_fleet', 'active_buses', 'Active buses');
        $g2->set($activeBuses);

        $g3 = $registry->getOrRegisterGauge('php_fleet', 'total_routes', 'Total routes');
        $g3->set($totalRoutes);

        $g4 = $registry->getOrRegisterGauge('php_fleet', 'total_gps_logs', 'Total GPS logs');
        $g4->set($totalGpsLogs);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return $this->response
            ->setHeader('Content-Type', RenderTextFormat::MIME_TYPE)
            ->setBody($result);
    }
}