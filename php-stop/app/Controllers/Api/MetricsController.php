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

        $totalStops = $db->table('stop_stops')->countAll();
        $stopsWithBus = $db->table('stop_passenger_counts')->where('bus_id IS NOT NULL', null, false)->countAll();
        $totalPassengerCount = $db->table('stop_passenger_counts')->selectSum('current_load')->get()->getRow()->current_load ?? 0;

        $g1 = $registry->getOrRegisterGauge('php_stop', 'total_stops', 'Total Halte');
        $g1->set($totalStops);

        $g2 = $registry->getOrRegisterGauge('php_stop', 'total_current_load', 'Total Orang di Semua Halte');
        $g2->set($totalPassengerCount);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return $this->response
            ->setHeader('Content-Type', RenderTextFormat::MIME_TYPE)
            ->setBody($result);
    }
}