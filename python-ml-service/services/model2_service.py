import logging
import pandas as pd
from .loader import MODELS
from utils.response import error_response, success_response

logger = logging.getLogger(__name__)

def predict_model2(input_data: dict):
    if not MODELS["model2"]["loaded"]:
        logger.error("Prediction failed: Model 2 is not loaded.")
        return error_response(
            status_code=503,
            message="Model 2 is currently unavailable due to initialization failure."
        )
    
    classifier = MODELS["model2"]["classifier"]
    target_mapping = MODELS["model2"]["target_mapping"]
    feature_order = MODELS["model2"]["feature_order"]

    try:
        if 'is_holiday' in input_data:
            input_data['is_holiday'] = int(input_data['is_holiday'])
            
        df_input = pd.DataFrame([input_data])
        
        try:
            df_input = df_input[feature_order]
        except KeyError as e:
            logger.error(f"Missing required feature in input data: {e}")
            return error_response(
                status_code=400,
                message=f"Validation Error: Missing required feature {str(e)} for crowd prediction."
            )
            
        prediction_idx = int(classifier.predict(df_input)[0])
        
        sample_key = list(target_mapping.keys())[0]
        if isinstance(sample_key, str):
            inverted_mapping = {v: k for k, v in target_mapping.items()}
            label = inverted_mapping.get(prediction_idx)
        else:
            label = target_mapping.get(prediction_idx)
            
        if label is None:
            logger.error(f"Prediction index {prediction_idx} not found in target mapping.")
            return error_response(
                status_code=500,
                message="Prediction mapping error: Model predicted an invalid category index."
            )
            
        return success_response(data=label, message="Crowd prediction successful.", status_code=200)
        
    except Exception as e:
        logger.error(f"Unexpected error during Model 2 prediction: {e}")
        return error_response(
            status_code=500,
            message="An unexpected error occurred during prediction handling."
        )