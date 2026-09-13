<?php
// Smoke test script: creates a test device, inserts a reading, and creates an alert if needed.
// Run from project root: php tools\\smoke_test.php

require_once __DIR__ . '/../api/config.php';

try{
  $now = time();
  $name = 'smoke-test-' . $now;
  $api_key = bin2hex(random_bytes(16));
  $lat = -6.2000;
  $lng = 106.8167;

  $ins = $pdo->prepare('INSERT INTO devices (name, api_key, lat, lng, last_seen, status) VALUES (?, ?, ?, ?, ?, ?)');
  $ins->execute([$name, $api_key, $lat, $lng, $now, 'online']);
  $device_id = $pdo->lastInsertId();

  // sample reading to trigger Unhealthy+ alert
  $pm25 = 120.0; // high PM2.5
  $pm10 = 200.0;
  $co = 0.4;
  $no2 = 50.0;
  $raw = json_encode(['test'=>true]);

  $insR = $pdo->prepare('INSERT INTO readings (device_id, pm25, pm10, co, no2, raw, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $insR->execute([$device_id, $pm25, $pm10, $co, $no2, $raw, $now]);
  $reading_id = $pdo->lastInsertId();

  // classification (same logic as ingest.php)
  $classify = function($pm25){
    if($pm25 === null) return null;
    if($pm25 <= 12) return ['level'=>'good','label'=>'Good','icon'=>'😊','color'=>'#10b981','severity'=>0];
    if($pm25 <= 35) return ['level'=>'fair','label'=>'Fair','icon'=>'🙂','color'=>'#f59e0b','severity'=>1];
    if($pm25 <= 55) return ['level'=>'unhealthy','label'=>'Unhealthy','icon'=>'😷','color'=>'#f97316','severity'=>2];
    if($pm25 <= 150) return ['level'=>'very_unhealthy','label'=>'Very Unhealthy','icon'=>'🤒','color'=>'#ef4444','severity'=>3];
    return ['level'=>'emergency','label'=>'Emergency','icon'=>'🆘','color'=>'#6b021d','severity'=>4];
  };

  $classification = $classify($pm25);
  $alert_id = null;
  if($classification && $classification['severity'] >= 2){
    $msg = sprintf('PM2.5 %.1f µg/m³ — %s', $pm25, $classification['label']);
    $insA = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $insA->execute([$device_id, $reading_id, $classification['level'], $classification['icon'], $classification['color'], $msg, $lat, $lng, $now]);
    $alert_id = $pdo->lastInsertId();
  }

  echo "Created device: id={$device_id}, api_key={$api_key}\n";
  echo "Inserted reading id={$reading_id} (pm25={$pm25})\n";
  if($alert_id){ echo "Created alert id={$alert_id}\n"; } else { echo "No alert created (below threshold)\n"; }
  echo "Open Monitoring and Dashboard pages to verify live updates.\n";

}catch(Exception $e){ echo 'Error: '.$e->getMessage()."\n"; }
