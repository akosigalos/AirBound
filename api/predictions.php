<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

function normalize_prediction_label($value){
  $text = trim(strtolower((string)$value));
  if($text === '') return 'Unknown';
  if(is_numeric($text)){
    $score = floatval($text);
    if($score <= 50) return 'Good';
    if($score <= 100) return 'Moderate';
    if($score <= 150) return 'Unhealthy for Sensitive Groups';
    if($score <= 200) return 'Unhealthy';
    if($score <= 300) return 'Very Unhealthy';
    return 'Hazardous';
  }
  return ucwords($text);
}

function prediction_band($label){
  $key = strtolower((string)$label);
  if(strpos($key, 'good') !== false) return ['label' => 'Good', 'color' => '#10b981'];
  if(strpos($key, 'moderate') !== false) return ['label' => 'Moderate', 'color' => '#f59e0b'];
  if(strpos($key, 'sensitive') !== false) return ['label' => 'Unhealthy for Sensitive Groups', 'color' => '#f97316'];
  if(strpos($key, 'very') !== false) return ['label' => 'Very Unhealthy', 'color' => '#ef4444'];
  if(strpos($key, 'hazard') !== false) return ['label' => 'Hazardous', 'color' => '#7c2d12'];
  if(strpos($key, 'unhealthy') !== false) return ['label' => 'Unhealthy', 'color' => '#dc2626'];
  return ['label' => normalize_prediction_label($label), 'color' => '#64748b'];
}

function combined_score($mq135, $dust){
  // Normalize each pollutant relative to the configured unhealthy thresholds
  // so predictions align with the project's bands (config: $ALERT_THRESHOLDS).
  global $ALERT_THRESHOLDS;
  $values = [];
  if($mq135 !== null && $mq135 !== '' && is_numeric($mq135)){
    $maxMq = isset($ALERT_THRESHOLDS['mq135']) ? floatval($ALERT_THRESHOLDS['mq135']) : 2000.0;
    $scoreMq = floatval($mq135) / max(1.0, $maxMq);
    $values[] = min(1.0, $scoreMq);
  }
  if($dust !== null && $dust !== '' && is_numeric($dust)){
    $maxDust = isset($ALERT_THRESHOLDS['dust']) ? floatval($ALERT_THRESHOLDS['dust']) : 2500.0;
    $scoreDust = floatval($dust) / max(1.0, $maxDust);
    $values[] = min(1.0, $scoreDust);
  }
  if(!$values) return null;
  return array_sum($values) / count($values);
}

function score_to_aqi($score){
  if($score === null) return ['label' => 'Unknown', 'color' => '#64748b'];
  if($score <= 0.20) return ['label' => 'Good', 'color' => '#10b981'];
  if($score <= 0.40) return ['label' => 'Moderate', 'color' => '#f59e0b'];
  if($score <= 0.60) return ['label' => 'Unhealthy for Sensitive Groups', 'color' => '#f97316'];
  if($score <= 0.80) return ['label' => 'Unhealthy', 'color' => '#ef4444'];
  return ['label' => 'Very Unhealthy', 'color' => '#7c2d12'];
}

function clamp_score($value){
  return max(0, min(1, $value));
}

function forecast_label_from_score($score){
  $bucket = score_to_aqi($score);
  return $bucket['label'];
}

function current_cycle_window($seconds = 3600){
  $now = time();
  $start = floor($now / $seconds) * $seconds;
  $end = $start + ($seconds - 1);
  return [$start, $end];
}

function normalize_cycle_seconds($value){
  $seconds = is_numeric($value) ? intval($value) : 3600;
  return $seconds === 30 ? 30 : 3600;
}

