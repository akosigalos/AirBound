<?php
// DB config — MySQL remains the default. Set DATABASE_DRIVER=sqlite explicitly to opt in.
$APP_TIMEZONE = getenv('APP_TIMEZONE') ?: 'Asia/Manila';
date_default_timezone_set($APP_TIMEZONE);

$DATABASE_DRIVER = strtolower(getenv('DATABASE_DRIVER') ?: 'mysql');
if (!in_array($DATABASE_DRIVER, ['mysql', 'sqlite'], true)) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'error'=>'Unsupported DATABASE_DRIVER']);
  exit;
}
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'airbound_app';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$SQLITE_PATH = getenv('SQLITE_PATH') ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'airbound.sqlite';

$pdo = null;
$lastException = null;

if ($DATABASE_DRIVER === 'sqlite') {
  try {
    // Do not silently create a database during normal application requests.
    if (!is_file($SQLITE_PATH)) {
      throw new RuntimeException('SQLite database not found. Run tools/migrate_mysql_to_sqlite.php --run first.');
    }
    $pdo = new PDO('sqlite:' . $SQLITE_PATH, null, null, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $DB_NAME = basename($SQLITE_PATH);
  } catch (Exception $e) {
    $lastException = $e;
  }
} else {
  $attemptedDbNames = array_values(array_unique([$DB_NAME, 'airbound_app', 'airbound', 'iotairbound']));
  foreach ($attemptedDbNames as $candidateDbName) {
    try {
      $pdo = new PDO("mysql:host=$DB_HOST;dbname=$candidateDbName;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
      $DB_NAME = $candidateDbName;
      break;
    } catch (Exception $e) {
      $lastException = $e;
    }
  }
}

if (!$pdo && $DATABASE_DRIVER === 'mysql') {
  try {
    $adminPdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
  } catch (Exception $e) {
    $lastException = $e;
  }
}

if (!$pdo) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'DB connection failed', 'details'=>($lastException ? $lastException->getMessage() : null)]);
  exit;
}

function ensure_project_schema(PDO $pdo): void {
  global $DATABASE_DRIVER;
  if ($DATABASE_DRIVER === 'sqlite') {
    // The migration tool creates the SQLite schema before the application can open it.
    return;
  }
  $pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

  $pdo->exec('CREATE TABLE IF NOT EXISTS devices (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    lat DECIMAL(10,6) DEFAULT NULL,
    lng DECIMAL(10,6) DEFAULT NULL,
    last_seen INT UNSIGNED DEFAULT NULL,
    status ENUM("online","offline") DEFAULT "offline",
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $pdo->exec('CREATE TABLE IF NOT EXISTS readings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id INT UNSIGNED NOT NULL,
    pm25 DECIMAL(8,3) DEFAULT NULL,
    pm10 DECIMAL(8,3) DEFAULT NULL,
    co DECIMAL(10,4) DEFAULT NULL,
    no2 DECIMAL(8,3) DEFAULT NULL,
    raw JSON DEFAULT NULL,
    created_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    INDEX (device_id),
    CONSTRAINT fk_readings_device FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $pdo->exec('CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT UNSIGNED NOT NULL,
    reading_id BIGINT UNSIGNED DEFAULT NULL,
    pollutant VARCHAR(16) DEFAULT NULL,
    value DOUBLE DEFAULT NULL,
    unit VARCHAR(16) DEFAULT NULL,
    level VARCHAR(32) NOT NULL,
    icon VARCHAR(8) DEFAULT NULL,
    color VARCHAR(32) DEFAULT NULL,
    message TEXT,
    lat DOUBLE DEFAULT NULL,
    lng DOUBLE DEFAULT NULL,
    created_at INT NOT NULL,
    read_at INT DEFAULT NULL,
    resolved_at INT DEFAULT NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX (created_at),
    INDEX (device_id),
    INDEX (pollutant)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $pdo->exec('CREATE TABLE IF NOT EXISTS sensor_data (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mq2 DOUBLE DEFAULT NULL,
    mq135 DOUBLE DEFAULT NULL,
    dust DOUBLE DEFAULT NULL,
    created_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $pdo->exec('CREATE TABLE IF NOT EXISTS predictions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    predicted_aqi VARCHAR(120) DEFAULT NULL,
    confidence DOUBLE DEFAULT NULL,
    prediction_time VARCHAR(64) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  foreach (['mq2', 'mq135'] as $column) {
    try {
      $pdo->exec("ALTER TABLE sensor_data ADD COLUMN $column DOUBLE DEFAULT NULL");
    } catch (Exception $e) {
    }
  }
}

ensure_project_schema($pdo);

function database_is_sqlite(): bool {
  global $DATABASE_DRIVER;
  return $DATABASE_DRIVER === 'sqlite';
}

function database_datetime_from_unix_sql(): string {
  return database_is_sqlite() ? "datetime(?, 'unixepoch')" : 'FROM_UNIXTIME(?)';
}

function send_json($arr){
  header('Content-Type: application/json');
  echo json_encode($arr);
  exit;
}

// Alert thresholds (server-side canonical thresholds)
$ALERT_THRESHOLDS = [
  'pm25' => 55,   // µg/m³ — Unhealthy threshold (server-side)
  'pm10' => 150,  // µg/m³
  'co'   => 9,    // ppm
  'no2'  => 100,  // ppb
  'mq135' => 2000, // CO (mq135) unhealthy threshold
  'dust'  => 2500, // dust sensor unhealthy threshold (µg/m³ equivalent)
];

// Human-friendly labels and units for pollutants
$POLLUTANT_LABELS = [ 'pm25' => 'PM2.5', 'pm10' => 'PM10', 'co' => 'CO', 'no2' => 'NO₂', 'mq135' => 'CO', 'dust' => 'NO₂' ];
$POLLUTANT_UNITS  = [ 'pm25' => 'µg/m³', 'pm10' => 'µg/m³', 'co' => 'ppm', 'no2' => 'ppb', 'mq135' => '', 'dust' => 'µg/m³' ];
