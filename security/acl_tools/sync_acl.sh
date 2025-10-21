#!/usr/bin/env bash
# Sync generated ACL file into mosquitto config and reload broker (containerized or host)
ACL_SRC="$1"
MOSQUITTO_CONF_DIR="${MOSQUITTO_CONF_DIR:-/etc/mosquitto}"
if [ -z "$ACL_SRC" ]; then
  echo "Usage: $0 generated_acl.conf"
  exit 1
fi
cp "$ACL_SRC" "$MOSQUITTO_CONF_DIR/acl.conf"
# If mosquitto in docker: docker kill -s HUP <mosquitto-container> or docker exec to reload
if command -v systemctl >/dev/null 2>&1; then
  systemctl restart mosquitto || true
else
  echo "Reload mosquitto manually (or restart container)"
fi
