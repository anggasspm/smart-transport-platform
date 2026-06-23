import json
import logging
import pika

logger = logging.getLogger(__name__)

RABBITMQ_HOST = "localhost"  # sesuaikan dengan env/config
EXCHANGE_NAME = "city.events"
QUEUE_NAME = "gps.update"

def on_gps_update(ch, method, properties, body):
    try:
        payload = json.loads(body)
        logger.info(
            f"[gps.update] bus_id={payload.get('bus_id')} "
            f"route_id={payload.get('route_id')} "
            f"speed_kmh={payload.get('speed_kmh')} "
            f"recorded_at={payload.get('recorded_at')}"
        )
        # Ga trigger predict_model1() soalnya distance_to_stop tidak tersedia di payload
        ch.basic_ack(delivery_tag=method.delivery_tag)
    except Exception as e:
        logger.error(f"Failed processing gps.update: {e}")
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)

def start_consumer():
    connection = pika.BlockingConnection(pika.ConnectionParameters(host=RABBITMQ_HOST))
    channel = connection.channel()
    channel.queue_declare(queue=QUEUE_NAME, durable=True)
    channel.basic_consume(queue=QUEUE_NAME, on_message_callback=on_gps_update)
    logger.info("Consumer started, waiting for gps.update events...")
    channel.start_consuming()

if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    start_consumer()