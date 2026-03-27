#!/usr/bin/env python3
"""
fetch_garmin.py — Fetches fitness data from Garmin Connect and writes garmin_data.json.

Usage:
    GARMIN_EMAIL=you@email.com GARMIN_PASSWORD=secret python3 fetch_garmin.py

Dependencies:
    pip install garminconnect
"""

import json
import os
import sys
from datetime import date, timedelta, datetime
from pathlib import Path

try:
    from garminconnect import (
        Garmin,
        GarminConnectAuthenticationError,
        GarminConnectConnectionError,
        GarminConnectTooManyRequestsError,
    )
except ImportError:
    print("ERROR: garminconnect not installed. Run: pip install garminconnect")
    sys.exit(1)

# ---------------------------------------------------------------------------
# Activity type labels (Ukrainian)
# ---------------------------------------------------------------------------

TYPE_LABELS = {
    "strength_training": "Силове тренування",
    "cycling": "Велосипед",
    "cardio_training": "Кардіо",
    "resort_skiing_snowboarding_ws": "Сноубординг",
    "running": "Біг",
    "walking": "Прогулянка",
    "swimming": "Плавання",
    "hiking": "Хайкінг",
    "yoga": "Йога",
    "indoor_cycling": "Велотренажер",
    "elliptical": "Еліпсоїд",
}

# ---------------------------------------------------------------------------
# Auth
# ---------------------------------------------------------------------------

def get_client() -> Garmin:
    email = os.environ.get("GARMIN_EMAIL")
    password = os.environ.get("GARMIN_PASSWORD")
    if not email or not password:
        print("ERROR: GARMIN_EMAIL and GARMIN_PASSWORD environment variables are required.")
        sys.exit(1)

    client = Garmin(email, password)
    try:
        # Try loading saved OAuth tokens first to avoid re-login
        client.login(tokenstore=os.path.expanduser("~/.garminconnect"))
    except Exception:
        # Fall back to full login and save tokens
        try:
            client.login()
            client.garth.dump(os.path.expanduser("~/.garminconnect"))
        except GarminConnectAuthenticationError as e:
            print(f"ERROR: Authentication failed — {e}")
            print("Check your GARMIN_EMAIL and GARMIN_PASSWORD.")
            sys.exit(1)
        except GarminConnectConnectionError as e:
            print(f"ERROR: Connection failed — {e}")
            sys.exit(1)
        except GarminConnectTooManyRequestsError as e:
            print(f"ERROR: Too many requests — {e}")
            sys.exit(1)
    return client

# ---------------------------------------------------------------------------
# Weight
# ---------------------------------------------------------------------------

def fetch_weight(client: Garmin) -> dict:
    today = date.today()
    week_ago = today - timedelta(days=7)

    def extract_kg(entry: dict | None) -> float | None:
        if not entry:
            return None
        # Weight is returned in grams
        grams = entry.get("weight")
        if grams is None:
            return None
        return round(grams / 1000, 1)

    def get_single(d: date) -> tuple[float | None, str | None]:
        try:
            result = client.get_body_composition(d.isoformat())
            entries = result.get("dateWeightList") or result.get("totalAverage") or []
            if isinstance(entries, list) and entries:
                entry = entries[-1]
                kg = extract_kg(entry)
                measured = entry.get("calendarDate") or d.isoformat()
                return kg, measured
            # Some API versions return a single dict
            if isinstance(result, dict) and result.get("weight"):
                return extract_kg(result), d.isoformat()
        except Exception:
            pass
        return None, None

    current_kg, measured_date = get_single(today)

    # Fallback: scan the last 14 days for the most recent record
    if current_kg is None:
        try:
            far_back = today - timedelta(days=14)
            range_data = client.get_body_composition(far_back.isoformat(), today.isoformat())
            entries = range_data.get("dateWeightList", [])
            # Filter entries that have a weight value
            valid = [e for e in entries if e.get("weight")]
            if valid:
                latest = valid[-1]
                current_kg = extract_kg(latest)
                measured_date = latest.get("calendarDate", today.isoformat())
        except Exception:
            pass

    previous_kg, _ = get_single(week_ago)

    # Determine trend
    if current_kg is not None and previous_kg is not None:
        if current_kg < previous_kg:
            trend = "down"
        elif current_kg > previous_kg:
            trend = "up"
        else:
            trend = "stable"
    else:
        trend = "stable"

    return {
        "current_kg": current_kg,
        "previous_kg": previous_kg,
        "trend": trend,
        "measured_date": measured_date or today.isoformat(),
    }

# ---------------------------------------------------------------------------
# Last activity
# ---------------------------------------------------------------------------

def fetch_last_activity(client: Garmin) -> dict:
    activity = client.get_last_activity()
    if not activity:
        return {}

    type_key = (activity.get("activityType") or {}).get("typeKey", "")
    type_label = TYPE_LABELS.get(type_key, type_key)

    start_time = activity.get("startTimeLocal", "")
    date_str = start_time[:10] if start_time else ""

    duration_sec = activity.get("duration")
    duration_min = round(duration_sec / 60) if duration_sec is not None else None

    distance_m = activity.get("distance")
    distance_km = round(distance_m / 1000, 1) if distance_m is not None else None

    avg_hr = activity.get("averageHR")
    calories = activity.get("calories")

    result = {
        "type": type_key,
        "type_label": type_label,
        "date": date_str,
        "duration_min": duration_min,
        "distance_km": distance_km,
        "avg_hr": int(avg_hr) if avg_hr is not None else None,
        "calories": int(calories) if calories is not None else None,
    }
    return result

# ---------------------------------------------------------------------------
# Weekly distance
# ---------------------------------------------------------------------------

def fetch_weekly_distance(client: Garmin) -> float:
    today = date.today()
    week_ago = today - timedelta(days=7)
    try:
        activities = client.get_activities_by_date(
            week_ago.isoformat(), today.isoformat()
        )
    except Exception:
        activities = []

    total_m = sum(
        a.get("distance", 0) or 0
        for a in (activities or [])
        if a.get("distance") is not None
    )
    return round(total_m / 1000, 1)

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    print("Connecting to Garmin Connect…")
    client = get_client()
    print("Authenticated.")

    print("Fetching weight…")
    weight = fetch_weight(client)

    print("Fetching last activity…")
    last_activity = fetch_last_activity(client)

    print("Fetching weekly distance…")
    weekly_distance_km = fetch_weekly_distance(client)

    output = {
        "updated_at": datetime.now().strftime("%Y-%m-%dT%H:%M:%S"),
        "weight": weight,
        "last_activity": last_activity,
        "weekly_distance_km": weekly_distance_km,
    }

    output_path = Path(__file__).parent / "garmin_data.json"
    output_path.write_text(json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Data written to {output_path}")


if __name__ == "__main__":
    main()
