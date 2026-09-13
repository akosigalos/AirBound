<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// GET params: start (unix), end (unix), device_id, limit
try{
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

  try{ $pdo->exec('ALTER TABLE readings ADD COLUMN co DECIMAL(10,4) DEFAULT NULL'); }catch(Exception $e){}
  try{ $pdo->exec('ALTER TABLE readings ADD COLUMN no2 DECIMAL(8,3) DEFAULT NULL'); }catch(Exception $e){}

  $now = time();
  $start = isset($_GET['start']) && is_numeric($_GET['start']) ? intval($_GET['start']) : ($now - 86400); // default last 24h
  $end = isset($_GET['end']) && is_numeric($_GET['end']) ? intval($_GET['end']) : $now;
  $device_id = isset($_GET['device_id']) && is_numeric($_GET['device_id']) ? intval($_GET['device_id']) : null;
  $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 1000;
  $sort = strtolower(trim($_GET['sort'] ?? 'asc'));
  $sortDir = $sort === 'desc' ? 'DESC' : 'ASC';

  $sql = 'SELECT r.id, r.device_id, r.pm25, r.pm10, r.co, r.no2, r.created_at, d.name AS device_name, d.lat, d.lng FROM readings r LEFT JOIN devices d ON r.device_id = d.id WHERE r.created_at BETWEEN ? AND ?';
  $params = [$start, $end];
  if($device_id){ $sql .= ' AND r.device_id = ?'; $params[] = $device_id; }
  $sql .= ' ORDER BY r.created_at ' . $sortDir . ' LIMIT ' . intval($limit);

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // normalize types
  foreach($rows as &$r){
    $r['pm25'] = $r['pm25'] !== null ? floatval($r['pm25']) : null;
    $r['pm10'] = $r['pm10'] !== null ? floatval($r['pm10']) : null;
    $r['co'] = $r['co'] !== null ? floatval($r['co']) : null;
    $r['no2'] = $r['no2'] !== null ? floatval($r['no2']) : null;
    $r['created_at'] = intval($r['created_at']);
    $r['lat'] = $r['lat'] !== null ? floatval($r['lat']) : null;
    $r['lng'] = $r['lng'] !== null ? floatval($r['lng']) : null;
  }

  echo json_encode(['readings'=>$rows]);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['error'=>'Server error']);
}
