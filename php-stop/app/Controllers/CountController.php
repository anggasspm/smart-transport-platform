<?php
namespace App\Controllers;
use App\Models\PassengerCountModel;
use App\Services\RabbitMQPublisher;
use CodeIgniter\HTTP\ResponseInterface;

class CountController extends BaseController
{
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

    public function store(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        // STEP 1 — VALIDASI FIELD 
        if (empty($data['stop_id'])) 
            return $this->respond(422, null, 'stop_id is required');
        if (empty($data['bus_id']))  
            return $this->respond(422, null, 'bus_id is required');

        // STEP 2 — SIMPAN KE DATABASE
        $payload = [
            'stop_id'      => $data['stop_id'],
            'bus_id'       => $data['bus_id'],
            'boarded'      => $data['boarded']      ?? 0,
            'alighted'     => $data['alighted']     ?? 0,
            'current_load' => $data['current_load'] ?? 0,
            'recorded_at'  => date('Y-m-d H:i:s'),
        ];

        $model = new PassengerCountModel();
        $count = $model->createCount($payload);

        // STEP 3 — AMBIL PREV COUNT DARI DB
        $prevCount = $model->getPrevCount(
            $data['stop_id'], 
            $count['id']
        );

        // STEP 4 — HITUNG FITUR ML DARI TIMESTAMP
        $now       = new \DateTime();
        $hour      = (int)$now->format('H');      
        // jam sekarang: 0-23

        $dayOfWeek = (int)$now->format('N');      
        // 1=Senin, 2=Selasa, ..., 7=Minggu

        $isHoliday = ($dayOfWeek >= 6) ? 1 : 0;  
        // 1 kalau Sabtu/Minggu, 0 kalau weekday

        $weather   = isset($data['weather']) 
                     ? (int)$data['weather'] 
                     : 0;                         
        // dari IoT kalau ada, default 0
        // 0=cerah, 1=mendung, 2=hujan, 3=badai

        // STEP 5 — PUBLISH KE RABBITMQ PAYLOAD 
        RabbitMQPublisher::publish('passenger.boarded', [
            // raw data
            'stop_id'      => $count['stop_id'],
            'bus_id'       => $count['bus_id'],
            'boarded'      => $count['boarded'],
            'alighted'     => $count['alighted'],
            'current_load' => $count['current_load'],

            // fitur untuk ML
            'hour'         => $hour,
            'day_of_week'  => $dayOfWeek,
            'is_holiday'   => $isHoliday,
            'weather'      => $weather,
            'prev_count'   => $prevCount,

            'timestamp'    => $now->format('c')
        ]);

        // STEP 6 — RETURN RESPONSE
        return $this->respond(201, $count, 'Passenger count recorded');
    }
}