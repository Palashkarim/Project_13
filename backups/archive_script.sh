#!/usr/bin/env bash
# Create compressed snapshot of important dirs
DST_DIR="${1:-/var/backups/iot}"
TIMESTAMP=$(date +%F_%H%M)
mkdir -p "$DST_DIR"
tar -czf "$DST_DIR/iot_full_${TIMESTAMP}.tar.gz" \
  --exclude='./node_modules' \
  --exclude='./vendor' \
  ./server_core ./web_dashboard ./ota_repo ./ai_engine
echo "Snapshot stored in $DST_DIR"
