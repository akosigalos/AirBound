<?php
// Server-Sent Events stream for new readings
require_once __DIR__ . '/config.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Access-Control-Allow-Origin: *');
set_time_limit(0);
ignore_user_abort(true);

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
}catch(Exception $e){}

$lastId = isset($_GET['lastId']) && is_numeric($_GET['lastId']) ? intval($_GET['lastId']) : null;
if($lastId === null){
  $stmt = $pdo->query('SELECT MAX(id) AS mx FROM readings');
  $r = $stmt->fetch();
  $lastId = $r && $r['mx'] ? intval($r['mx']) : 0;
}

function send_event($name, $data){
  echo "event: {$name}\n";
  $lines = explode("\n", rtrim($data, "\n"));
  foreach($lines as $line){ echo 'data: '.$line."\n"; }
  echo "\n";
  @ob_flush(); @flush();
}

try{
  $start = time();
  $timeout = 300; // 5 minutes
  while(true){
    $stmt = $pdo->prepare('SELECT r.id, r.device_id, r.pm25, r.pm10, r.co, r.no2, r.created_at, d.name AS device_name, d.lat, d.lng FROM readings r LEFT JOIN devices d ON r.device_id = d.id WHERE r.id > ? ORDER BY r.id ASC');
    $stmt->execute([$lastId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($rows){
      foreach($rows as $row){
        $row['pm25'] = $row['pm25'] !== null ? floatval($row['pm25']) : null;
        $row['pm10'] = $row['pm10'] !== null ? floatval($row['pm10']) : null;
        $row['co'] = $row['co'] !== null ? floatval($row['co']) : null;
        $row['no2'] = $row['no2'] !== null ? floatval($row['no2']) : null;
        $row['created_at'] = intval($row['created_at']);
        $row['lat'] = $row['lat'] !== null ? floatval($row['lat']) : null;
        $row['lng'] = $row['lng'] !== null ? floatval($row['lng']) : null;
        send_event('reading', json_encode($row));
        $lastId = intval($row['id']);
      }
    }
    // keepalive
    send_event('keepalive', json_encode(['ok'=>true]));
    if((time() - $start) > $timeout) break;
    sleep(1);
    if(connection_aborted()) break;
  }
}catch(Exception $e){ }
