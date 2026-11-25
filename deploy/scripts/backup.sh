#!/usr/bin/env bash
# Encrypted Off-Site Ready Backup Script for St. Cecilia PYC (Spec Section 15.4)
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/scc-pyc}"
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/scc-pyc}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
TIMESTAMP="$(date -u +%Y%m%dT%H%M%SZ)"

if [[ -z "${BACKUP_PASSPHRASE:-}" ]]; then
    echo "ERROR: BACKUP_PASSPHRASE environment variable is required for AES-256 encryption." >&2
    exit 1
fi

mkdir -p "${BACKUP_ROOT}"
WORK_DIR="$(mktemp -d)"
trap 'rm -rf "${WORK_DIR}"' EXIT

# Load DB connection parameters safely
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-scc_pyc}"
DB_USERNAME="${DB_USERNAME:-pyc_app}"

echo "[${TIMESTAMP}] Dumping MySQL database ${DB_DATABASE}..."
mysqldump --single-transaction --routines --triggers \
    -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USERNAME}" "${DB_DATABASE}" \
    > "${WORK_DIR}/db.sql"

echo "[${TIMESTAMP}] Archiving uploaded storage files..."
tar -czf "${WORK_DIR}/storage.tar.gz" -C "${APP_DIR}/storage" app

tar -czf "${WORK_DIR}/bundle.tar.gz" -C "${WORK_DIR}" db.sql storage.tar.gz

OUT_FILE="${BACKUP_ROOT}/scc-pyc-backup-${TIMESTAMP}.tar.gz.enc"
openssl enc -aes-256-cbc -salt -pbkdf2 -iter 100000 \
    -in "${WORK_DIR}/bundle.tar.gz" \
    -out "${OUT_FILE}" \
    -pass env:BACKUP_PASSPHRASE

chmod 600 "${OUT_FILE}"
find "${BACKUP_ROOT}" -type f -name "scc-pyc-backup-*.tar.gz.enc" -mtime "+${RETENTION_DAYS}" -delete

echo "[${TIMESTAMP}] Encrypted backup written to ${OUT_FILE}"
