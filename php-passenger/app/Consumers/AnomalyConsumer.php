<?php
define('FCPATH', __DIR__ . '/../public/');
chdir(FCPATH . '..');
require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$conn    = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
    $_ENV['RABBITMQ_PORT'] ?? 5672,
    $_ENV['RABBITMQ_USER'] ?? 'guest',
    $_ENV['RABBITMQ_PASS'] ?? 'guest'
);
$channel = $conn->channel();
$channel->exchange_declare('city.events', 'topic', false, true, false);
$channel->queue_declare('anomaly.alert', false, true, false, false);
$channel->queue_bind('anomaly.alert', 'city.events', 'anomaly.alert');

$pdo = new PDO(
    "mysql:host=" . ($_ENV['database.default.hostname'] ?? 'mysql') .
    ";dbname=" . ($_ENV['database.default.database'] ?? 'smartcity'),
    $_ENV['database.default.username'] ?? 'root',
    $_ENV['database.default.password'] ?? ''
);

echo "[AnomalyConsumer] Listening on anomaly.alert...\n";

$channel->basic_consume('anomaly.alert', '', false, false, false, false,
    function($msg) use ($pdo) {
        $event = json_decode($msg->body, true);

        $stmt = $pdo->prepare("
            SELECT DISTINCT passenger_id FROM passenger_tickets
            WHERE route_id = :route_id AND status = 'active'
        ");
        $stmt->execute([':route_id' => $event['route_id'] ?? 0]);
        $passengers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $insert = $pdo->prepare("
            INSERT INTO passenger_notifications 
                (passenger_id, title, body, type)
            VALUES (:pid, :title, :body, 'anomaly')
        ");

        foreach ($passengers as $p) {
            $insert->execute([
                ':pid'   => $p['passenger_id'],
                ':title' => 'Gangguan Terdeteksi',
                ':body'  => 'Anomali di rute ' . ($event['route_id'] ?? '-')
            ]);
        }

        echo "[AnomalyConsumer] Notif ke " . count($passengers) . " penumpang\n";
        $msg->ack();
    }
);

while ($channel->is_consuming()) {
    $channel->wait();
}