import json
import logging
import pika
from consumers.rabbitmq_utils import connect_with_retry

logger = logging.getLogger(__name__)

RABBITMQ_HOST = "rabbitmq"  # samakan dengan consumer.py — nama service di docker-compose
EXCHANGE_NAME = "city.events"


def _publish(routing_key: str, payload: dict):
    try:
        connection = connect_with_retry(RABBITMQ_HOST)
        channel = connection.channel()
        channel.exchange_declare(exchange=EXCHANGE_NAME, exchange_type="topic", durable=True)

        channel.basic_publish(
            exchange=EXCHANGE_NAME,
            routing_key=routing_key,
            body=json.dumps(payload),
            properties=pika.BasicProperties(content_type="application/json", delivery_mode=2),
        )
        connection.close()
        return True
    except Exception as e:
        logger.error(f"Failed to publish to {routing_key}: {e}")
        return False


def publish_anomaly_alert(bus_id, route_id, detail: dict):
    payload = {
        "event": "anomaly.alert",
        "bus_id": bus_id,
        "route_id": route_id,
        "is_anomaly": detail.get("is_anomaly"),
        "anomaly_score": detail.get("anomaly_score"),
        "severity": detail.get("severity"),
        "timestamp": detail.get("timestamp"),  
    }
    success = _publish("anomaly.alert", payload)
    if not success:
        logger.warning(f"anomaly.alert NOT published for bus_id={bus_id} — check RabbitMQ connection")
    return success