try{
  $stored = null;
  try{
    $stmt = $pdo->query('SELECT id, predicted_aqi, confidence, prediction_time, created_at FROM predictions ORDER BY id DESC LIMIT 1');
    $stored = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
  }catch(Exception $e){
    $stored = null;
  }

  $historyStmt = $pdo->query('SELECT mq135, dust, created_at FROM sensor_data ORDER BY id DESC LIMIT 12');
  $history = $historyStmt ? array_reverse($historyStmt->fetchAll(PDO::FETCH_ASSOC)) : [];
  $scores = [];
  $latestSensorTs = null;
  foreach($history as $row){
    $score = combined_score($row['mq135'] ?? null, $row['dust'] ?? null);
    if($score !== null) $scores[] = $score;
    if(isset($row['created_at'])){
      if(is_numeric($row['created_at'])) $latestSensorTs = intval($row['created_at']);
      else {
        $ts = strtotime($row['created_at']); if($ts !== false) $latestSensorTs = $ts;
      }
    }
  }

  $latestScore = $scores ? $scores[count($scores) - 1] : null;
  $slope = null;
  if(count($scores) >= 2){
    $slope = ($scores[count($scores) - 1] - $scores[0]) / max(1, count($scores) - 1);
  }

  $cycleSeconds = normalize_cycle_seconds($_GET['cycle_seconds'] ?? $_GET['cycle'] ?? 3600);
  [$cycleStart, $cycleEnd] = current_cycle_window($cycleSeconds);
  $forecastTime = date('Y-m-d H:i:s', $cycleStart + $cycleSeconds);
  $savedPrediction = null;
  $predictionWasPersisted = false;

  $forecast = [];
  $horizons = [
    ['label' => '1h', 'factor' => 1.0],
    ['label' => '3h', 'factor' => 2.2],
    ['label' => '6h', 'factor' => 3.4],
  ];
  foreach($horizons as $horizon){
    $projected = $latestScore;
    if($projected !== null && $slope !== null){
      $projected = clamp_score($projected + ($slope * $horizon['factor']));
    }
    $bucket = score_to_aqi($projected);
    $forecast[] = [
      'horizon' => $horizon['label'],
      'label' => $bucket['label'],
      'color' => $bucket['color'],
      'score' => $projected,
    ];
  }

  $derivedConfidence = null;
  if($scores){
    $avg = array_sum($scores) / count($scores);
    $variance = 0.0;
    foreach($scores as $score){
      $variance += pow($score - $avg, 2);
    }
    $variance = $variance / count($scores);
    $derivedConfidence = round(max(45, min(96, 96 - ($variance * 180))));

    $targetScore = $forecast[0]['score'] ?? $latestScore;
    $targetLabel = forecast_label_from_score($targetScore);
    $predictionPayload = [
      'predicted_aqi' => $targetLabel,
      'confidence' => $derivedConfidence,
      'prediction_time' => $forecastTime,
    ];

    $existingPrediction = null;
    try{
      $check = $pdo->prepare('SELECT id, predicted_aqi, confidence, prediction_time, created_at FROM predictions WHERE created_at >= FROM_UNIXTIME(?) AND created_at <= FROM_UNIXTIME(?) ORDER BY id DESC LIMIT 1');
      $check->execute([$cycleStart, $cycleEnd]);
      $existingPrediction = $check->fetch(PDO::FETCH_ASSOC) ?: null;
    }catch(Exception $e){
      $existingPrediction = null;
    }

    try{
      if($existingPrediction){
        // If we have newer sensor data than the existing prediction's created_at,
        // overwrite the stored prediction so the UI reflects fresh readings immediately.
        $existingCreatedTs = null;
        if(isset($existingPrediction['created_at'])){
          if(is_numeric($existingPrediction['created_at'])) $existingCreatedTs = intval($existingPrediction['created_at']);
          else { $t = strtotime($existingPrediction['created_at']); if($t !== false) $existingCreatedTs = $t; }
        }

        if($latestSensorTs && $existingCreatedTs && $latestSensorTs > $existingCreatedTs){
          try{
            $update = $pdo->prepare('UPDATE predictions SET predicted_aqi = ?, confidence = ?, prediction_time = ?, created_at = FROM_UNIXTIME(?) WHERE id = ?');
            $update->execute([$predictionPayload['predicted_aqi'], $predictionPayload['confidence'], $predictionPayload['prediction_time'], $latestSensorTs, intval($existingPrediction['id'])]);
            $predictionWasPersisted = true;
            $savedPrediction = [
              'id' => intval($existingPrediction['id']),
              'predicted_aqi' => $predictionPayload['predicted_aqi'],
              'confidence' => $predictionPayload['confidence'],
              'prediction_time' => $predictionPayload['prediction_time'],
              'created_at' => date('Y-m-d H:i:s', $latestSensorTs),
            ];
          }catch(Exception $e){
            // If update fails, fall back to returning existing prediction
            $savedPrediction = [
              'id' => intval($existingPrediction['id']),
              'predicted_aqi' => $existingPrediction['predicted_aqi'] ?? $predictionPayload['predicted_aqi'],
              'confidence' => isset($existingPrediction['confidence']) && is_numeric($existingPrediction['confidence']) ? floatval($existingPrediction['confidence']) : $predictionPayload['confidence'],
              'prediction_time' => $existingPrediction['prediction_time'] ?? $predictionPayload['prediction_time'],
              'created_at' => $existingPrediction['created_at'] ?? null,
            ];
          }
        }else{
          $savedPrediction = [
            'id' => intval($existingPrediction['id']),
            'predicted_aqi' => $existingPrediction['predicted_aqi'] ?? $predictionPayload['predicted_aqi'],
            'confidence' => isset($existingPrediction['confidence']) && is_numeric($existingPrediction['confidence']) ? floatval($existingPrediction['confidence']) : $predictionPayload['confidence'],
            'prediction_time' => $existingPrediction['prediction_time'] ?? $predictionPayload['prediction_time'],
            'created_at' => $existingPrediction['created_at'] ?? null,
          ];
        }
      }else{
        $insert = $pdo->prepare('INSERT INTO predictions (predicted_aqi, confidence, prediction_time) VALUES (?, ?, ?)');
        $insert->execute([$predictionPayload['predicted_aqi'], $predictionPayload['confidence'], $predictionPayload['prediction_time']]);
        $savedPrediction = [
          'id' => intval($pdo->lastInsertId()),
          'predicted_aqi' => $predictionPayload['predicted_aqi'],
          'confidence' => $predictionPayload['confidence'],
          'prediction_time' => $predictionPayload['prediction_time'],
          'created_at' => date('Y-m-d H:i:s'),
        ];
        $predictionWasPersisted = true;
      }
    }catch(Exception $e){
      $predictionWasPersisted = false;
      $savedPrediction = null;
    }
  }

  $latestPrediction = null;
  if($savedPrediction){
    $band = prediction_band($savedPrediction['predicted_aqi'] ?? null);
    $latestPrediction = [
      'id' => isset($savedPrediction['id']) ? intval($savedPrediction['id']) : null,
      'predicted_aqi' => normalize_prediction_label($savedPrediction['predicted_aqi'] ?? 'Unknown'),
      'confidence' => isset($savedPrediction['confidence']) && is_numeric($savedPrediction['confidence']) ? floatval($savedPrediction['confidence']) : null,
      'prediction_time' => $savedPrediction['prediction_time'] ?? null,
      'prediction_timestamp' => isset($savedPrediction['prediction_time']) ? (is_numeric($savedPrediction['prediction_time']) ? intval($savedPrediction['prediction_time']) : strtotime($savedPrediction['prediction_time'])) : null,
      'created_at' => $savedPrediction['created_at'] ?? null,
      'color' => $band['color'],
      'source' => 'saved-from-sensor-data'
    ];
  }elseif($stored){
    $band = prediction_band($stored['predicted_aqi'] ?? null);
    $latestPrediction = [
      'id' => isset($stored['id']) ? intval($stored['id']) : null,
      'predicted_aqi' => normalize_prediction_label($stored['predicted_aqi'] ?? 'Unknown'),
      'confidence' => isset($stored['confidence']) && is_numeric($stored['confidence']) ? floatval($stored['confidence']) : null,
      'prediction_time' => $stored['prediction_time'] ?? null,
      'prediction_timestamp' => isset($stored['prediction_time']) ? (is_numeric($stored['prediction_time']) ? intval($stored['prediction_time']) : strtotime($stored['prediction_time'])) : null,
      'created_at' => $stored['created_at'] ?? null,
      'color' => $band['color'],
      'source' => 'stored'
    ];
  }elseif($forecast){
    $latest = $forecast[0];
    $latestPrediction = [
      'id' => null,
      'predicted_aqi' => $latest['label'],
      'confidence' => $derivedConfidence,
      'prediction_time' => date('Y-m-d H:i:s', time() + 3600),
      'prediction_timestamp' => time() + 3600,
      'created_at' => date('Y-m-d H:i:s'),
      'color' => $latest['color'],
      'source' => 'derived'
    ];
  }

  $direction = 'Stable';
  if($slope !== null){
    if($slope > 0.02) $direction = 'Worsening';
    elseif($slope < -0.02) $direction = 'Improving';
  }

  echo json_encode([
    'success' => true,
    'latest' => $latestPrediction,
    'forecast' => $forecast,
    'saved' => $predictionWasPersisted,
    'cycle_seconds' => $cycleSeconds,
    'trend' => [
      'direction' => $direction,
      'score' => $latestScore,
      'confidence' => $derivedConfidence,
      'samples' => count($scores),
      'source' => $savedPrediction ? 'sensor_data persisted to predictions' : ($stored ? 'predictions table' : 'sensor_data fallback')
    ]
  ]);
}catch(Exception $e){
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DB failed']);
}

// Debug helper: if debug=1, output last computed values (no DB changes)
// Note: kept after main response to avoid changing normal behavior; use only for troubleshooting.
if(isset($_GET['debug']) && intval($_GET['debug']) === 1){
  // Re-run minimal pieces to surface internal state (non-invasive)
  $debug = [
    'scores' => $scores ?? null,
    'latestSensorTs' => $latestSensorTs ?? null,
    'savedPrediction' => $savedPrediction ?? null,
    'stored' => $stored ?? null,
    'cycleSeconds' => $cycleSeconds ?? null,
    'forecast' => $forecast ?? null,
  ];
  header('Content-Type: application/json');
  echo "\n";
  echo json_encode(['debug' => $debug], JSON_PRETTY_PRINT);
}