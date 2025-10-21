"""
Very small behavior model:
- Learns probability of ON/OFF by minute-of-day and day-of-week from state telemetry.
- Predicts next horizon as likely states with confidence scores.
State metric assumption:
- metric='state', value in {0,1} (or strings 'on'/'off')
"""

from __future__ import annotations
import os
import json
from dataclasses import dataclass, asdict
from typing import List, Dict, Any
import numpy as np
import pandas as pd

@dataclass
class BehaviorModel:
    # 7x1440 matrix of P(ON) per weekday x minute-of-day
    p_on: list  # nested list for JSON-compat

    @staticmethod
    def fit_from_dataframe(df_state: pd.DataFrame) -> 'BehaviorModel':
        if df_state is None or df_state.empty:
            return BehaviorModel(p_on=[[0.0]*1440 for _ in range(7)])
        df = df_state.copy()
        df = df[df["metric"]=="state"]
        if df.empty:
            return BehaviorModel(p_on=[[0.0]*1440 for _ in range(7)])
        # Normalize values to 0/1
        vals = df["value"].astype(str).str.lower().map(lambda x: 1.0 if x in ("1", "on", "true") else 0.0).astype(float)
        df = df.assign(v=vals)
        df["dow"] = df["time"].dt.weekday
        df["min"] = df["time"].dt.hour*60 + df["time"].dt.minute
        table = [[0.0]*1440 for _ in range(7)]
        counts = [[0]*1440 for _ in range(7)]
        for _, r in df.iterrows():
            d = int(r["dow"]); m = int(r["min"]); v=float(r["v"])
            table[d][m] += v
            counts[d][m] += 1
        # average
        for d in range(7):
            for m in range(1440):
                c = counts[d][m]
                if c>0:
                    table[d][m] = table[d][m]/c
        return BehaviorModel(p_on=table)

    def predict_next(self, horizon_minutes: int = 60) -> List[Dict[str, Any]]:
        now = pd.Timestamp.utcnow()
        dow = now.weekday()
        minute = now.hour*60 + now.minute
        out = []
        for i in range(horizon_minutes):
            m = (minute + i) % 1440
            p = float(self.p_on[dow][m]) if 0<=m<1440 else 0.0
            state = "on" if p>=0.5 else "off"
            out.append({"t_plus_min": i, "prob_on": round(p,3), "expected_state": state})
        return out

def _path(model_dir: str, key: str) -> str:
    return os.path.join(model_dir, f"behavior_{key}.json")

def save_behavior_model(model_dir: str, key: str, model: BehaviorModel):
    os.makedirs(model_dir, exist_ok=True)
    with open(_path(model_dir, key), "w") as f:
        json.dump(asdict(model), f)

def load_behavior_model(model_dir: str, key: str) -> BehaviorModel|None:
    p = _path(model_dir, key)
    if not os.path.exists(p): return None
    with open(p,"r") as f:
        j=json.load(f)
    return BehaviorModel(**j)
