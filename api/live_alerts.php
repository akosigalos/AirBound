<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

session_start();
if(!isset($_SESSION['user_id'])){ http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

try{
  if (!database_is_sqlite()) {
  $pdo->exec('CREATE TABLE IF NOT EXISTS sensor_data (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mq135 DOUBLE DEFAULT NULL,
    dust DOUBLE DEFAULT NULL,
    created_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  $pdo->exec('CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NOT NULL,
    reading_id BIGINT DEFAULT NULL,
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
    INDEX (created_at),
    INDEX (device_id),
    INDEX (pollutant)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  }

  $lastSensorId = isset($_GET['last_sensor_id']) && is_numeric($_GET['last_sensor_id']) ? intval($_GET['last_sensor_id']) : 0;
  $limit = 10;
  $seed = isset($_GET['seed']) && $_GET['seed'] === '1';

  $thresholds = $ALERT_THRESHOLDS;
  $labels = $POLLUTANT_LABELS;
  $units = $POLLUTANT_UNITS;

  $sensorRows = [];
  $paused = false;
  if(!$seed){
    $sensorStmt = $pdo->prepare('SELECT id, mq135, dust, created_at FROM sensor_data WHERE id > ? ORDER BY id ASC LIMIT 100');
    $sensorStmt->execute([$lastSensorId]);
    $sensorRows = $sensorStmt->fetchAll(PDO::FETCH_ASSOC);
    $paused = count($sensorRows) === 0;
  }

  $deviceStmt = $pdo->query("SELECT id, name FROM devices WHERE name = 'Demo Sensor' ORDER BY id ASC LIMIT 1");
  $device = $deviceStmt ? $deviceStmt->fetch(PDO::FETCH_ASSOC) : null;
  if(!$device){
    echo json_encode([
      'success' => true,
      'synced_until_sensor_id' => $lastSensorId,
      'inserted' => 0,
      'alerts' => [],
      'note' => 'Demo Sensor not found',
    ]);
    exit;
  }
  $deviceId = intval($device['id']);

  $checkExisting = $pdo->prepare('SELECT id FROM alerts WHERE reading_id = ? AND pollutant = ? LIMIT 1');
  $insertAlert = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, pollutant, value, unit, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

  $classify = function($key, $value){
    if($key === 'mq135'){
      if($value <= 1000) return ['level' => 'good', 'icon' => '😊', 'color' => '#10b981', 'messageLevel' => 'Good'];
      if($value <= 2000) return ['level' => 'fair', 'icon' => '🙂', 'color' => '#f59e0b', 'messageLevel' => 'Fair'];
      return ['level' => 'unhealthy', 'icon' => '😷', 'color' => '#f97316', 'messageLevel' => 'Unhealthy'];
    }

    if($key === 'dust'){
      if($value <= 1500) return ['level' => 'good', 'icon' => '😊', 'color' => '#10b981', 'messageLevel' => 'Good'];
      if($value <= 2500) return ['level' => 'fair', 'icon' => '🙂', 'color' => '#f59e0b', 'messageLevel' => 'Fair'];
      return ['level' => 'unhealthy', 'icon' => '🤧', 'color' => '#ef4444', 'messageLevel' => 'Unhealthy'];
    }

    return ['level' => 'good', 'icon' => '😊', 'color' => '#10b981', 'messageLevel' => 'Good'];
  };

  $inserted = 0;
  $newAlerts = [];
  $syncedUntil = $lastSensorId;

  if($seed){
    $seedMaxStmt = $pdo->query('SELECT COALESCE(MAX(id), 0) AS mx FROM sensor_data');
    $seedMax = $seedMaxStmt ? $seedMaxStmt->fetch(PDO::FETCH_ASSOC) : ['mx' => 0];
    $syncedUntil = intval($seedMax['mx'] ?? 0);
    $seedHistory = $pdo->prepare('SELECT a.id, a.device_id, d.name AS device_name, a.reading_id, a.pollutant, a.value, a.unit, a.level, a.icon, a.color, a.message, a.lat, a.lng, a.created_at, a.read_at, a.resolved_at FROM alerts a LEFT JOIN devices d ON a.device_id = d.id WHERE d.name = ? AND a.pollutant IN (\'mq135\',\'dust\') ORDER BY a.created_at DESC, a.id DESC LIMIT ' . intval($limit));
    $seedHistory->execute(['Demo Sensor']);
    $newAlerts = $seedHistory->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
      'success' => true,
      'synced_until_sensor_id' => $syncedUntil,
      'inserted' => 0,
      'alerts' => $newAlerts,
      'paused' => count($newAlerts) === 0,
      'seed' => true,
    ]);
    exit;
  }

  foreach($sensorRows as $row){
    $sensorId = intval($row['id']);
    $ts = intval($row['created_at']);
    $syncedUntil = $sensorId;

    foreach(['mq135', 'dust'] as $key){
      if(!isset($row[$key]) || !is_numeric($row[$key])) continue;
      $value = floatval($row[$key]);
      $classification = $classify($key, $value);

      $checkExisting->execute([$sensorId, $key]);
      if($checkExisting->fetch()) continue;

      $message = sprintf('%s %.1f %s — %s', $labels[$key] ?? strtoupper($key), $value, $units[$key] ?? '', $classification['messageLevel']);
      $level = $classification['level'];
      $icon = $classification['icon'];
      $color = $classification['color'];

      $insertAlert->execute([$deviceId, $sensorId, $key, $value, $units[$key] ?? '', $level, $icon, $color, $message, null, null, $ts]);
      $alertId = (int)$pdo->lastInsertId();
      $newAlerts[] = [
        'id' => $alertId,
        'device_id' => $deviceId,
        'device_name' => $device['name'],
        'reading_id' => $sensorId,
        'pollutant' => $key,
        'value' => $value,
        'unit' => $units[$key] ?? '',
        'level' => $level,
        'icon' => $icon,
        'color' => $color,
        'message' => $message,
        'lat' => null,
        'lng' => null,
        'created_at' => $ts,
        'read_at' => null,
        'resolved_at' => null,
      ];
      $inserted++;
    }
  }

  echo json_encode([
    'success' => true,
    'synced_until_sensor_id' => $syncedUntil,
    'inserted' => $inserted,
    'alerts' => $newAlerts,
    'paused' => $paused,
  ]);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Server error']);
}
