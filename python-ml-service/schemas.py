from pydantic import BaseModel, Field

from pydantic import BaseModel, Field


class PredictDelayRequest(BaseModel):
    bus_id: int
    hour: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=0, le=6)
    route_id: str
    weather: int = Field(default=0, ge=0, le=2)

class PredictCrowdRequest(BaseModel):
    stop_id: int
    hour: int = Field(..., ge=0, le=23)
    day_of_week: int = Field(..., ge=0, le=6)
    weather: int = Field(..., ge=0, le=2)
    prev_count: int = Field(..., ge=0)
    is_holiday: bool

class DetectAnomalyRequest(BaseModel):
    speed_kmh: float 
    passenger_count: int 
    engine_temp_c: float 
    rolling_mean_1h: float 
    z_score: float
