"""
Offline/async-ish training helpers. In this simple version, training is quick and
can run synchronously when called (your PHP can call /train/all via cron).
- BehaviorModel: fit from last N days of metric='state'
- FaultPredictor: just persist chosen hyperparams (no heavy fitting needed)
"""

from __future__ import annotations
import os
from typing import Optional, List, Dict, Any
from datetime import datetime, timedelta, timezone

import pandas as pd
from sqlalchemy import text, Engine

from user_behavior_model import BehaviorModel, save_behavior_model
from fault_prediction import FaultPredictor, save_fault_model

def _fetch(engine: Engine, user_id: Optional[int], device_id: Optional[str],
           metrics: List[str], lookback_days: int) -> pd.DataFrame:
    to_ts = datetime.now(timezone.utc)
    from_ts = to_ts - timedelta(days=lookback_days)
    params = {"from_ts": from_ts, "to_ts": to_ts}
    conds = ["time >= :from_ts", "time <= :to_ts"]
    if user_id:
        conds.append("user_id = :user_id"); params["user_id"] = user_id
    if device_id:
        conds.append("device_id = :device_id"); params["device_id"] = device_id
    if metrics:
        conds.append("metric = ANY(:metrics)"); params["metrics"] = metrics

    sql = f"""
      SELECT time, user_id, device_id, metric, value
      FROM iot.telemetry
      WHERE {" AND ".join(conds)}
      ORDER BY time DESC
    """
    with engine.connect() as conn:
        df = pd.read_sql(text(sql), conn, params=params)
    if not df.empty:
        df["time"] = pd.to_datetime(df["time"], utc=True)
    return df

def train_all_models(model_dir: str,
                     engine: Engine,
                     user_id: Optional[int],
                     device_id: Optional[str],
                     metrics: List[str],
                     lookback_days: int) -> Dict[str, Any]:
    os.makedirs(model_dir, exist_ok=True)
    df = _fetch(engine, user_id, device_id, metrics=list(set(metrics+["state"])), lookback_days=lookback_days)

    # Behavior (state)
    df_state = df[df["metric"]=="state"].copy() if not df.empty else pd.DataFrame()
    key_b = f"user{user_id or 'all'}" + (f"_dev_{device_id}" if device_id else "")
    model_b = BehaviorModel.fit_from_dataframe(df_state)
    save_behavior_model(model_dir, key_b, model_b)

    # Fault predictor (baseline hyperparams; could be tuned later from df)
    key_f = f"user{user_id or 'all'}_faults"
    fp = FaultPredictor(z_thresh=3.0, window=24)  # keep defaults for now
    save_fault_model(model_dir, key_f, fp)

    return {
        "ok": True,
        "behavior_model_key": key_b,
        "fault_model_key": key_f,
        "samples_used": int(df.shape[0]) if df is not None else 0
    }
