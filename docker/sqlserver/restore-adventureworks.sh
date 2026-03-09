#!/usr/bin/env bash
set -euo pipefail

SA_PASSWORD="${SA_PASSWORD:-YourStrong!Passw0rd}"
MSSQL_HOST="${MSSQL_HOST:-sqlserver}"
MSSQL_PORT="${MSSQL_PORT:-1433}"
DATABASE_NAME="${DATABASE_NAME:-AdventureWorksLT2022}"
BACKUP_FILE="${BACKUP_FILE:-/var/opt/mssql/backup/AdventureWorksLT2022.bak}"

resolve_sqlcmd() {
  if command -v /opt/mssql-tools18/bin/sqlcmd >/dev/null 2>&1; then
    echo "/opt/mssql-tools18/bin/sqlcmd"
    return
  fi

  if command -v /opt/mssql-tools/bin/sqlcmd >/dev/null 2>&1; then
    echo "/opt/mssql-tools/bin/sqlcmd"
    return
  fi

  echo "sqlcmd not found" >&2
  exit 1
}

SQLCMD_BIN="$(resolve_sqlcmd)"

echo "Waiting for SQL Server at ${MSSQL_HOST}:${MSSQL_PORT}..."
until "$SQLCMD_BIN" -C -S "${MSSQL_HOST},${MSSQL_PORT}" -U sa -P "$SA_PASSWORD" -Q "SELECT 1" >/dev/null 2>&1; do
  sleep 2
  echo "Still waiting for SQL Server..."
done

echo "SQL Server is ready."

if [ ! -f "$BACKUP_FILE" ]; then
  echo "Backup file not found at $BACKUP_FILE" >&2
  exit 1
fi

DB_EXISTS=$("$SQLCMD_BIN" -C -h -1 -W -S "${MSSQL_HOST},${MSSQL_PORT}" -U sa -P "$SA_PASSWORD" -Q "SET NOCOUNT ON; SELECT COUNT(*) FROM sys.databases WHERE name='${DATABASE_NAME}';")
DB_EXISTS="$(echo "$DB_EXISTS" | tr -d '[:space:]')"

if [ "$DB_EXISTS" = "1" ]; then
  echo "Database ${DATABASE_NAME} already exists. Skipping restore."
  exit 0
fi

echo "Inspecting backup logical file names..."
FILELIST=$(
  "$SQLCMD_BIN" -C -h -1 -W -s "|" -S "${MSSQL_HOST},${MSSQL_PORT}" -U sa -P "$SA_PASSWORD" -Q "RESTORE FILELISTONLY FROM DISK='${BACKUP_FILE}';"
)

DATA_LOGICAL=$(echo "$FILELIST" | awk -F'|' '/\|D\|/ { print $1; exit }' | xargs)
LOG_LOGICAL=$(echo "$FILELIST" | awk -F'|' '/\|L\|/ { print $1; exit }' | xargs)

if [ -z "$DATA_LOGICAL" ] || [ -z "$LOG_LOGICAL" ]; then
  echo "Could not extract logical names from backup file." >&2
  echo "$FILELIST" >&2
  exit 1
fi

echo "Restoring ${DATABASE_NAME} from ${BACKUP_FILE}..."
"$SQLCMD_BIN" -C -S "${MSSQL_HOST},${MSSQL_PORT}" -U sa -P "$SA_PASSWORD" -Q "RESTORE DATABASE [${DATABASE_NAME}] FROM DISK='${BACKUP_FILE}' WITH MOVE '${DATA_LOGICAL}' TO '/var/opt/mssql/data/${DATABASE_NAME}.mdf', MOVE '${LOG_LOGICAL}' TO '/var/opt/mssql/data/${DATABASE_NAME}_log.ldf', RECOVERY, REPLACE;"

echo "Restore completed successfully."
