import json
import joblib  
import logging
import os
from contextlib import asynccontextmanager
from fastapi import FastAPI

logger = logging.getLogger(__name__)

MODELS = {
    "model1": {"regressor": None, "encoder": None, "config": None, "loaded": False, "error": None},
    "model2": {"classifier": None, "target_mapping": None, "feature_order": None, "loaded": False, "error": None},
    "model3": {"scaler": None, "anomaly_detector": None, "config": None, "loaded": False, "error": None}
}

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.dirname(CURRENT_DIR)
MODELS_DIR = os.path.abspath(os.path.join(PARENT_DIR, "models"))

def load_joblib(file_name: str):
    path = os.path.join(MODELS_DIR, file_name)
    return joblib.load(path)  

def load_json(file_name: str):
    path = os.path.join(MODELS_DIR, file_name)
    with open(path, "r") as f:
        return json.load(f)

def load_model_1():
    try:
        MODELS["model1"]["regressor"] = load_joblib("model1_delay_regressor.pkl")
        MODELS["model1"]["encoder"] = load_joblib("model1_corridor_encoder.pkl")
        MODELS["model1"]["config"] = load_json("model1_config.json")
        MODELS["model1"]["loaded"] = True
        logger.info("Model 1 successfully loaded via joblib.")
    except Exception as e:
        MODELS["model1"]["loaded"] = False
        MODELS["model1"]["error"] = str(e)
        logger.error(f"Failed to load Model 1: {e}")

def load_model_2():
    try:
        MODELS["model2"]["classifier"] = load_joblib("model2_crowd_classifier.pkl")
        MODELS["model2"]["target_mapping"] = load_joblib("model2_target_mapping.pkl")
        MODELS["model2"]["feature_order"] = load_joblib("model2_feature_order.pkl")
        MODELS["model2"]["loaded"] = True
        logger.info("Model 2 successfully loaded via joblib.")
    except Exception as e:
        MODELS["model2"]["loaded"] = False
        MODELS["model2"]["error"] = str(e)
        logger.error(f"Failed to load Model 2: {e}")

def load_model_3():
    try:
        MODELS["model3"]["scaler"] = load_joblib("model3_scaler.pkl")
        MODELS["model3"]["anomaly_detector"] = load_joblib("model3_anomaly_detector.pkl")
        MODELS["model3"]["config"] = load_json("model3_config.json")
        MODELS["model3"]["loaded"] = True
        logger.info("Model 3 successfully loaded via joblib.")
    except Exception as e:
        MODELS["model3"]["loaded"] = False
        MODELS["model3"]["error"] = str(e)
        logger.error(f"Failed to load Model 3: {e}")

@asynccontextmanager
async def lifespan(app: FastAPI):
    load_model_1()
    load_model_2()
    load_model_3()
    yield
    logger.info("Shutting down application and clearing model registry.")
    for model_key in MODELS:
        for artifact_key in MODELS[model_key]:
            if artifact_key not in ["loaded", "error"]:
                MODELS[model_key][artifact_key] = None
        MODELS[model_key]["loaded"] = False