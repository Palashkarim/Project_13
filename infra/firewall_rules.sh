#!/usr/bin/env bash
set -euo pipefail

# UFW baseline
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp        # SSH
ufw allow 80/tcp        # HTTP (optional if using HTTPS)
ufw allow 443/tcp       # HTTPS (Nginx)
ufw allow 1883/tcp      # MQTT (LAN only ideally)
ufw allow 5432/tcp      # PostgreSQL (restrict by IP in production)
ufw allow 3000/tcp      # Grafana (optional)
ufw allow 9090/tcp      # Prometheus (optional)
ufw --force enable

echo "[OK] Firewall rules applied."
