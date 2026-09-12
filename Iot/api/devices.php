<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
try{
  if($method === 'GET'){
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

    $sql = 'SELECT d.id, d.name, d.lat, d.lng, d.last_seen AS last_updated, d.status, d.api_key
      FROM devices d ORDER BY d.id';
    $stmt = $pdo->query($sql);
    $devices = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    if(empty($devices)){
      try{
        $legacy = new PDO('mysql:host=127.0.0.1;dbname=airbound;charset=utf8mb4', 'root', '', [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $legacy->exec('CREATE TABLE IF NOT EXISTS devices (
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

        $legacyStmt = $legacy->query('SELECT id, name, lat, lng, last_seen AS last_updated, status, api_key FROM devices ORDER BY id');
        $legacyDevices = $legacyStmt ? $legacyStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if(!empty($legacyDevices)){
          $devices = $legacyDevices;
        }
      }catch(Exception $e){
        // keep current database result if legacy database is unavailable
      }
    }

    // normalize types
    foreach($devices as &$d){
      $d['last_reading_at'] = null;
    }
    echo json_encode(['devices'=>$devices]);
    exit;
  }

  // write actions require an authenticated session
  session_start();
  if(!isset($_SESSION['user_id'])){ http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); exit; }

  $raw = file_get_contents('php://input');
  $input = json_decode($raw, true) ?? [];

  if($method === 'POST'){
    // create device
    $name = trim($input['name'] ?? '');
    $statusIn = strtolower(trim($input['status'] ?? 'inactive'));
    $status = ($statusIn === 'online' || $statusIn === 'active') ? 'online' : 'offline';
    $lat = isset($input['lat']) && is_numeric($input['lat']) ? floatval($input['lat']) : null;
    $lng = isset($input['lng']) && is_numeric($input['lng']) ? floatval($input['lng']) : null;
    if($name === ''){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Name required']); exit; }
    $api_key = bin2hex(random_bytes(16));
    $now = time();
    $ins = $pdo->prepare('INSERT INTO devices (name, api_key, lat, lng, last_seen, status) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$name, $api_key, $lat, $lng, $now, $status]);
    $id = $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT id, name, lat, lng, last_seen AS last_updated, status, api_key FROM devices WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $device = $stmt->fetch();
    echo json_encode(['success'=>true,'device'=>$device]);
    exit;
  }

  if($method === 'PUT' || $method === 'PATCH'){
    $id = isset($input['id']) ? intval($input['id']) : 0;
    if(!$id){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
    $fields = [];
    $params = [];
    if(array_key_exists('name',$input)){ $fields[]='name = ?'; $params[] = trim($input['name']); }
    if(array_key_exists('status',$input)){ $statusIn = strtolower(trim($input['status'])); $status = ($statusIn === 'online' || $statusIn === 'active') ? 'online' : 'offline'; $fields[]='status = ?'; $params[] = $status; }
    if(array_key_exists('lat',$input)){ $fields[]='lat = ?'; $params[] = is_numeric($input['lat']) ? floatval($input['lat']) : null; }
    if(array_key_exists('lng',$input)){ $fields[]='lng = ?'; $params[] = is_numeric($input['lng']) ? floatval($input['lng']) : null; }
    if(array_key_exists('last_seen',$input)){ $fields[]='last_seen = ?'; $params[] = intval($input['last_seen']); }
    if(empty($fields)){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'No fields to update']); exit; }
    $params[] = $id;
    $sql = 'UPDATE devices SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $stmt = $pdo->prepare('SELECT id, name, lat, lng, last_seen AS last_updated, status, api_key FROM devices WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $device = $stmt->fetch();
    echo json_encode(['success'=>true,'device'=>$device]);
    exit;
  }

  if($method === 'DELETE'){
    $id = isset($input['id']) ? intval($input['id']) : 0;
    if(!$id){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Missing id']); exit; }
    $stmt = $pdo->prepare('DELETE FROM devices WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(['success'=>false,'error'=>'Method not allowed']);

}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Server error']);
}
