""""
AI Engine API
-------------
Small FastAPI service that your PHP backend calls.
Endpoints:
- POST /optimize/energy         -> energy-saving suggestions & schedule
- POST /predict/behavior        -> predict likely device states in next horizon
- POST /predict/faults          -> anomaly/fault probabilities
- POST /train/all               -> run training pipeline and refresh models

Auth:
- Optional bearer token via AI_TOKEN env (set the same in your backend call)

DB:
- Read-only access to PostgreSQL iot.telemetry table as needed.
"""

import os
import time
from typing import List, Optional, Dict, Any
from datetime import datetime, timedelta, timezone

import numpy as np
import pandas as pd
from fastapi import FastAPI, Depends, HTTPException, Header
from pydantic import BaseModel, Field

from energy_optimizer import suggest_energy_actions
from user_behavior_model import BehaviorModel, load_behavior_model, save_behavior_model
from fault_prediction import FaultPredictor, load_fault_model, save_fault_model
from trainer.train import train_all_models

from sqlalchemy import create_engine, text

# -----------------------------------------------------------------------------
# Config / Auth
# -----------------------------------------------------------------------------
AI_TOKEN = os.getenv("AI_TOKEN", "")  # if set, clients must send Authorization: Bearer <AI_TOKEN>

def require_auth(authorization: Optional[str] = Header(None)):
    if AI_TOKEN:
        if not authorization or not authorization.startswith("Bearer "):
            raise HTTPException(status_code=401, detail="Missing bearer token")
        token = authorization[7:].strip()
        if token != AI_TOKEN:
            raise HTTPException(status_code=403, detail="Invalid token")
    return True

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = int(os.getenv("DB_PORT", "5432"))
DB_NAME = os.getenv("DB_NAME", "iot")
DB_USER = os.getenv("DB_USER", "postgres")
DB_PASS = os.getenv("DB_PASS", "password")

SQLALCHEMY_URL = f"postgresql+psycopg2://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}"
engine = create_engine(SQLALCHEMY_URL, pool_pre_ping=True, pool_recycle=3600)

MODEL_DIR = os.getenv("MODEL_DIR", "/app/models")
os.makedirs(MODEL_DIR, exist_ok=True)

app = FastAPI(title="AI Engine", version="1.0")

# -----------------------------------------------------------------------------
# Schemas
# -----------------------------------------------------------------------------
class TeleQuery(BaseModel):
    user_id: int
    device_id: Optional[str] = None
    metric: Optional[str] = None
    from_ts: Optional[datetime] = None
    to_ts: Optional[datetime] = None
    limit: int = 5000

class EnergyOptimizeRequest(BaseModel):
    user_id: int
    tariff_per_kwh: Optional[float] = Field(default=None, description="Override default tariff")
    devices: List[Dict[str, Any]] = Field(default_factory=list, description="[{device_id, rated_watts, schedule_constraints...}]")
    horizon_hours: int = 24

class EnergyOptimizeResponse(BaseModel):
    suggestions: List[Dict[str, Any]]
    expected_savings_kwH: float
    expected_savings_currency: float

class BehaviorPredictRequest(BaseModel):
    user_id: int
    device_id: Optional[str] = None
    horizon_minutes: int = 60

class BehaviorPredictResponse(BaseModel):
    predictions: List[Dict[str, Any]]

class FaultPredictRequest(BaseModel):
    user_id: int
    device_id: Optional[str] = None
    metrics: List[str] = Field(default_factory=lambda: ["voltage","current","power"])
    window_minutes: int = 120

class FaultPredictResponse(BaseModel):
    anomalies: List[Dict[str, Any]]
    summary: Dict[str, Any]

class TrainRequest(BaseModel):
    user_id: Optional[int] = None
    device_id: Optional[str] = None
    metrics: List[str] = Field(default_factory=lambda: ["power","temp","current"])
    lookback_days: int = 30

