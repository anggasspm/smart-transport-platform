import logging
import pandas as pd
from .loader import MODELS
from utils.response import error_response, success_response

logger = logging.getLogger(__name__)

def predict_model3(input_data: dict):
    if not MODELS["model3"]["loaded"]:
        logger.error("Prediction failed: Model 3 is not loaded.")
        return error_response(
            status_code=503,
            message="Model 3 is currently unavailable due to initialization failure."
        )
    
    model = MODELS["model3"]["anomaly_detector"]
    config = MODELS["model3"]["config"]
    
    try:
        feature_order = config["features"]
        try:
            features_vector = [input_data[feature] for feature in feature_order]
        except KeyError as e:
            logger.error(f"Missing required feature in input data: {e}")
            return error_response(
                status_code=400,
                message=f"Validation Error: Missing required feature {str(e)} for anomaly detection."
            )

        input_df = pd.DataFrame([features_vector], columns=feature_order)
        
        anomaly_score = float(-model.score_samples(input_df)[0])
        
        raw_label = int(model.predict(input_df)[0])
        is_anomaly = True if raw_label == -1 else False
        
        severity_thresholds = config["severity_thresholds"]
        
        if anomaly_score >= severity_thresholds["High"]:
            severity = "High"
        elif anomaly_score >= severity_thresholds["Medium"]:
            severity = "Medium"
        elif anomaly_score >= severity_thresholds["Low"]:
            severity = "Low"
        else:
            severity = None
            
        return success_response(
            data={
                "anomaly_score": anomaly_score,
                "severity": severity,
                "is_anomaly": is_anomaly
            },
            message="Anomaly prediction successful.",
            status_code=200
        )
        
    except Exception as e:
        logger.error(f"Unexpected error during Model 3 prediction: {e}")
        return error_response(
            status_code=500,
            message="An unexpected error occurred during anomaly prediction handling."
        )