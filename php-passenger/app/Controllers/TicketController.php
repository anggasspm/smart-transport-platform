<?php
namespace App\Controllers;
use App\Models\TicketModel;
use App\Services\RabbitMQPublisher;
use CodeIgniter\HTTP\ResponseInterface;

class TicketController extends BaseController
{
    private function respond(int $code, $data, string $message): ResponseInterface
    {
        return $this->response->setStatusCode($code)->setJSON([
            'status'    => $code < 400 ? 'success' : 'error',
            'code'      => $code,
            'data'      => $data,
            'message'   => $message,
            'timestamp' => date('c'),
            'service'   => 'passenger-service'
        ]);
    }

    public function store(): ResponseInterface
    {
        // Gateway inject X-User-Id setelah verifikasi JWT
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $data = $this->request->getJSON(true) ?? [];

        if (empty($data['route_id']))       return $this->respond(422, null, 'route_id is required');
        if (empty($data['origin_stop_id'])) return $this->respond(422, null, 'origin_stop_id is required');
        if (empty($data['dest_stop_id']))   return $this->respond(422, null, 'dest_stop_id is required');
        if (empty($data['price']))          return $this->respond(422, null, 'price is required');

        $model  = new TicketModel();
        $data['passenger_id'] = $passengerId;
        $data['status']       = 'active';
        $id     = $model->insert($data, true);
        $ticket = $model->find($id);

        RabbitMQPublisher::publish('ticket.purchased', [
            'ticket_id'    => $ticket['id'],
            'passenger_id' => $passengerId,
            'route_id'     => $ticket['route_id'],
            'timestamp'    => date('c')
        ]);

        return $this->respond(201, $ticket, 'Ticket purchased successfully');
    }

    public function index(): ResponseInterface
    {
        $passengerId = (int)($this->request->getHeaderLine('X-User-Id') ?: 0);
        if ($passengerId === 0) {
            return $this->respond(401, null, 'Unauthorized');
        }

        $model   = new TicketModel();
        $tickets = $model->getByPassenger($passengerId);
        return $this->respond(200, $tickets, 'OK');
    }

    // GET /api/tickets/active/{passenger_id}
    // iot halte ngecek si passenger sudah punya tiket active atau belum
    public function hasActiveTicket(int $passengerId): ResponseInterface
    {
        $model = new TicketModel();
        $ticket = $model->where('passenger_id', $passengerId)
                         ->where('status', 'active')
                         ->first();

        return $this->respond(200, ['has_active' => $ticket !== null], 'OK');
    }

    // POST /api/tickets/check-and-create
    // iot halte ngecek saldo penumpang cukup atau nggak + buat tiketnya kalau cukup saldonya
    public function checkAndCreate(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true);

        $cardNumber = $body['card_number'] ?? '';
        $originStopId = $body['origin_stop_id'] ?? 0;
        $destStopId = $body['dest_stop_id'] ?? 0;

        // 1. cari passenger lewat card number, ambil id, cek di passenger tickets
        $passenger = $db->table('passenger_passengers')
                        ->where('card_number', $cardNumber)
                        ->get()->getRowArray();

        if (!$passenger) {
            return $this->respond(404, null, 'Kartu tidak valid');
        }

        // 2. hitung harga dari origin dan dest stop
        $originStop = $db->table('stop_stops')->where('id', $originStopId)->get()->getRowArray();
        $destStop = $db->table('stop_stops')->where('id', $destStopId)->get()->getRowArray();

        if (!$originStop || !$destStop) {
            return $this->respond(400, null, 'Halte tidak ditemukan');
        }

        $seqDiff = abs($destStop['sequence_order'] - $originStop['sequence_order']);
        $price = 3000 + ($seqDiff * 500); // Base 3000 + 500 per stop
        $routeId = $originStop['route_id'];

        // 3. cek saldo
        if ((float)$passenger['balance'] < $price) {
            return $this->respond(400, [
                'required' => $price,
                'balance' => $passenger['balance']
            ], 'Saldo tidak cukup');
        }

        // 4. kurangin saldo
        $newBalance = (float)$passenger['balance'] - $price;
        $db->table('passenger_passengers')
           ->where('id', $passenger['id'])
           ->update(['balance' => $newBalance]);

