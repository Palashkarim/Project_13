#!/usr/bin/env bash
# =============================================================================
# Restore a gzipped SQL dump into a (fresh) database.
# Usage: ./restore.sh path/to/iot_YYYYmmdd-HHMMSS.sql.gz target_db
# =============================================================================
set -euo pipefail

DUMP="${1:-}"
TARGET_DB="${2:-}"

if [[ -z "$DUMP" || -z "$TARGET_DB" ]]; then
  echo "Usage: $0 dump.sql.gz target_db"
  exit 1
fi

PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-postgres}"

echo "[*] Creating target DB: $TARGET_DB (if missing)"
createdb -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" "$TARGET_DB" || true

echo "[*] Restoring $DUMP -> $TARGET_DB"
gunzip -c "$DUMP" | psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$TARGET_DB"

echo "[*] Done."
