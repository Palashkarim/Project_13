# ai_engine/fault_prediction.py
"""
Baseline fault/anomaly detector:
- Uses rolling z-score per metric to flag spikes/drops
- Summarizes anomaly counts and worst offenders
Suitable as a first step; later swap for more advanced models (LOF/IsolationForest).
"""

from __future__ import annotations
import os
import json
from typing import List, Dict, Any
import numpy as np
import pandas as pd

class FaultPredictor:
    def __init__(self, z_thresh: float = 3.0, window: int = 24):
        self.z_thresh = z_thresh
        self.window = window  # points, not minutes (depends on resampling)

    def score_series(self, s: pd.Series) -> pd.Series:
        if s.empty: return s
        m = s.rolling(self.window, min_periods=max(3,self.window//3)).mean()
        sd = s.rolling(self.window, min_periods=max(3,self.window//3)).std().replace(0, np.nan)
        z = (s - m) / sd
        return z.abs()

    def score_dataframe(self, df: pd.DataFrame, metrics: List[str], window_minutes: int = 120):
        if df is None or df.empty:
            return [], {"count":0, "by_metric":{}}

        # prep timeseries per metric
        df = df.copy()
        df["time"] = pd.to_datetime(df["time"], utc=True)
        out_rows = []
        by_metric = {}

        end = df["time"].max()
        start = end - pd.Timedelta(minutes=window_minutes)
        df = df[(df["time"]>=start)&(df["time"]<=end)]
        if df.empty:
            return [], {"count":0, "by_metric":{}}

        for metric in metrics:
            d = df[df["metric"]==metric][["time","value"]].set_index("time").sort_index()
            d["value"] = pd.to_numeric(d["value"], errors="coerce")
            # resample to 5-min
            r = d["value"].resample("5min").mean().interpolate(limit=2)
            z = self.score_series(r)
            anom = z[z>self.z_thresh]
            cnt = int(anom.shape[0])
            by_metric[metric] = cnt
            for t, zval in anom.items():
                out_rows.append({"time": t.isoformat(), "metric": metric, "z": round(float(zval),2)})

        out_rows.sort(key=lambda x: x["time"])
        total = sum(by_metric.values())
        return out_rows, {"count": total, "by_metric": by_metric, "window_minutes": window_minutes}

def _path(model_dir: str, key: str) -> str:
    return os.path.join(model_dir, f"fault_{key}.json")

def save_fault_model(model_dir: str, key: str, model: FaultPredictor):
    os.makedirs(model_dir, exist_ok=True)
    with open(_path(model_dir, key), "w") as f:
        json.dump({"z_thresh": model.z_thresh, "window": model.window}, f)

def load_fault_model(model_dir: str, key: str) -> FaultPredictor|None:
    p = _path(model_dir, key)
    if not os.path.exists(p): return None
    with open(p,"r") as f:
        j=json.load(f)
    return FaultPredictor(z_thresh=j.get("z_thresh",3.0), window=j.get("window",24))
