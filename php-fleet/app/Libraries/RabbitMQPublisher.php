<?php

namespace App\Libraries;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQPublisher
 *
 * Publish event ke exchange city.events dengan topic routing key.
 * Jika koneksi gagal, lempar exception agar controller bisa catch
 * dan tetap return response sukses dengan warning.
 */
class RabbitMQPublisher
{
    private string $host;
    private int    $port;
    private string $user;
    private string $pass;
    private string $exchange;

    // Routing keys standar
    public const ROUTING_GPS_UPDATE       = 'gps.update';
    public const ROUTING_INCIDENT_CREATED = 'fleet.incident.created';
    public const ROUTING_BUS_STATUS       = 'bus.status.updated';

    public function __construct()
    {
        $this->host     = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
        $this->port     = (int) (getenv('RABBITMQ_PORT') ?: 5672);
        $this->user     = getenv('RABBITMQ_USER') ?: 'admin';
        $this->pass     = getenv('RABBITMQ_PASS') ?: 'adminpass';
        $this->exchange = getenv('RABBITMQ_EXCHANGE') ?: 'city.events';
    }

    /**
     * Publish pesan ke exchange city.events.
     *
     * @param string               $routingKey  Contoh: 'gps.update'
     * @param array<string, mixed> $payload
     * @throws \Exception jika koneksi gagal
     */
    public function publish(string $routingKey, array $payload): void
    {
        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->pass,
            '/',
            false,      // insist
            'AMQPLAIN', // login_method
            null,       // login_response
            'en_US',    // locale
            3.0,        // connection_timeout (detik)
            3.0         // read_write_timeout
        );

        $channel = $connection->channel();

        // Deklarasi exchange topic (idempotent, aman dipanggil berkali-kali)
        $channel->exchange_declare(
            $this->exchange,
            'topic',
            false,  // passive
            true,   // durable
            false   // auto_delete
        );

        $body = json_encode(array_merge(
            ['event' => $routingKey],
            $payload
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $msg = new AMQPMessage($body, [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($msg, $this->exchange, $routingKey);

        $channel->close();
        $connection->close();
    }

    // ─── Shorthand Publishers ──────────────────────────────────

    /**
     * Publish gps.update setelah GPS log tersimpan.
     *
     * @param array<string, mixed> $gpsLog  Row dari fleet_gps_logs
     */
    public function publishGpsUpdate(array $gpsLog): void
    {
        $this->publish(self::ROUTING_GPS_UPDATE, [
            'bus_id'          => (int) $gpsLog['bus_id'],
            'route_id'        => (int) $gpsLog['route_id'],
            'lat'             => (float) $gpsLog['lat'],
            'lng'             => (float) $gpsLog['lng'],
            'speed_kmh'       => (float) $gpsLog['speed_kmh'],
            'heading'         => (float) $gpsLog['heading'],
            'passenger_count' => (int) ($gpsLog['passenger_count'] ?? 0),
            'engine_temp'     => isset($gpsLog['engine_temp']) ? (float) $gpsLog['engine_temp'] : null,
            'recorded_at'     => $gpsLog['recorded_at'],
        ]);
    }

    /**
     * Publish fleet.incident.created setelah incident dibuat.
     *
     * @param array<string, mixed> $incident  Row dari fleet_incidents
     */
    public function publishIncidentCreated(array $incident): void
    {
        $this->publish(self::ROUTING_INCIDENT_CREATED, [
            'incident_id' => (int) $incident['id'],
            'bus_id'      => (int) $incident['bus_id'],
            'type'        => $incident['type'],
            'severity'    => $incident['severity'],
            'description' => $incident['description'],
            'reported_at' => $incident['reported_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Publish bus.status.updated ketika status bus berubah.
     */
    public function publishBusStatusUpdated(int $busId, string $oldStatus, string $newStatus): void
    {
        $this->publish(self::ROUTING_BUS_STATUS, [
            'bus_id'     => $busId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
