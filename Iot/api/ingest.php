<?php
require_once __DIR__ . '/config.php';

// Ingest endpoint for devices to POST validated sensor readings.
// Authentication: device must provide its api_key via header X-API-KEY or in JSON body as api_key.
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if(!is_array($input)){
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>'Invalid JSON']);
  exit;
}

$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($input['api_key'] ?? '');
if(!$apiKey){ http_response_code(401); echo json_encode(['success'=>false,'error'=>'Missing API key']); exit; }

try{
  $stmt = $pdo->prepare('SELECT id FROM devices WHERE api_key = ? LIMIT 1');
  $stmt->execute([$apiKey]);
  $device = $stmt->fetch();
  if(!$device){ http_response_code(403); echo json_encode(['success'=>false,'error'=>'Invalid API key']); exit; }

  $device_id = (int)$device['id'];
  $now = time();

  // validation helper: return float or null
  $validNumber = function($v, $min, $max){
    if($v === null || $v === '') return null;
    if(!is_numeric($v)) return null;
    $f = floatval($v);
    if($f < $min || $f > $max) return null;
    return $f;
  };

  $pm25 = array_key_exists('pm25',$input) ? $validNumber($input['pm25'], 0, 2000) : null;
  $pm10 = array_key_exists('pm10',$input) ? $validNumber($input['pm10'], 0, 2000) : null;
  $co   = array_key_exists('co',$input)   ? $validNumber($input['co'], 0, 10000) : null; // ppm
  $no2  = array_key_exists('no2',$input)  ? $validNumber($input['no2'], 0, 2000) : null; // ppb
  $mq135 = array_key_exists('mq135',$input) ? $validNumber($input['mq135'], 0, 10000) : null;
  $dust  = array_key_exists('dust',$input)  ? $validNumber($input['dust'], 0, 10000) : null;

  $lat = array_key_exists('lat',$input) && is_numeric($input['lat']) ? floatval($input['lat']) : null;
  $lng = array_key_exists('lng',$input) && is_numeric($input['lng']) ? floatval($input['lng']) : null;

  // store reading (store only validated numeric values; invalid fields become NULL)
  $ins = $pdo->prepare('INSERT INTO readings (device_id, pm25, pm10, co, no2, raw, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $ins->execute([$device_id, $pm25, $pm10, $co, $no2, json_encode($input), $now]);
  $readingId = (int)$pdo->lastInsertId();

  // update device last seen and optionally lat/lng
  $upd = $pdo->prepare('UPDATE devices SET last_seen = ?, status = "online", lat = COALESCE(?, lat), lng = COALESCE(?, lng) WHERE id = ?');
  $upd->execute([$now, $lat, $lng, $device_id]);

  // Also persist a lightweight row for the prediction pipeline so
  // `api/predictions.php` (which reads `sensor_data`) sees recent device values.
  try{
    $pdo->exec('CREATE TABLE IF NOT EXISTS sensor_data (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT,
      mq2 DOUBLE DEFAULT NULL,
      mq135 DOUBLE DEFAULT NULL,
      dust DOUBLE DEFAULT NULL,
      created_at INT UNSIGNED NOT NULL DEFAULT 0,
      PRIMARY KEY (id),
      INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  }catch(Exception $e){ }

  try{ $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq2 DOUBLE DEFAULT NULL'); }catch(Exception $e){ }
  try{ $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq135 DOUBLE DEFAULT NULL'); }catch(Exception $e){ }

  try{
    // Insert only validated numeric values (keep schema consistent with other helpers)
    $insSensor = $pdo->prepare('INSERT INTO sensor_data (mq2, mq135, dust, created_at) VALUES (?, ?, ?, ?)');
    // Some devices send MQ-2 vs MQ-135; use mq135 when available for both columns for compatibility.
    $insSensor->execute([ $mq135 ?? null, $mq135 ?? null, $dust ?? null, $now ]);
  }catch(Exception $e){ /* don't block ingestion on sensor_data errors */ }

  // classify air quality for PM2.5 and PM10. Returns null if no valid value.
  $classifyAQ = function($pollutant, $v) {
    if($v === null) return null;
    // Use the project bands requested: Good, Fair, Unhealthy
    if($pollutant === 'pm25'){
      if($v <= 12) return ['level'=>'Good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
      if($v <= 35) return ['level'=>'Moderate','label'=>'Moderate','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
      if($v <= 55) return ['level'=>'Unhealthy','label'=>'Unhealthy','icon'=>'😷','color'=>'#f97316','severity'=>2];
      return ['level'=>'Hazardous','label'=>'Hazardous','icon'=>'🆘','color'=>'#6b021d','severity'=>3];
    }
    if($pollutant === 'pm10'){
      if($v <= 54) return ['level'=>'Good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
      if($v <= 154) return ['level'=>'Moderate','label'=>'Moderate','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
      if($v <= 254) return ['level'=>'Unhealthy','label'=>'Unhealthy','icon'=>'😷','color'=>'#f97316','severity'=>2];
      return ['level'=>'Hazardous','label'=>'Hazardous','icon'=>'🆘','color'=>'#6b021d','severity'=>3];
    }
    if($pollutant === 'mq135'){
      if($v <= 1000) return ['level'=>'Good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
      if($v <= 2000) return ['level'=>'Fair','label'=>'Fair','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
      return ['level'=>'Unhealthy','label'=>'Unhealthy','icon'=>'😷','color'=>'#f97316','severity'=>2];
    }
    if($pollutant === 'dust'){
      if($v <= 1500) return ['level'=>'Good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
      if($v <= 2500) return ['level'=>'Fair','label'=>'Fair','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
      return ['level'=>'Unhealthy','label'=>'Unhealthy','icon'=>'🤧','color'=>'#ef4444','severity'=>2];
    }
    return null;
  };

  // Create alerts for any pollutant that exceeds server-side thresholds.
  try{
    // mappings come from config.php: $ALERT_THRESHOLDS, $POLLUTANT_LABELS, $POLLUTANT_UNITS
    $pollutants = ['pm25' => $pm25, 'pm10' => $pm10, 'co' => $co, 'no2' => $no2, 'mq135' => $mq135, 'dust' => $dust];
    foreach($pollutants as $key => $val){
      if($val === null) continue; // no valid measurement -> skip
      $threshold = $ALERT_THRESHOLDS[$key] ?? null;
      if($threshold === null) continue;
      if($val > $threshold){
        // determine level/icon/color using classifier when available
        $classification = $classifyAQ($key, $val);
        if($classification){
          $level = $classification['level'];
          $icon = $classification['icon'];
          $color = $classification['color'];
        }else{
          // fallback for sensors without a specific classifier (mq135, dust)
          $level = 'Unhealthy';
          $icon = '⚠️';
          $color = '#f97316';
          // for mq135 and dust we can optionally make different defaults
          if($key === 'mq135') { $level = 'Unhealthy'; $icon='😷'; $color='#f97316'; }
          if($key === 'dust') { $level = 'Unhealthy'; $icon='🤧'; $color='#ef4444'; }
        }
        $label = $POLLUTANT_LABELS[$key] ?? strtoupper($key);
        $unit = $POLLUTANT_UNITS[$key] ?? '';
        $msg = sprintf('%s %.1f %s — exceeds %s %s', $label, $val, $unit, $threshold, $unit);

        // Avoid duplicate alerts for same reading+pollutant
        try{
          $chk = $pdo->prepare('SELECT id FROM alerts WHERE reading_id = ? AND pollutant = ? LIMIT 1');
          $chk->execute([$readingId, $key]);
          $exists = $chk->fetch();
          if($exists) continue;
        }catch(Exception $e){ /* ignore check errors and attempt insert */ }

        // Try insert using new structured alert columns (pollutant, value, unit).
        try{
          $insA = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, pollutant, value, unit, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
          $insA->execute([$device_id, $readingId, $key, $val, $unit, $level, $icon, $color, $msg, $lat, $lng, $now]);
        }catch(Exception $e){
          // Fallback for older schema: insert without pollutant/value/unit
          try{
            $insA2 = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insA2->execute([$device_id, $readingId, $level, $icon, $color, $msg, $lat, $lng, $now]);
          }catch(Exception $e2){
            // swallow to avoid blocking ingestion
          }
        }
      }
    }
  }catch(Exception $e){ /* don't block ingestion on alert errors */ }

  echo json_encode(['success'=>true]);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'Server error']);
}