        // 5. buat tiket baru
        $db->table('passenger_tickets')->insert([
            'passenger_id' => $passenger['id'],
            'route_id' => $routeId,
            'origin_stop_id' => $originStopId,
            'dest_stop_id' => $destStopId,
            'status' => 'active',
            'price' => $price,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $newTicket = $db->table('passenger_tickets')
               ->where('passenger_id', $passenger['id'])
               ->where('status', 'active')
               ->orderBy('created_at', 'DESC')
               ->limit(1)
               ->get()->getRowArray();

        RabbitMQPublisher::publish('ticket.purchased', [
            'ticket_id'    => $newTicket['id'] ?? null,
            'passenger_id' => $passenger['id'],
            'route_id'     => $routeId,
            'card_number'  => $cardNumber,
            'event_type'   => 'tap_in',
            'timestamp'    => date('c')
        ]);

        return $this->respond(201, [
            'price' => $price,
            'new_balance' => $newBalance
        ], 'Tiket berhasil dibuat');
    }

    // POST /api/tickets/checkout
    // (iot halte) buat scan keluar, update status tiket dari active jadi 'used', ngecek apakai stop_id dest beda atau sama dengan yg di tiket
    public function checkout(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $body = $this->request->getJSON(true);

        $cardNumber = $body['card_number']  ?? '';
        $exitStopId = $body['exit_stop_id'] ?? 0;

        // 1. cari passenger dari card number
        $passenger = $db->table('passenger_passengers')
                        ->where('card_number', $cardNumber)
                        ->get()->getRowArray();

        if (!$passenger) {
            return $this->respond(404, null, 'Kartu tidak ditemukan');
        }

        // 2. cari tiket dari id passenger dan yang statusnya active
        $ticket = $db->table('passenger_tickets')
                    ->where('passenger_id', $passenger['id'])
                    ->where('status', 'active')
                    ->get()->getRowArray();

        if (!$ticket) {
            return $this->respond(404, null, 'Kamu belum pernah scan masuk');
        }

        // 3. ngecek apakah dest stop di tiket sama dengan dest stop tempat passenger scan kartu keluar
        $exitStop = $db->table('stop_stops')->where('id', $exitStopId)->get()->getRowArray();
        $destStop = $db->table('stop_stops')->where('id', $ticket['dest_stop_id'])->get()->getRowArray();
        $origStop = $db->table('stop_stops')->where('id', $ticket['origin_stop_id'])->get()->getRowArray();

        $oldPrice = (float)$ticket['price'];
        $finalDestId = $ticket['dest_stop_id'];

        if ($exitStopId != $ticket['dest_stop_id']) {
            $exitSeq = $exitStop['sequence_order'] ?? 0;
            $destSeq = $destStop['sequence_order'] ?? 0;
            $origSeq = $origStop['sequence_order'] ?? 0;

            $seqDiffExit = abs($exitSeq - $origSeq);
            $newPrice = 3000 + ($seqDiffExit * 500);

            if ($exitSeq < $destSeq) {
                // keluar sebelum dest tiket, maka akan balikin selisih saldo
                $refund = $oldPrice - $newPrice;
                $db->table('passenger_passengers')
                   ->where('id', $passenger['id'])
                   ->update(['balance' => (float)$passenger['balance'] + $refund]);
            } else {
                // keluar setelah dest tiket, maka akan kurangi saldo sesuai selisih tambahan
                $extra = $newPrice - $oldPrice;
                $db->table('passenger_passengers')
                   ->where('id', $passenger['id'])
                   ->update(['balance' => (float)$passenger['balance'] - $extra]);
            }

            // perbarui dest_stop_id dan price di tiket
            $db->table('passenger_tickets')
               ->where('id', $ticket['id'])
               ->update([
                   'dest_stop_id' => $exitStopId,
                   'price' => $newPrice,
               ]);

            $finalDestId = $exitStopId;
        }

        // 4. ubah status tiket itu jadi 'used'
        $db->table('passenger_tickets')
        ->where('id', $ticket['id'])
        ->update(['status' => 'used']);

        // 5. Publish event checkout ke RabbitMQ
        // Pakai $ticket['id'] langsung, bukan query ulang
        RabbitMQPublisher::publish('ticket.checkout', [
            'ticket_id'    => $ticket['id'],
            'passenger_id' => $passenger['id'],
            'exit_stop_id' => $exitStopId,
            'card_number'  => $cardNumber,
            'timestamp'    => date('c')
        ]);

        return $this->respond(200, ['final_dest_stop_id' => $finalDestId], 'Checkout berhasil');
    }
}