#!/usr/bin/env bash
# =============================================================================
# Daily logical backup (pg_dump) with rotation.
# Put in cron:  0 3 * * * /path/to/daily_backup.sh
# =============================================================================
set -euo pipefail

# CONFIG
PGHOST="${PGHOST:-127.0.0.1}"
PGPORT="${PGPORT:-5432}"
PGUSER="${PGUSER:-postgres}"         # or dedicated backup user with read perms
PGDATABASE="${PGDATABASE:-iot}"      # your database name
BACKUP_DIR="${BACKUP_DIR:-$(dirname "$0")/..}/backups"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="${BACKUP_DIR}/iot_${STAMP}.sql.gz"

echo "[*] Dumping ${PGDATABASE} @ ${PGHOST}:${PGPORT}"
pg_dump -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" --no-owner --format=plain \
  | gzip -9 > "$OUT"

echo "[*] Created: $OUT"
echo "[*] Rotating backups > ${RETENTION_DAYS} days"
find "$BACKUP_DIR" -name "iot_*.sql.gz" -mtime +${RETENTION_DAYS} -delete
echo "[*] Done."

Make executable:

chmod +x postgres_primary/backups/daily_backup.sh

postgres_primary/backups/restore.sh

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
