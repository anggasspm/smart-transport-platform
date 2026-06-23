from fastapi import FastAPI
from fastapi.responses import JSONResponse
from services.loader import lifespan, MODELS
from schemas import PredictDelayRequest, PredictCrowdRequest, DetectAnomalyRequest
from services.model1_service import predict_model1
from services.model2_service import predict_model2
from services.model3_service import predict_model3

app = FastAPI(
    title="Smart Transport ML Service",
    version="1.0.0",
    lifespan=lifespan
)

@app.get("/health")
def health():
    model_status = {}
    all_healthy = True
    
    for model_name, info in MODELS.items():
        loaded = info.get("loaded", False)
        model_status[model_name] = {
            "status": "LOADED" if loaded else "FAILED",
            "error": info.get("error")  
        }
        if not loaded:
            all_healthy = False
            
    return {
        "status": "healthy" if all_healthy else "degraded",
        "models": model_status
    }

@app.post("/predict/delay")
def predict_delay(req: PredictDelayRequest):
    result = predict_model1(req.model_dump())
    status_code = result.get("code", 200) if isinstance(result, dict) and "code" in result else 200
    return JSONResponse(status_code=status_code, content=result)

@app.post("/predict/crowd")
def predict_crowd(req: PredictCrowdRequest):
    result = predict_model2(req.model_dump())
    status_code = result.get("code", 200) if isinstance(result, dict) and "code" in result else 200
    return JSONResponse(status_code=status_code, content=result)

@app.post("/detect/anomaly")
def detect_anomaly(req: DetectAnomalyRequest):
    result = predict_model3(req.model_dump())
    status_code = result.get("code", 200) if isinstance(result, dict) and "code" in result else 200
    return JSONResponse(status_code=status_code, content=result)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5000)