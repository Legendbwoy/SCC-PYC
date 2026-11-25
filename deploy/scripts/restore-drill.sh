#!/usr/bin/env bash
# Non-Production Backup Restore Drill Script (Spec Section 15.4 & 17.4)
set -euo pipefail

ENCRYPTED_ARCHIVE="${1:?Usage: restore-drill.sh <encrypted-backup-file> <drill-db-name>}"
DRILL_DB="${2:-scc_pyc_restore_drill}"

if [[ "${APP_ENV:-staging}" == "production" ]]; then
    echo "ERROR: Restore drills must only target a non-production database/environment." >&2
    exit 1
fi

if [[ -z "${BACKUP_PASSPHRASE:-}" ]]; then
    echo "ERROR: BACKUP_PASSPHRASE environment variable is required." >&2
    exit 1
fi

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "${WORK_DIR}"' EXIT

openssl enc -d -aes-256-cbc -pbkdf2 -iter 100000 \
    -in "${ENCRYPTED_ARCHIVE}" \
    -out "${WORK_DIR}/bundle.tar.gz" \
    -pass env:BACKUP_PASSPHRASE

tar -xzf "${WORK_DIR}/bundle.tar.gz" -C "${WORK_DIR}"

mysql -u "${DB_USERNAME:-root}" -e "DROP DATABASE IF EXISTS \`${DRILL_DB}\`; CREATE DATABASE \`${DRILL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u "${DB_USERNAME:-root}" "${DRILL_DB}" < "${WORK_DIR}/db.sql"

TABLE_COUNT="$(mysql -N -u "${DB_USERNAME:-root}" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DRILL_DB}';")"
echo "Restore drill succeeded: ${TABLE_COUNT} tables restored into ${DRILL_DB}."
