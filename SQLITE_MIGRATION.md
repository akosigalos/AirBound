# AIR-BOUND SQLite migration

## Safety model

MySQL remains the default driver. The migration reads from MySQL and writes only to a new SQLite file; it never drops, truncates, updates, or deletes MySQL tables or records.

The expected source is `airbound_app` with `users`, `devices`, `readings`, `alerts`, `sensor_data`, and `predictions`. If any expected table is missing, the migration stops before SQLite creation.

## Backup

Before migration, create a MySQL backup while MySQL is running:

```powershell
C:\xampp\mysql\bin\mysqldump.exe --host=127.0.0.1 --user=root --single-transaction --routines --events --databases airbound_app --result-file=database-backups\airbound_app-pre-sqlite-migration.sql
```

The `database-backups/*.sql` path is ignored by Git. Keep the backup outside the web root as well for disaster recovery.

## Create and copy the SQLite database

Do not create the database manually. After validating the MySQL backup, run:

```powershell
php tools/migrate_mysql_to_sqlite.php --run
```

The script creates `database/airbound.sqlite`, retains original IDs and timestamps, compares row counts table-by-table, validates foreign keys, and saves `database/sqlite-migration-report.json`.

Run without `--run` for a no-write safety check. It refuses to overwrite an existing SQLite file.

## Switch drivers

MySQL is the default and requires no setting. To opt in to SQLite for one PowerShell session after migration:

```powershell
$env:DATABASE_DRIVER = 'sqlite'
php -S localhost:8000
```

Optional: set `SQLITE_PATH` to an absolute SQLite file path. The application refuses to silently create a missing SQLite database.

## Roll back

Remove `DATABASE_DRIVER=sqlite` or set `DATABASE_DRIVER=mysql`, then restart Apache/PHP. The original MySQL configuration and database are untouched.

## Compatibility changes

- SQLite maps unsigned numeric/decimal values to `INTEGER` or `REAL`, JSON to `TEXT`, and device status to a `CHECK` constraint.
- `NOW()` was replaced with a PHP-generated timestamp for registration.
- Prediction timestamp filtering uses driver-specific MySQL/SQLite date functions.
- Existing MySQL-only per-endpoint schema checks are bypassed only when SQLite is explicitly selected; the SQLite schema is established by the migration tool.

## Testing after migration

With `DATABASE_DRIVER=sqlite`, verify login, registration, devices, ingestion, readings, alerts, SSE streams, dashboard charts, maps, and predictions. Do not run the simulator as a migration test because it creates synthetic readings.
