<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQPublisher
{
    public static function publish(string $routingKey, array $payload): void
    {
        try {
            $conn = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                (int) env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'guest'),
                env('RABBITMQ_PASS', 'guest')
            );

            $channel = $conn->channel();
            $channel->exchange_declare('city.events', 'topic', false, true, false);

            $msg = new AMQPMessage(
                json_encode($payload),
                [
                    'content_type'  => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ]
            );

            $channel->basic_publish($msg, 'city.events', $routingKey);
            $channel->close();
            $conn->close();

        } catch (\Exception $e) {
            $log = date('Y-m-d H:i:s') . " | $routingKey | " . json_encode($payload) . "\n";
            file_put_contents(WRITEPATH . 'logs/rabbitmq.log', $log, FILE_APPEND);
        }
    }
}