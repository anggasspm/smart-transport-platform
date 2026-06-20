<?php
define('FCPATH', __DIR__ . '/../../public/');
chdir(__DIR__ . '/../..');
require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$env = parse_ini_file(__DIR__ . '/../../.env');
foreach ($env as $key => $val) {
    $_ENV[$key] = $val;
}

$conn    = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASS'] ?? 'guest'
);
$channel = $conn->channel();
$channel->exchange_declare('city.events', 'topic', false, true, false);
$channel->queue_declare('ticket.purchased', false, true, false, false);
$channel->queue_bind('ticket.purchased', 'city.events', 'ticket.purchased');

$dbHost = $_ENV['database.default.hostname'] ?? 'mysql';
$dbName = $_ENV['database.default.database'] ?? 'smarttransport';
$dbUser = $_ENV['database.default.username'] ?? 'root';
$dbPass = $_ENV['database.default.password'] ?? '';

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass
);

echo "[TicketConsumer] Listening on ticket.purchased...\n";

$channel->basic_consume(
    'ticket.purchased', '', false, false, false, false,
    function($msg) use ($pdo) {
        $event = json_decode($msg->body, true);
        echo "[TicketConsumer] Event diterima: " . json_encode($event) . "\n";

        $passengerId = $event['passenger_id'] ?? null;
        $routeId     = $event['route_id'] ?? null;
        $ticketId    = $event['ticket_id'] ?? null;

        if (!$passengerId) {
            echo "[TicketConsumer] passenger_id tidak ada, skip.\n";
            $msg->ack();
            return;
        }

        $insert = $pdo->prepare("
            INSERT INTO passenger_notifications
                (passenger_id, title, body, type, is_read)
            VALUES (:pid, :title, :body, 'ticket', 0)
        ");

        $insert->execute([
            ':pid'   => $passengerId,
            ':title' => 'Tiket Berhasil Dibeli',
            ':body'  => 'Tiket #' . $ticketId . ' untuk rute ' . $routeId . ' berhasil dibeli. Selamat bepergian!'
        ]);

        echo "[TicketConsumer] Notif tiket dikirim ke passenger_id: {$passengerId}\n";
        $msg->ack();
    }
);

while ($channel->is_consuming()) {
    $channel->wait();
}