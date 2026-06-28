<?php
define('FCPATH', __DIR__ . '/../../public/');
chdir(__DIR__ . '/../..');
require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

// Parse .env manual karena parse_ini_file tidak support karakter '!'
$envFile = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($envFile as $line) {
    if (strpos(trim($line), '#') === 0) continue; // skip komentar
    if (strpos($line, '=') === false) continue;
    [$key, $val] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($val);
}

$conn    = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    (int)($_ENV['RABBITMQ_PORT'] ?? 5672),
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASS'] ?? 'guest'
);
$channel = $conn->channel();
$channel->exchange_declare('city.events', 'topic', false, true, false);
$channel->queue_declare('ticket.checkout', false, true, false, false);
$channel->queue_bind('ticket.checkout', 'city.events', 'ticket.checkout');

$dbHost = $_ENV['database.default.hostname'] ?? 'mysql';
$dbName = $_ENV['database.default.database'] ?? 'smarttransport';
$dbUser = $_ENV['database.default.username'] ?? 'root';
$dbPass = $_ENV['database.default.password'] ?? '';

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass
);

echo "[CheckoutConsumer] Listening on ticket.checkout...\n";

$channel->basic_consume(
    'ticket.checkout', '', false, false, false, false,
    function($msg) use ($pdo) {
        $event = json_decode($msg->body, true);
        echo "[CheckoutConsumer] Event diterima: " . json_encode($event) . "\n";

        $passengerId = $event['passenger_id'] ?? null;
        $ticketId    = $event['ticket_id']    ?? null;
        $exitStopId  = $event['exit_stop_id'] ?? null;
        $cardNumber  = $event['card_number']  ?? '';

        if (!$passengerId) {
            echo "[CheckoutConsumer] passenger_id tidak ada, skip.\n";
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
            ':title' => 'Perjalanan Selesai',
            ':body'  => 'Kamu telah keluar di halte #' . $exitStopId . '. Terima kasih telah menggunakan layanan kami!'
        ]);

        echo "[CheckoutConsumer] Notif dikirim ke passenger_id: {$passengerId}\n";
        $msg->ack();
    }
);

while ($channel->is_consuming()) {
    $channel->wait();
}