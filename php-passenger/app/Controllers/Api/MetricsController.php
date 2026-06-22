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

        $totalPassengers = $db->table('passenger_passengers')->countAll();
        $totalTickets = $db->table('passenger_tickets')->countAll();
        $activeTickets = $db->table('passenger_tickets')->where('status', 'active')->countAll();
        $usedTickets = $db->table('passenger_tickets')->where('status', 'used')->countAll();

        // menggunakan gauge karena data bisa naik turun
        $g1 = $registry->getOrRegisterGauge('php_passenger', 'total_passengers', 'Total Penumpang Terdaftar');
        $g1->set($totalPassengers);

        $g2 = $registry->getOrRegisterGauge('php_passenger', 'total_tickets', 'Total tickets');
        $g2->set($totalTickets);

        $g3 = $registry->getOrRegisterGauge('php_passenger', 'active_tickets', 'Active tickets');
        $g3->set($activeTickets);

        $g4 = $registry->getOrRegisterGauge('php_passenger', 'used_tickets', 'Used tickets');
        $g4->set($usedTickets);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return $this->response
            ->setHeader('Content-Type', RenderTextFormat::MIME_TYPE)
            ->setBody($result);
    }
}