import logging
from .loader import MODELS
from utils.response import error_response, success_response 

logger = logging.getLogger(__name__)

def predict_model1(input_data: dict):
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
        corridor_raw = input_data.get("route_id")
        dtd = input_data.get("distance_to_stop")
        
        try:
            corridor_encoded = encoder.transform([corridor_raw])[0]
        except Exception as e:
            logger.warning(f"Unknown corridor encountered '{corridor_raw}': {e}")
            return error_response(
                status_code=400,
                message=f"Validation Error: Unknown route_id/corridor '{corridor_raw}'."
            )
            
        internal_features = {
            **input_data, 
            "corridor": corridor_encoded,
            "dtd": dtd
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
                 "status": status
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