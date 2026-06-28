from fastapi import FastAPI
from fastapi.responses import JSONResponse, Response
from services.loader import lifespan, MODELS
from schemas import PredictDelayRequest, PredictCrowdRequest, DetectAnomalyRequest
from services.model1_service_v2 import predict_model1_v2
from services.model2_service import predict_model2
from services.model3_service import predict_model3
from prometheus_client import Counter, Histogram, Gauge, generate_latest, CONTENT_TYPE_LATEST
import time

app = FastAPI(
    title="Smart Transport ML Service",
    version="1.0.0",
    lifespan=lifespan
)

REQUEST_COUNT = Counter(
    'python_ml_http_requests_total',
    'Total HTTP requests',
    ['method', 'path', 'status', 'job']
)
REQUEST_LATENCY = Histogram(
    'python_ml_http_request_duration_seconds',
    'HTTP request duration',
    ['method', 'path', 'job'],
    buckets=[0.05, 0.1, 0.3, 0.5, 1, 2, 5]
)

@app.middleware("http")
async def metrics_middleware(request, call_next):
    start = time.time()
    response = await call_next(request)
    duration = time.time() - start
    path = request.url.path
    if path != "/metrics":
        REQUEST_COUNT.labels(
            method=request.method,
            path=path,
            status=str(response.status_code),
            job="python-ml"
        ).inc()
        REQUEST_LATENCY.labels(
            method=request.method,
            path=path,
            job="python-ml"
        ).observe(duration)
    return response

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

@app.get("/metrics")
def metrics():
    return Response(generate_latest(), media_type=CONTENT_TYPE_LATEST)

@app.post("/predict/delay")
def predict_delay(req: PredictDelayRequest):
    result = predict_model1_v2(req.model_dump())
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