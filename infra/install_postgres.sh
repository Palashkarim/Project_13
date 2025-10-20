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
