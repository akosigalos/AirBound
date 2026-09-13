<?php
// SSE stream for alerts
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
}catch(Exception $e){}

$lastId = isset($_GET['lastId']) && is_numeric($_GET['lastId']) ? intval($_GET['lastId']) : null;
if($lastId === null){
  $stmt = $pdo->query('SELECT MAX(id) AS mx FROM alerts');
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
  $timeout = 300;
  while(true){
    $stmt = $pdo->prepare('SELECT a.id, a.device_id, d.name AS device_name, a.reading_id, a.pollutant, a.value, a.unit, a.level, a.icon, a.color, a.message, a.lat, a.lng, a.created_at FROM alerts a LEFT JOIN devices d ON a.device_id = d.id WHERE a.id > ? ORDER BY a.id ASC');
    $stmt->execute([$lastId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if($rows){
      foreach($rows as $row){
        $row['created_at'] = intval($row['created_at']);
        send_event('alert', json_encode($row));
        $lastId = intval($row['id']);
      }
    }
    send_event('keepalive', json_encode(['ok'=>true]));
    if((time() - $start) > $timeout) break;
    sleep(1);
    if(connection_aborted()) break;
  }
}catch(Exception $e){ }

