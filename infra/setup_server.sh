#!/usr/bin/env bash
set -euo pipefail
# Purpose: baseline OS hardening + dependencies

# Update & basic tools
apt-get update -y
apt-get upgrade -y
apt-get install -y ca-certificates curl wget git ufw unzip jq

# Time sync
apt-get install -y chrony
systemctl enable chrony
systemctl start chrony

# Fail2ban
apt-get install -y fail2ban
systemctl enable fail2ban
systemctl start fail2ban

# Docker & Compose
if ! command -v docker >/dev/null 2>&1; then
  curl -fsSL https://get.docker.com | sh
fi
if ! command -v docker compose >/dev/null 2>&1; then
  DOCKER_COMPOSE_VERSION="v2.28.1"
  curl -SL https://github.com/docker/compose/releases/download/${DOCKER_COMPOSE_VERSION}/docker-compose-linux-x86_64 -o /usr/local/bin/docker-compose
  chmod +x /usr/local/bin/docker-compose
fi

# Create storage dirs
mkdir -p /var/iot/storage/firmware_builds
mkdir -p /var/iot/storage/export_jobs
chmod -R 750 /var/iot/storage

echo "[OK] Base server setup completed."

infra/install_mosquitto.sh

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

infra/install_postgres.sh

#!/usr/bin/env bash
set -euo pipefail

apt-get update -y
apt-get install -y postgresql postgresql-contrib

# Basic tuning (optional)
PG_CONF="/etc/postgresql/$(ls /etc/postgresql)/main/postgresql.conf"
PG_HBA="/etc/postgresql/$(ls /etc/postgresql)/main/pg_hba.conf"

sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/g" "$PG_CONF"
echo "host all all 0.0.0.0/0 md5" >> "$PG_HBA"

systemctl enable postgresql
systemctl restart postgresql

echo "[OK] PostgreSQL installed."