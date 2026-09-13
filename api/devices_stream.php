<?php
// Server-Sent Events stream for device list changes.
// Clients can connect to receive realtime device updates without polling.
require_once __DIR__ . '/config.php';
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
// allow browser connections from same origin; adjust CORS if needed
header('Access-Control-Allow-Origin: *');
set_time_limit(0);
ignore_user_abort(true);

$lastHash = $_GET['last'] ?? null;
$start = time();
$timeout = 300; // close stream after 5 minutes, client should reconnect

function send_event($name, $data){
  echo "event: {$name}\n";
  // ensure data is sent as valid JSON string lines
  $lines = explode("\n", rtrim($data, "\n"));
  foreach($lines as $line){ echo 'data: '.$line."\n"; }
  echo "\n";
  @ob_flush();
  @flush();
}

try{
  while(true){
    $stmt = $pdo->query('SELECT id, name, lat, lng, last_seen AS last_updated, status FROM devices ORDER BY id');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // normalize numeric values
    foreach($rows as &$r){
      $r['lat'] = $r['lat'] !== null ? (float)$r['lat'] : null;
      $r['lng'] = $r['lng'] !== null ? (float)$r['lng'] : null;
      $r['last_updated'] = $r['last_updated'] !== null ? (int)$r['last_updated'] : null;
    }
    $payload = json_encode(['devices'=>$rows]);
    $hash = md5($payload);
    if($hash !== $lastHash){
      send_event('devices', $payload);
      $lastHash = $hash;
    }

    if((time() - $start) > $timeout) break;
    // sleep briefly to avoid high CPU; keep stream responsive
    sleep(2);
    // allow client disconnects to be noticed
    if(connection_aborted()) break;
  }
  // send a short keepalive
  send_event('keepalive', json_encode(['ok'=>true]));
}catch(Exception $e){
  // nothing special
}

?>