# -----------------------------------------------------------------------------
# Helpers
# -----------------------------------------------------------------------------
def fetch_telemetry(q: TeleQuery) -> pd.DataFrame:
    """Fetch telemetry from iot.telemetry with basic filters."""
    to_ts = q.to_ts or datetime.now(timezone.utc)
    from_ts = q.from_ts or (to_ts - timedelta(days=7))

    params = {
        "user_id": q.user_id,
        "from_ts": from_ts,
        "to_ts": to_ts,
        "limit": q.limit
    }

    conditions = ["user_id = :user_id", "time >= :from_ts", "time <= :to_ts"]
    if q.device_id:
        conditions.append("device_id = :device_id")
        params["device_id"] = q.device_id
    if q.metric:
        conditions.append("metric = :metric")
        params["metric"] = q.metric

    sql = f"""
        SELECT time, device_id, metric, value, tags
        FROM iot.telemetry
        WHERE {" AND ".join(conditions)}
        ORDER BY time DESC
        LIMIT :limit
    """
    with engine.connect() as conn:
        df = pd.read_sql(text(sql), conn, params=params)
    # Ensure datetime
    if not df.empty:
        df["time"] = pd.to_datetime(df["time"], utc=True)
    return df

# -----------------------------------------------------------------------------
# Endpoints
# -----------------------------------------------------------------------------
@app.get("/healthz")
def health():
    # quick DB round-trip
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        db_ok = True
    except Exception:
        db_ok = False
    return {"status": "ok", "db": db_ok, "time": time.time()}

@app.post("/optimize/energy", response_model=EnergyOptimizeResponse, dependencies=[Depends(require_auth)])
def optimize_energy(req: EnergyOptimizeRequest):
    df_power = fetch_telemetry(TeleQuery(user_id=req.user_id, metric="power", limit=20000))
    tariff = req.tariff_per_kwh if req.tariff_per_kwh is not None else float(os.getenv("DEFAULT_TARIFF_PER_KWH", "0.12"))
    result = suggest_energy_actions(df_power, req.devices, tariff, horizon_hours=req.horizon_hours)
    return EnergyOptimizeResponse(**result)

@app.post("/predict/behavior", response_model=BehaviorPredictResponse, dependencies=[Depends(require_auth)])
def predict_behavior(req: BehaviorPredictRequest):
    # Load or fit a simple behavior model (per user or per user+device)
    key = f"user{req.user_id}" + (f"_dev_{req.device_id}" if req.device_id else "")
    model = load_behavior_model(MODEL_DIR, key)
    if model is None:
        # fit quickly from telemetry
        df_state = fetch_telemetry(TeleQuery(user_id=req.user_id, device_id=req.device_id, metric="state", limit=10000))
        model = BehaviorModel.fit_from_dataframe(df_state)
        save_behavior_model(MODEL_DIR, key, model)

    preds = model.predict_next(horizon_minutes=req.horizon_minutes)
    return BehaviorPredictResponse(predictions=preds)

@app.post("/predict/faults", response_model=FaultPredictResponse, dependencies=[Depends(require_auth)])
def predict_faults(req: FaultPredictRequest):
    df = fetch_telemetry(TeleQuery(user_id=req.user_id, device_id=req.device_id, limit=50000))
    model_key = f"user{req.user_id}_faults"
    fp = load_fault_model(MODEL_DIR, model_key)
    if fp is None:
        fp = FaultPredictor()  # baseline thresholds/rolling z-score
        save_fault_model(MODEL_DIR, model_key, fp)
    anomalies, summary = fp.score_dataframe(df, metrics=req.metrics, window_minutes=req.window_minutes)
    return FaultPredictResponse(anomalies=anomalies, summary=summary)

@app.post("/train/all")
def train_all(req: TrainRequest, ok: bool = Depends(require_auth)):
    # Train behavior & fault models; return summary paths/metrics
    out = train_all_models(model_dir=MODEL_DIR,
                           engine=engine,
                           user_id=req.user_id,
                           device_id=req.device_id,
                           metrics=req.metrics,
                           lookback_days=req.lookback_days)
    return out
