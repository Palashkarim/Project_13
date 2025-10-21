"""
Energy optimization heuristics:
- Compute baseline kWh/day from recent telemetry (metric='power' in watts)
- If TOU (time-of-use) hints are provided later, can shift flexible loads
- For now: simple suggestions to defer heavy loads to low-usage windows
"""

from __future__ import annotations
from typing import List, Dict, Any
from datetime import datetime, timedelta
import numpy as np
import pandas as pd

def _watts_to_kwh(series_watts: pd.Series, freq_seconds: float) -> float:
    """Approximate kWh from average watts and sampling frequency."""
    if series_watts.empty: return 0.0
    avg_w = series_watts.mean()
    hours = (len(series_watts) * freq_seconds) / 3600.0
    return (avg_w * hours) / 1000.0

def suggest_energy_actions(df_power: pd.DataFrame,
                           devices: List[Dict[str, Any]],
                           tariff_per_kwh: float,
                           horizon_hours: int = 24) -> Dict[str, Any]:
    """
    df_power: rows for metric='power', value=watts, time desc
    devices: [{device_id, rated_watts, flexible:bool, earliest?:HH:MM, latest?:HH:MM, min_duration?:minutes}]
    """
    if df_power.empty:
        return {"suggestions": [], "expected_savings_kwH": 0.0, "expected_savings_currency": 0.0}

    df = df_power[df_power["metric"]=="power"].copy()
    df = df.sort_values("time")  # asc for resample
    df["value"] = pd.to_numeric(df["value"], errors="coerce").fillna(0.0)

    # Resample to 5-min average to smooth spikes
    df5 = df.set_index("time").resample("5min").mean(numeric_only=True).dropna()
    baseline_kwh = (df5["value"].sum() * (5/60.0)) / 1000.0  # watts*hours /1000

    # Find low-usage windows in the last day
    last_day = df5.last("24H")
    if last_day.empty:
        last_day = df5.tail(288)
    # Score each 30-min slot by average watts (lower = better time to run heavy loads)
    slots = last_day["value"].rolling("30min").mean().dropna().sort_values()

    suggestions = []
    expected_kwh_save = 0.0

    # For each flexible device, recommend a 30-min start in the lowest slots
    for d in devices:
        rated = float(d.get("rated_watts", 500))
        flexible = bool(d.get("flexible", True))
        min_dur = int(d.get("min_duration", 30))  # minutes
        if not flexible:  # skip non-flex
            continue
        if slots.empty:
            continue
        start_time = slots.index[0].to_pydatetime()
        # obey earliest/latest if given
        earliest = d.get("earliest")
        latest = d.get("latest")
        if earliest:
            et = datetime.combine(start_time.date(), pd.to_datetime(earliest).time()).replace(tzinfo=start_time.tzinfo)
            if start_time < et: start_time = et
        if latest:
            lt = datetime.combine(start_time.date(), pd.to_datetime(latest).time()).replace(tzinfo=start_time.tzinfo)
            if start_time > lt:  # fallback: keep suggested but flag
                pass

        kwh_device = (rated * (min_dur/60.0)) / 1000.0
        # Heuristic savings if shifting away from peak to low slot (10–25%)
        est_save = kwh_device * 0.15
        expected_kwh_save += est_save

        suggestions.append({
            "device_id": d.get("device_id", "unknown"),
            "action": "schedule_defer",
            "suggested_start": start_time.isoformat(),
            "duration_minutes": min_dur,
            "estimated_saving_kwh": round(est_save, 3)
        })

    return {
        "suggestions": suggestions,
        "expected_savings_kwH": round(expected_kwh_save, 3),
        "expected_savings_currency": round(expected_kwh_save * tariff_per_kwh, 2)
    }


