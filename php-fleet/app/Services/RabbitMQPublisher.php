<?php

declare(strict_types=1);

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * RabbitMQPublisher
 *
 * Publishes events to the city.events topic exchange.
 * All failures are caught and returned as false / error strings —
 * no fatal exceptions bubble up to controllers.
 */
class RabbitMQPublisher
{
    private ?AMQPStreamConnection $connection = null;
    private ?\PhpAmqpLib\Channel\AMQPChannel $channel = null;

    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $exchange;

    /** Seconds to wait for socket connection before giving up. */
    private int $connectTimeout = 5;

    public function __construct()
    {
        $this->host     = (string) env('RABBITMQ_HOST',     'rabbitmq');
        $this->port     = (int)    env('RABBITMQ_PORT',     5672);
        $this->user     = (string) env('RABBITMQ_USER',     'guest');
        $this->pass     = (string) env('RABBITMQ_PASS',     'guest');
        $this->exchange = (string) env('RABBITMQ_EXCHANGE', 'city.events');
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Publish a message to the topic exchange.
     *
     * @param string $routingKey e.g. "fleet.gps", "fleet.incident"
     * @param array  $payload    Will be JSON-encoded automatically.
     *
     * @return true|string  Returns true on success, error message string on failure.
     */
    public function publish(string $routingKey, array $payload): true|string
    {
        try {
            $this->connect();

            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $msg = new AMQPMessage($body, [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);

            $this->channel->basic_publish($msg, $this->exchange, $routingKey);

            return true;
        } catch (Throwable $e) {
            log_message('error', '[RabbitMQPublisher] publish failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /**
     * Check whether a connection to RabbitMQ can be established.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        try {
            $this->connect();
            return $this->connection !== null && $this->connection->isConnected();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Close the channel and connection gracefully.
     */
    public function close(): void
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (Throwable) {
            // best effort
        } finally {
            $this->channel    = null;
            $this->connection = null;
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Lazily open a connection and declare the topic exchange.
     * Throws on failure — callers must wrap in try/catch.
     */
    private function connect(): void
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return;
        }

        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->pass,
            '/',                          // vhost
            false,                        // insist
            'AMQPLAIN',                   // login_method
            null,                         // login_response
            'en_US',                      // locale
            $this->connectTimeout,        // connection_timeout
            $this->connectTimeout,        // read_write_timeout
        );

        $this->channel = $this->connection->channel();

        // Declare the exchange (idempotent — safe to call on every connect).
        $this->channel->exchange_declare(
            $this->exchange,
            'topic',
            false,   // passive
            true,    // durable
            false,   // auto_delete
        );
    }
}
