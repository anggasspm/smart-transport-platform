import json
import logging
import pika
import numpy as np

from shared_state import append_speed, get_last_n_speeds
from services.model2_service import predict_model2
from services.model3_service import predict_model3
from services.rabbitmq_publisher import publish_anomaly_alert

logger = logging.getLogger(__name__)

RABBITMQ_HOST = "rabbitmq"


def compute_rolling_features_model3(bus_id, current_speed):
    """
    Model 3 pakai histori SEBELUM speed baru ini di-append — supaya rolling_mean_1h
    konsisten secara semantik dengan "rata-rata 1 jam terakhir s.d. sebelum observasi ini".
    """
    history = get_last_n_speeds(bus_id, 120)
    if len(history) < 2:
        return None, None
    rolling_mean = float(np.mean(history))
    std = float(np.std(history))
    z = (current_speed - rolling_mean) / std if std > 0 else 0.0
    return rolling_mean, z


def on_gps_update(ch, method, properties, body):
    try:
        payload = json.loads(body)
        bus_id = payload.get("bus_id")
        speed = payload.get("speed_kmh")

        logger.info(f"[gps.update] bus_id={bus_id} speed_kmh={speed} recorded_at={payload.get('recorded_at')}")

        # 1) Hitung dulu rolling features Model 3 pakai histori SEBELUM speed baru di-append.
        rolling_mean, z_score = compute_rolling_features_model3(bus_id, speed)

        # 2) BARU append speed baru ke shared state. Setelah titik ini, speed baru ini
        #    akan terbaca sebagai observasi paling akhir oleh model1_service.py
        #    (dipakai sebagai speed_lag_1 saat /predict/delay dipanggil belakangan).
        append_speed(bus_id, speed)

        if rolling_mean is None:
            logger.info(f"[gps.update] bus_id={bus_id} - belum cukup histori, skip anomaly check")
            ch.basic_ack(delivery_tag=method.delivery_tag)
            return

        anomaly_input = {
            "speed": speed,
            "passenger_count": payload.get("passenger_count"),
            "engine_temp": payload.get("engine_temp"),
            "rolling_mean_1h": rolling_mean,
            "z_score": z_score,
        }

        result = predict_model3(anomaly_input)
        data = result.get("data", {})
        logger.info(f"[gps.update] bus_id={bus_id} anomaly_check -> {data}")

        if data.get("severity") in ("Medium", "High"):
            publish_anomaly_alert(bus_id=bus_id, route_id=payload.get("route_id"), detail=data)
            logger.warning(f"[anomaly.alert] published for bus_id={bus_id} severity={data.get('severity')}")

        ch.basic_ack(delivery_tag=method.delivery_tag)

    except Exception as e:
        logger.error(f"Failed processing gps.update: {e}")
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)


def on_passenger_boarded(ch, method, properties, body):
    try:
        payload = json.loads(body)
        input_data = {
            "stop_id": payload["stop_id"],
            "hour": payload["hour"],
            "day_of_week": payload["day_of_week"],
            "weather": payload["weather"],
            "prev_count": payload["prev_count"],
            "is_holiday": payload["is_holiday"],
        }
        result = predict_model2(input_data)
        logger.info(f"[passenger.boarded] stop_id={payload['stop_id']} -> {result.get('data')}")
        ch.basic_ack(delivery_tag=method.delivery_tag)
    except KeyError as e:
        logger.error(f"Missing field in passenger.boarded payload: {e}")
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)
    except Exception as e:
        logger.error(f"Failed processing passenger.boarded: {e}")
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=False)


import time

def connect_with_retry(host, max_retries=10, delay_seconds=5):
    for attempt in range(1, max_retries + 1):
        try:
            connection = pika.BlockingConnection(pika.ConnectionParameters(host=host))
            logger.info(f"Connected to RabbitMQ on attempt {attempt}")
            return connection
        except pika.exceptions.AMQPConnectionError as e:
            logger.warning(f"RabbitMQ not ready (attempt {attempt}/{max_retries}): {e}")
            if attempt == max_retries:
                logger.error("Max retries reached. Could not connect to RabbitMQ.")
                raise
            time.sleep(delay_seconds)

def start_consumer():
    connection = connect_with_retry(RABBITMQ_HOST)
    channel = connection.channel()

    channel.exchange_declare(exchange="city.events", exchange_type="topic", durable=True)

    channel.queue_declare(queue="gps.update", durable=True)
    channel.queue_bind(exchange="city.events", queue="gps.update", routing_key="gps.update")

    channel.queue_declare(queue="passenger.boarded", durable=True)
    channel.queue_bind(exchange="city.events", queue="passenger.boarded", routing_key="passenger.boarded")

    channel.basic_consume(queue="gps.update", on_message_callback=on_gps_update)
    channel.basic_consume(queue="passenger.boarded", on_message_callback=on_passenger_boarded)

    logger.info("Consumer started, waiting for gps.update & passenger.boarded events...")
    channel.start_consuming()


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    start_consumer()