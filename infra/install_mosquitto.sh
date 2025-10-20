#!/usr/bin/env bash
set -euo pipefail

# Install mosquitto (if bare-metal needed; in Docker we use the container)
apt-get update -y
apt-get install -y mosquitto mosquitto-clients

# Create dirs for config, logs, certs if running on host
mkdir -p /etc/mosquitto/conf.d /var/log/mosquitto /etc/mosquitto/certs

# Example: disable anonymous, include ACL + password file
cat >/etc/mosquitto/mosquitto.conf <<'CONF'
listener 1883
allow_anonymous false
password_file /etc/mosquitto/passwd
acl_file /etc/mosquitto/acl.conf
persistence true
persistence_location /var/lib/mosquitto/
log_dest file /var/log/mosquitto/mosquitto.log
log_type all
max_inflight_messages 100
message_size_limit 1048576
CONF

touch /etc/mosquitto/acl.conf
touch /etc/mosquitto/passwd

systemctl enable mosquitto
systemctl restart mosquitto

echo "[OK] Mosquitto installed."
