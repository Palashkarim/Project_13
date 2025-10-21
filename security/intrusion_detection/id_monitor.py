"""
Simple IDS monitor stub.
- Reads event stream (file/db) and applies rules to raise alerts.
- Replace file-based reading with Kafka/Redis stream or DB triggers in production.
"""
import json
from datetime import datetime

RULES_FILE = "rules.json"

def load_rules():
    with open(RULES_FILE) as f:
        return json.load(f)

def evaluate_event(event, rules):
    alerts = []
    # Example rule: many failed logins from same ip within X minutes
    for r in rules:
        if r['type'] == 'failed_logins':
            if event.get('type') == 'auth_failed' and event.get('ip') == r['target']:
                alerts.append({'msg': 'failed login from blocked ip', 'event': event})
    return alerts

if __name__ == "__main__":
    rules = load_rules()
    # Example: read events.json lines stream
    try:
        with open('events.log') as f:
            for line in f:
                event = json.loads(line.strip())
                alerts = evaluate_event(event, rules)
                for a in alerts:
                    print(datetime.utcnow().isoformat(), "ALERT:", a)
    except FileNotFoundError:
        print("No events.log found; IDS idle.")
