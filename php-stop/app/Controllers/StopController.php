<?php
namespace App\Controllers;
use App\Models\StopModel;
use App\Models\PassengerCountModel;
use CodeIgniter\HTTP\ResponseInterface;


class StopController extends BaseController
{
    private const CAPACITY = 50;

    private function respond(int $code, $data, string $message): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'    => $code < 400 ? 'success' : 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c'),
            'service'   => 'stop-service'
        ]);
    }

    public function index(): ResponseInterface
    {
        $model = new StopModel();
        return $this->respond(200, $model->getAllStops(), 'OK');
    }

    public function status(int $id): ResponseInterface
    {
        $stopModel  = new StopModel();
        $countModel = new PassengerCountModel();

        $stop = $stopModel->getStopById($id);
        if (!$stop) return $this->respond(404, null, 'Stop not found');

        $latest = $countModel->getLatestByStop($id);
        $load   = $latest['current_load'] ?? 0;
        $pct    = ($load / self::CAPACITY) * 100;

        if ($pct < 25)      $status = 'Sepi';
        elseif ($pct < 60)  $status = 'Normal';
        elseif ($pct < 90)  $status = 'Padat';
        else                $status = 'Penuh';

        return $this->respond(200, [
            'stop'         => $stop,
            'current_load' => $load,
            'capacity'     => self::CAPACITY,
            'percentage'   => round($pct, 2),
            'status'       => $status
        ], 'OK');
    }
    
    // GET /api/stops/route/{route_id}/except/{stop_id}
    public function getStopsByRouteExcept(int $routeId, int $exceptStopId): ResponseInterface
    {
        $db = \Config\Database::connect();
        $stops = $db->table('stop_stops')
                     ->where('route_id', $routeId)
                     ->where('id !=', $exceptStopId)
                     ->orderBy('sequence_order', 'ASC')
                     ->get()->getResultArray();

        return $this->respond(200, $stops, 'Stops retrieved');
    }

    // GET /api/stops/{stop_id}/bus-status
    public function getBusStatus(int $stopId): ResponseInterface
    {
        $db = \Config\Database::connect();
        $row = $db->table('stop_passenger_counts')
                  ->where('stop_id', $stopId)
                  ->orderBy('recorded_at', 'DESC')
                  ->limit(1)
                  ->get()->getRowArray();

        return $this->respond(200, $row ?: ['bus_id' => null, 'boarded' => 0, 'alighted' => 0], 'OK');
    }

    // PUT /api/stops/{stop_id}/bus-arrival
    public function busArrival(int $stopId): ResponseInterface
    {
        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true);

        $busId    = $body['bus_id']   ?? null;
        $boarded  = $body['boarded']  ?? 0;
        $alighted = $body['alighted'] ?? 0;

        $existing = $db->table('stop_passenger_counts')
                       ->where('stop_id', $stopId)
                       ->where('bus_id', $busId)
                       ->get()->getRowArray();

        if ($existing) {
            $db->table('stop_passenger_counts')
               ->where('stop_id', $stopId)
               ->where('bus_id', $busId)
               ->update([
                   'boarded'     => $boarded,
                   'alighted'    => $alighted,
                   'recorded_at' => date('Y-m-d H:i:s'),
               ]);
        } else {
            $last = $db->table('stop_passenger_counts')
                       ->where('stop_id', $stopId)
                       ->orderBy('recorded_at', 'DESC')
                       ->limit(1)->get()->getRowArray();

            $db->table('stop_passenger_counts')->insert([
                'stop_id'      => $stopId,
                'bus_id'       => $busId,
                'boarded'      => $boarded,
                'alighted'     => $alighted,
                'current_load' => $last['current_load'] ?? 0,
                'recorded_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respond(200, null, 'Jumlah yang naik dan turun bus di halte terperbarui');
    }

    // PUT /api/stops/{stop_id}/bus-departure
    public function busDeparture(int $stopId): ResponseInterface
    {
        $db = \Config\Database::connect();
        $db->table('stop_passenger_counts')
           ->where('stop_id', $stopId)
           ->whereNotNull('bus_id')
           ->update([
               'bus_id'      => null,
               'recorded_at' => date('Y-m-d H:i:s'),
           ]);

        return $this->respond(200, null, 'Bus berangkat updated');
    }

    // POST /api/stops/passenger-count
    public function updateCurrentLoad(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true);

        $stopId      = $body['stop_id']      ?? 0;
        $currentLoad = $body['current_load'] ?? 0;

        $last = $db->table('stop_passenger_counts')
                   ->where('stop_id', $stopId)
                   ->orderBy('recorded_at', 'DESC')
                   ->limit(1)->get()->getRowArray();

        if ($last) {
            $db->table('stop_passenger_counts')
               ->where('id', $last['id'])
               ->update(['current_load' => $currentLoad, 'recorded_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('stop_passenger_counts')->insert([
                'stop_id'      => $stopId,
                'current_load' => $currentLoad,
                'recorded_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respond(200, null, 'Current load updated');
    }
}

