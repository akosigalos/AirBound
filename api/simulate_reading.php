<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

session_start();
// Allow local development POSTs from localhost without a session so developers
// can run the simulator from tools or from other local pages without logging in.
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalRequest = in_array($remoteAddr, ['127.0.0.1', '::1', 'localhost']);
if(!isset($_SESSION['user_id']) && !$isLocalRequest){ http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if(!is_array($input)) $input = [];

function randFloat($min, $max, $precision = 1){
  $value = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
  return round($value, $precision);
}

function sanitizeReadingValue($input, $key, $min, $max, $fallback){
  if(!array_key_exists($key, $input)) return $fallback;
  if($input[$key] === null || $input[$key] === '') return $fallback;
  if(!is_numeric($input[$key])) return $fallback;
  $value = floatval($input[$key]);
  if($value < $min || $value > $max) return $fallback;
  return round($value, 1);
}

function timeBasedDrift($now, $deviceId, $offset, $span, $precision = 1){
  $phase = (($now + ($deviceId * 13) + $offset) % $span) / $span;
  $wave = sin($phase * 2 * M_PI);
  return round($wave * 6.5, $precision);
}

function ensureSimulationSchema(PDO $pdo){
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
    INDEX (device_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

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

try{
  ensureSimulationSchema($pdo);

  $deviceId = isset($input['device_id']) && is_numeric($input['device_id']) ? intval($input['device_id']) : 0;
  $device = null;

  if($deviceId){
    $stmt = $pdo->prepare('SELECT id, name, lat, lng FROM devices WHERE id = ? LIMIT 1');
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if(!$device){
    $stmt = $pdo->query("SELECT id, name, lat, lng FROM devices WHERE name = 'Demo Sensor' ORDER BY id ASC LIMIT 1");
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if(!$device){
    $now = time();
    $apiKey = bin2hex(random_bytes(16));
    $lat = 7.3003;
    $lng = 125.6804;
    $ins = $pdo->prepare('INSERT INTO devices (name, api_key, lat, lng, last_seen, status) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute(['Demo Sensor', $apiKey, $lat, $lng, $now, 'online']);
    $deviceId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT id, name, lat, lng FROM devices WHERE id = ? LIMIT 1');
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if(!$device){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'No device available for simulation']);
    exit;
  }

  $deviceId = (int)$device['id'];
  $now = time();

  $pm25 = sanitizeReadingValue($input, 'pm25', 0, 2000, randFloat(18, 180, 1));
  $pm10 = sanitizeReadingValue($input, 'pm10', 0, 2000, randFloat(25, 260, 1));
  $mq135 = sanitizeReadingValue($input, 'mq135', 0, 5000, (mt_rand(1, 100) <= 60) ? randFloat(55, 230, 1) : randFloat(180, 980, 1));
  $dust = sanitizeReadingValue($input, 'dust', 0, 5000, (mt_rand(1, 100) <= 60) ? randFloat(20, 145, 1) : randFloat(120, 1800, 1));

  $pm25 = round($pm25 + timeBasedDrift($now, $deviceId, 0, 18, 1), 1);
  $pm10 = round($pm10 + timeBasedDrift($now, $deviceId, 7, 24, 1), 1);
  $mq135 = round($mq135 + timeBasedDrift($now, $deviceId, 11, 20, 1), 1);
  $dust = round($dust + timeBasedDrift($now, $deviceId, 17, 28, 1), 1);

  $mq135 = max(0, $mq135);
  $dust = max(0, $dust);

  if($pm25 <= $ALERT_THRESHOLDS['pm25'] && $pm10 <= $ALERT_THRESHOLDS['pm10']){
    $pm25 = randFloat(60, 180, 1);
  }

  $co = null;
  $no2 = null;

  $readingPayload = [
    'source' => 'simulation',
    'device_id' => $deviceId,
    'generated_at' => $now,
    'pm25' => $pm25,
    'pm10' => $pm10,
    'mq135' => $mq135,
    'dust' => $dust,
    'co' => $co,
    'no2' => $no2,
  ];

  $ins = $pdo->prepare('INSERT INTO readings (device_id, pm25, pm10, co, no2, raw, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $ins->execute([$deviceId, $pm25, $pm10, $co, $no2, json_encode($readingPayload), $now]);
  $readingId = (int)$pdo->lastInsertId();

  $lat = isset($device['lat']) && is_numeric($device['lat']) ? floatval($device['lat']) : null;
  $lng = isset($device['lng']) && is_numeric($device['lng']) ? floatval($device['lng']) : null;
  $upd = $pdo->prepare('UPDATE devices SET last_seen = ?, status = "online", lat = COALESCE(?, lat), lng = COALESCE(?, lng) WHERE id = ?');
  $upd->execute([$now, $lat, $lng, $deviceId]);

  try{
    $insSensor = $pdo->prepare('INSERT INTO sensor_data (mq135, dust, created_at) VALUES (?, ?, ?)');
    $insSensor->execute([$mq135, $dust, $now]);
  }catch(Exception $e){
    // keep simulation flow alive even if the legacy dashboard feed fails
  }

  $classify = function($value){
    if($value === null) return null;
    if($value <= 12) return ['level'=>'good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
    if($value <= 35) return ['level'=>'fair','label'=>'Fair','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
    if($value <= 55) return ['level'=>'unhealthy','label'=>'Unhealthy','icon'=>'😷','color'=>'#f97316','severity'=>2];
    if($value <= 150) return ['level'=>'very_unhealthy','label'=>'Very Unhealthy','icon'=>'🤒','color'=>'#ef4444','severity'=>3];
    return ['level'=>'emergency','label'=>'Emergency','icon'=>'🆘','color'=>'#6b021d','severity'=>4];
  };

  $alerts = [];
  $pollutants = ['pm25' => $pm25, 'pm10' => $pm10, 'mq135' => $mq135, 'dust' => $dust];
  foreach($pollutants as $key => $value){
    $threshold = $ALERT_THRESHOLDS[$key] ?? null;
    if($threshold === null || $value === null || $value <= $threshold) continue;

    if($key === 'pm25'){
      $classification = $classify($value);
      $level = $classification['level'];
      $icon = $classification['icon'];
      $color = $classification['color'];
    }elseif($key === 'mq135'){
      $level = 'unhealthy'; $icon = '😷'; $color = '#f97316';
    }elseif($key === 'dust'){
      $level = 'very_unhealthy'; $icon = '🤧'; $color = '#ef4444';
    }else{
      $level = 'unhealthy'; $icon = '⚠️'; $color = '#f97316';
    }

    $label = $POLLUTANT_LABELS[$key] ?? strtoupper($key);
    $unit = $POLLUTANT_UNITS[$key] ?? '';
    $message = sprintf('%s %.1f %s — exceeds %s %s', $label, $value, $unit, $threshold, $unit);

    try{
      $insAlert = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, pollutant, value, unit, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $insAlert->execute([$deviceId, $readingId, $key, $value, $unit, $level, $icon, $color, $message, $lat, $lng, $now]);
      $alerts[] = ['pollutant' => $key, 'message' => $message];
    }catch(Exception $e){
      try{
        $insAlert = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insAlert->execute([$deviceId, $readingId, $level, $icon, $color, $message, $lat, $lng, $now]);
        $alerts[] = ['pollutant' => $key, 'message' => $message];
      }catch(Exception $e2){
        // keep ingestion flow alive even if alerts fail
      }
    }
  }

  echo json_encode([
    'success' => true,
    'device' => ['id' => $deviceId, 'name' => $device['name']],
    'reading' => [
      'id' => $readingId,
      'pm25' => $pm25,
      'pm10' => $pm10,
      'mq135' => $mq135,
      'dust' => $dust,
      'created_at' => $now,
    ],
    'alerts' => $alerts,
  ]);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Server error']);
}