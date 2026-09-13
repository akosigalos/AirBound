<?php
require_once __DIR__ . '/config.php';

// Simple utility to create alerts from existing sensor_data rows for mq135 and dust.
// Usage (local): visit /api/generate_historical_alerts.php?confirm=1 or run via CLI.

header('Content-Type: application/json');

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = in_array($remote, ['127.0.0.1', '::1', 'localhost']);
if(php_sapi_name() !== 'cli' && !$isLocal && !isset($_GET['confirm'])){
  http_response_code(403);
  echo json_encode(['success'=>false,'error'=>'Forbidden - local only or require ?confirm=1']);
  exit;
}

try{
  $thresholds = $ALERT_THRESHOLDS;
  $labels = $POLLUTANT_LABELS;
  $units = $POLLUTANT_UNITS;

  $stmt = $pdo->query('SELECT id, mq135, dust, created_at FROM sensor_data ORDER BY created_at ASC');
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $inserted = 0; $skipped = 0;

  $chk = $pdo->prepare('SELECT id FROM alerts WHERE pollutant = ? AND created_at = ? LIMIT 1');
  $ins = $pdo->prepare('INSERT INTO alerts (device_id, reading_id, pollutant, value, unit, level, icon, color, message, lat, lng, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

  foreach($rows as $r){
    $ts = intval($r['created_at']);
    // mq135
    if(isset($r['mq135']) && is_numeric($r['mq135'])){
      $v = floatval($r['mq135']);
      $thr = $thresholds['mq135'] ?? null;
      if($thr !== null && $v > $thr){
        $chk->execute(['mq135', $ts]);
        if($chk->fetch()) { $skipped++; } else {
          $msg = sprintf('%s %.1f %s — exceeds %s %s', $labels['mq135'] ?? 'CO', $v, $units['mq135'] ?? '', $thr, $units['mq135'] ?? '');
          $ins->execute([0, null, 'mq135', $v, $units['mq135'] ?? '', 'unhealthy', '😷', '#f97316', $msg, null, null, $ts]);
          $inserted++;
        }
      }
    }
    // dust
    if(isset($r['dust']) && is_numeric($r['dust'])){
      $v = floatval($r['dust']);
      $thr = $thresholds['dust'] ?? null;
      if($thr !== null && $v > $thr){
        $chk->execute(['dust', $ts]);
        if($chk->fetch()) { $skipped++; } else {
          $msg = sprintf('%s %.1f %s — exceeds %s %s', $labels['dust'] ?? 'Dust', $v, $units['dust'] ?? '', $thr, $units['dust'] ?? '');
          $ins->execute([0, null, 'dust', $v, $units['dust'] ?? '', 'unhealthy', '🤧', '#ef4444', $msg, null, null, $ts]);
          $inserted++;
        }
      }
    }
  }

  echo json_encode(['success'=>true,'inserted'=>$inserted,'skipped'=>$skipped,'checked'=>count($rows)]);
}catch(Exception $e){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'Server error','detail'=> $e->getMessage()]); }
