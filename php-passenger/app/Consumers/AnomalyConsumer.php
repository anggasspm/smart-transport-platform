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
$channel->queue_declare('anomaly.alert', false, true, false, false);
$channel->queue_bind('anomaly.alert', 'city.events', 'anomaly.alert');

$dbHost = $_ENV['database.default.hostname'] ?? 'mysql';
$dbName = $_ENV['database.default.database'] ?? 'smarttransport';
$dbUser = $_ENV['database.default.username'] ?? 'root';
$dbPass = $_ENV['database.default.password'] ?? '';

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass
);

echo "[AnomalyConsumer] Listening on anomaly.alert...\n";

$channel->basic_consume(
    'anomaly.alert', '', false, false, false, false,
    function($msg) use ($pdo) {
        $event = json_decode($msg->body, true);
        echo "[AnomalyConsumer] Event diterima: " . json_encode($event) . "\n";

        $stmt = $pdo->prepare("
            SELECT DISTINCT passenger_id FROM passenger_tickets
            WHERE route_id = :route_id AND status = 'active'
        ");
        $stmt->execute([':route_id' => $event['route_id'] ?? 0]);
        $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insert = $pdo->prepare("
            INSERT INTO passenger_notifications
                (passenger_id, title, body, type, is_read)
            VALUES (:pid, :title, :body, 'anomaly', 0)
        ");

        foreach ($passengers as $p) {
            $insert->execute([
                ':pid'   => $p['passenger_id'],
                ':title' => 'Gangguan Terdeteksi',
                ':body'  => 'Anomali terdeteksi di rute ' . ($event['route_id'] ?? '-')
                          . '. ' . ($event['message'] ?? '')
            ]);
        }

        echo "[AnomalyConsumer] Notif dikirim ke " . count($passengers) . " penumpang\n";
        $msg->ack();
    }
);

while ($channel->is_consuming()) {
    $channel->wait();
}