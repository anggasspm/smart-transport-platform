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

        $busId = $body['bus_id'] ?? null;
        $boarded = (int) ($body['boarded']  ?? 0);
        $alighted = (int) ($body['alighted'] ?? 0);

        $db->transStart();

        $last = $db->query(
            'SELECT * FROM stop_passenger_counts
            WHERE stop_id = ?
            ORDER BY recorded_at DESC
            LIMIT 1
            FOR UPDATE',
            [$stopId]
        )->getRowArray();

        $lastLoad = (int) ($last['current_load'] ?? 0);

        if ($boarded === 0 && $alighted === 0) {
            $newLoad = $lastLoad;
        } else {
            $newLoad = max(0, $lastLoad + $alighted - $boarded);
        }

        if ($last) {
            $db->table('stop_passenger_counts')
            ->where('id', $last['id'])
            ->update([
                'bus_id' => $busId ?? $last['bus_id'],
                'boarded' => $boarded,
                'alighted' => $alighted,
                'current_load' => $newLoad,
                'recorded_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            $db->table('stop_passenger_counts')->insert([
                'stop_id' => $stopId,
                'bus_id' => $busId,
                'boarded' => $boarded,
                'alighted' => $alighted,
                'current_load' => $newLoad,
                'recorded_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $db->transComplete();

        return $this->respond(200, null, 'Jumlah yang naik dan turun bus di halte terperbarui');
    }

    // PUT /api/stops/{stop_id}/bus-departure
    public function busDeparture(int $stopId): ResponseInterface
    {
        $db = \Config\Database::connect();

        $last = $db->table('stop_passenger_counts')
                ->where('stop_id', $stopId)
                ->orderBy('recorded_at', 'DESC')
                ->limit(1)->get()->getRowArray();

        if ($last && $last['bus_id'] !== null) {
            $db->table('stop_passenger_counts')
            ->where('id', $last['id'])
            ->update([
                'bus_id' => null,
                'recorded_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->respond(200, null, 'Bus berangkat updated');
    }

    // POST /api/stops/passenger-count
    public function updateCurrentLoad(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true);

	 log_message('debug', json_encode($body));

        $stopId = $body['stop_id'] ?? 0;
        $newLoad = (int) ($body['prev_count'] ?? 0);
        $busId = $body['bus_id'] ?? null;

        $db->transStart();

        $last = $db->query(
            'SELECT * FROM stop_passenger_counts
            WHERE stop_id = ?
            ORDER BY recorded_at DESC
            LIMIT 1
            FOR UPDATE',
            [$stopId]
        )->getRowArray();

        $db->table('stop_passenger_counts')->insert([
            'stop_id' => $stopId,
            'bus_id' => $busId,
            'boarded' => $last['boarded']  ?? 0,
            'alighted' => $last['alighted'] ?? 0,
            'current_load' => max(0, $newLoad),
            'recorded_at'  => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->respond(500, null, 'Failed to update current load');
        }

        try {

            \App\Services\RabbitMQPublisher::publish('passenger.boarded', [
                'stop_id' => $stopId,
                'hour' => (int) ($body['hour'] ?? date('G')),
                'day_of_week' => (int) ($body['day_of_week'] ?? date('N') - 1),
                'weather' => (int) ($body['weather'] ?? 0),
                'prev_count' => (int) ($last['current_load'] ?? 0),
                'is_holiday' => (int) ($body['is_holiday'] ?? 0),
            ]);

        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());
        }

        return $this->respond(200, null, 'Current load updated');
    }
}

