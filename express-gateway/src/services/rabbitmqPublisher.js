const amqp = require('amqplib');

const RABBITMQ_HOST = process.env.RABBITMQ_HOST || 'rabbitmq';
const RABBITMQ_USER = process.env.RABBITMQ_USER || 'admin';
const RABBITMQ_PASS = process.env.RABBITMQ_PASS || 'adminpass';
const EXCHANGE_NAME = 'city.commands';

async function publishBusCommand(busId, command, extra = {}) {
    const conn = await amqp.connect(`amqp://${RABBITMQ_USER}:${RABBITMQ_PASS}@${RABBITMQ_HOST}`);
    const channel = await conn.createChannel();

    await channel.assertExchange(EXCHANGE_NAME, 'topic', { durable: true });

    const payload = {
        bus_id: busId,
        command,
        ...extra,
        issued_at: new Date().toISOString()
    };

    channel.publish(
        EXCHANGE_NAME,
        'bus.command',
        Buffer.from(JSON.stringify(payload)),
        { contentType: 'application/json', persistent: true }
    );

    await channel.close();
    await conn.close();
    return payload;
}

module.exports = { publishBusCommand };