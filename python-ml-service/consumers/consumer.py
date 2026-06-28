import json
import logging
import time

import numpy as np

from consumers.rabbitmq_publisher import publish_anomaly_alert
from consumers.rabbitmq_utils import connect_with_retry
from consumers.shared_state import append_speed, get_last_n_speeds
from services.model2_service import predict_model2
from services.model3_service import predict_model3

logger = logging.getLogger(__name__)

RABBITMQ_HOST = "rabbitmq"


def wait_for_models():
    from services.loader import MODELS

    timeout = 120
    waited = 0

    while waited < timeout:
        if MODELS["model2"]["loaded"] and MODELS["model3"]["loaded"]:
            return True

        logger.info(f"Waiting for models to load... ({waited}s)")
        time.sleep(2)
        waited += 2

    logger.error("Models not loaded after timeout.")
    return False


def compute_rolling_features_model3(bus_id, current_speed):
    history = get_last_n_speeds(bus_id, 120)

    if len(history) < 2:
        return None, None

    rolling_mean = float(np.mean(history))
    std = float(np.std(history))
    z = (current_speed - rolling_mean) / std if std > 0 else 0.0

    return rolling_mean, z


def on_gps_update(ch, method, properties, body):
    from services.loader import MODELS

    if not MODELS["model3"]["loaded"]:
        logger.warning("Model 3 not ready, requeuing message...")
        time.sleep(2)
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=True)
        return

    try:
        payload = json.loads(body)

        bus_id = payload.get("bus_id")
        speed = payload.get("speed_kmh")

        logger.info(
            f"[gps.update] bus_id={bus_id} speed={speed}"
        )

        rolling_mean, z_score = compute_rolling_features_model3(
            bus_id,
            speed
        )

        append_speed(bus_id, speed)

        if rolling_mean is None:
            logger.info(
                f"[gps.update] bus_id={bus_id} - not enough history"
            )
            ch.basic_ack(delivery_tag=method.delivery_tag)
            return

        anomaly_input = {
            "speed_kmh": speed,
            "passenger_count": payload.get("passenger_count"),
            "engine_temp_c": payload.get("engine_temp"),
            "rolling_mean_1h": rolling_mean,
            "z_score": z_score,
        }

        result = predict_model3(anomaly_input)

        data = result.get("data", {})

        logger.info(data)

        if data.get("severity") in ("Medium", "High"):
            publish_anomaly_alert(
                bus_id=bus_id,
                route_id=payload.get("route_id"),
                detail=data,
            )

            logger.warning(
                f"[anomaly.alert] published for bus_id={bus_id}"
            )

        ch.basic_ack(delivery_tag=method.delivery_tag)

    except Exception:
        logger.exception("Failed processing gps.update")
        ch.basic_nack(
            delivery_tag=method.delivery_tag,
            requeue=False,
        )


def on_passenger_boarded(ch, method, properties, body):
    from services.loader import MODELS

    logger.warning("========== passenger.boarded CALLBACK ==========")

    if not MODELS["model2"]["loaded"]:
        logger.warning("Model 2 not ready")
        time.sleep(2)
        ch.basic_nack(delivery_tag=method.delivery_tag, requeue=True)
        return

    try:
        logger.warning(f"Raw body: {body.decode()}")

        payload = json.loads(body)

        logger.warning(f"Parsed payload: {payload}")

        input_data = {
            "stop_id": payload["stop_id"],
            "hour": payload["hour"],
            "day_of_week": payload["day_of_week"],
            "weather": payload["weather"],
            "prev_count": payload["prev_count"],
            "is_holiday": payload["is_holiday"],
        }

        logger.warning(f"Input model2: {input_data}")

        result = predict_model2(input_data)

        logger.warning(f"Prediction result: {result}")

        ch.basic_ack(delivery_tag=method.delivery_tag)

        logger.warning(
            f"[passenger.boarded] SUCCESS stop_id={payload['stop_id']}"
        )

    except KeyError:
        logger.exception("Missing field")
        ch.basic_nack(
            delivery_tag=method.delivery_tag,
            requeue=False,
        )

    except Exception:
        logger.exception("Failed processing passenger.boarded")
        ch.basic_nack(
            delivery_tag=method.delivery_tag,
            requeue=False,
        )


def start_consumer():
    if not wait_for_models():
        logger.error("Models failed to load.")
        return

    connection = connect_with_retry(RABBITMQ_HOST)
    channel = connection.channel()

    channel.exchange_declare(
        exchange="city.events",
        exchange_type="topic",
        durable=True,
    )

    channel.queue_declare(
        queue="gps.update",
        durable=True,
    )

    channel.queue_bind(
        exchange="city.events",
        queue="gps.update",
        routing_key="gps.update",
    )

    channel.queue_declare(
        queue="passenger.boarded",
        durable=True,
    )

    channel.queue_bind(
        exchange="city.events",
        queue="passenger.boarded",
        routing_key="passenger.boarded",
    )

    channel.basic_qos(prefetch_count=1)

    channel.basic_consume(
        queue="gps.update",
        on_message_callback=on_gps_update,
        auto_ack=False,
    )

    channel.basic_consume(
        queue="passenger.boarded",
        on_message_callback=on_passenger_boarded,
        auto_ack=False,
    )

    logger.info(
        "Consumer started, waiting for gps.update & passenger.boarded..."
    )

    channel.start_consuming()


if __name__ == "__main__":
    logging.basicConfig(level=logging.INFO)
    start_consumer()