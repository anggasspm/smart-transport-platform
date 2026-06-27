import logging
import numpy as np
from .loader import MODELS
from utils.response import error_response, success_response
from consumers.shared_state import get_last_n_speeds

logger = logging.getLogger(__name__)


def _resolve_speed_features(bus_id, hour, day_of_week, corridor_encoded, config):
    """
    Bangun speed_lag_1, speed_lag_2, speed_rolling_mean_3.
    Kalau histori kurang dari 3 entri, SEMUA tiga fitur fallback ke expected_speed
    (median historis untuk kombinasi hour/day_of_week/corridor saat training) —
    sesuai keputusan yang sudah dikonfirmasi.
    """
    last_speeds = get_last_n_speeds(bus_id, 3)  # urutan lama -> baru, len 0-3

    expected_speed_table = config.get("expected_speed_lookup", {})
    lookup_key = f"{hour}_{day_of_week}_{corridor_encoded}"
    fallback_speed = expected_speed_table.get(
        lookup_key, config.get("global_median_speed", 20.0)
    )

    if len(last_speeds) < 3:
        logger.info(
            f"bus_id={bus_id} histori speed kurang ({len(last_speeds)}/3) — fallback ke expected_speed"
        )
        return fallback_speed, fallback_speed, fallback_speed, True

    speed_lag_1 = last_speeds[-1]
    speed_lag_2 = last_speeds[-2]
    speed_rolling_mean_3 = float(np.mean(last_speeds))
    return speed_lag_1, speed_lag_2, speed_rolling_mean_3, False


def predict_model1_v2(input_data: dict):
    if not MODELS["model1"]["loaded"]:
        logger.error("Prediction failed: Model 1 is not loaded.")
        return error_response(
            status_code=503,
            message="Model 1 is currently unavailable due to initialization failure."
        )

    regressor = MODELS["model1"]["regressor"]
    encoder = MODELS["model1"]["encoder"]
    config = MODELS["model1"]["config"]

    try:
        bus_id = input_data.get("bus_id")
        corridor_raw = input_data.get("route_id")
        hour = input_data.get("hour")
        day_of_week = input_data.get("day_of_week")
        
        try:
            corridor_encoded = int(encoder.transform([corridor_raw])[0])
        except Exception as e:
            logger.warning(f"Unknown corridor encountered '{corridor_raw}': {e}")
            return error_response(
                status_code=400,
                message=f"Validation Error: Unknown route_id/corridor '{corridor_raw}'."
            )

        speed_lag_1, speed_lag_2, speed_rolling_mean_3, used_fallback = _resolve_speed_features(
            bus_id, hour, day_of_week, corridor_encoded, config
        )

        internal_features = {
            "hour": hour,
            "corridor_encoded": corridor_encoded,
            "speed_lag_1": speed_lag_1,
            "speed_lag_2": speed_lag_2,
            "speed_rolling_mean_3": speed_rolling_mean_3,
        }

        feature_order = config["features_regressor"]
        try:
            features_vector = [internal_features[feature] for feature in feature_order]
        except KeyError as e:
            logger.error(f"Missing required feature in input mapping: {e}")
            return error_response(
                status_code=400,
                message=f"Validation Error: Missing required feature {str(e)} for model inference."
            )

        delay_minutes = float(regressor.predict([features_vector])[0])

        late_threshold = config["delay_thresholds"]["Late"]
        severe_threshold = config["delay_thresholds"]["Severe"]

        if delay_minutes > severe_threshold:
            status = "Severe"
        elif delay_minutes > late_threshold:
            status = "Late"
        else:
            status = "OnTime"

        return success_response(
            data={
                "predicted_delay_minutes": delay_minutes,
                "status": status,
                "used_speed_history_fallback": used_fallback,
            },
            message="Model 1 prediction successful.",
            status_code=200
        )

    except Exception as e:
        logger.error(f"Unexpected error during Model 1 prediction: {e}")
        return error_response(
            status_code=500,
            message="An unexpected error occurred during prediction handling."
        )