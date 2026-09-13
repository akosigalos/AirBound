<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
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

  if($method === 'GET'){
    $start = isset($_GET['start']) && is_numeric($_GET['start']) ? intval($_GET['start']) : null;
    $end = isset($_GET['end']) && is_numeric($_GET['end']) ? intval($_GET['end']) : null;
    $device_id = isset($_GET['device_id']) && is_numeric($_GET['device_id']) ? intval($_GET['device_id']) : null;
    $level = isset($_GET['level']) ? trim($_GET['level']) : null;
    $unread = isset($_GET['unread']) ? boolval($_GET['unread']) : false;
    $limit = 10;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1,intval($_GET['page'])) : 1;

    $where = [];
    $params = [];
    if($start) { $where[] = 'a.created_at >= ?'; $params[] = $start; }
    if($end) { $where[] = 'a.created_at <= ?'; $params[] = $end; }
    if($device_id){ $where[] = 'a.device_id = ?'; $params[] = $device_id; }
    if($level){ $where[] = 'a.level = ?'; $params[] = $level; }
    if($unread){ $where[] = 'a.read_at IS NULL'; }
    $where[] = "a.pollutant IN ('mq135','dust')";

    $offset = ($page - 1) * $limit;
    $sql = 'SELECT a.id, a.device_id, d.name AS device_name, a.reading_id, a.pollutant, a.value, a.unit, a.level, a.icon, a.color, a.message, a.lat, a.lng, a.created_at, a.read_at, a.resolved_at FROM alerts a LEFT JOIN devices d ON a.device_id = d.id';
    if($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY a.id DESC, a.created_at DESC LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['alerts'=>$rows, 'page'=>$page, 'limit'=>$limit]);
    exit;
  }

  // write actions require authenticated session
  session_start();
  if(!isset($_SESSION['user_id'])){ http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?? [];

  if($method === 'POST'){
    $action = $input['action'] ?? '';

    // Create new alert (admin UI can POST here; requires authenticated session)
    if($action === 'create'){
      $device_id = isset($input['device_id']) ? intval($input['device_id']) : 0;
      if(!$device_id){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing device_id']); exit; }
      $reading_id = isset($input['reading_id']) && is_numeric($input['reading_id']) ? intval($input['reading_id']) : null;
      $pollutant = isset($input['pollutant']) ? trim($input['pollutant']) : null;
      $value = isset($input['value']) && is_numeric($input['value']) ? floatval($input['value']) : null;
      $unit = isset($input['unit']) ? trim($input['unit']) : null;
      $level = isset($input['level']) ? trim($input['level']) : 'unhealthy';
      $icon = isset($input['icon']) ? trim($input['icon']) : null;
      $color = isset($input['color']) ? trim($input['color']) : null;
      $message = isset($input['message']) ? trim($input['message']) : null;
      $lat = isset($input['lat']) && is_numeric($input['lat']) ? floatval($input['lat']) : null;
      $lng = isset($input['lng']) && is_numeric($input['lng']) ? floatval($input['lng']) : null;

      // avoid duplicate alerts for the same reading + pollutant
      if($reading_id){
        $chk = $pdo->prepare('SELECT id FROM alerts WHERE reading_id = ? AND pollutant = ? LIMIT 1');
        $chk->execute([$reading_id, $pollutant]);
        $exists = $chk->fetch();
        if($exists){ echo json_encode(['success'=>true,'id'=>intval($exists['id']),'note'=>'exists']); exit; }
      }

      $now = time();
      $stmt = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, pollutant, value, unit, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([$device_id, $reading_id, $pollutant, $value, $unit, $level, $icon, $color, $message, $lat, $lng, $now]);
      $insertId = (int)$pdo->lastInsertId();
      echo json_encode(['success'=>true,'id'=>$insertId]);
      exit;
    }

    $id = isset($input['id']) ? intval($input['id']) : 0;
    if(!$id){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
    if($action === 'mark_read'){
      $now = time();
      $stmt = $pdo->prepare('UPDATE alerts SET read_at = ? WHERE id = ?');
      $stmt->execute([$now, $id]);
      echo json_encode(['success'=>true]); exit;
    }
    if($action === 'resolve'){
      $now = time();
      $stmt = $pdo->prepare('UPDATE alerts SET resolved_at = ? WHERE id = ?');
      $stmt->execute([$now, $id]);
      echo json_encode(['success'=>true]); exit;
    }
    if($action === 'delete'){
      $stmt = $pdo->prepare('DELETE FROM alerts WHERE id = ?');
      $stmt->execute([$id]);
      echo json_encode(['success'=>true]); exit;
    }
    http_response_code(400); echo json_encode(['success'=>false,'error'=>'Unknown action']); exit;
  }

  http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']);
}catch(Exception $e){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'Server error']); }
