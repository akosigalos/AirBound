<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$dsDir = __DIR__ . '/../Data sets';
if(!is_dir($dsDir)){
  http_response_code(404);
  echo json_encode(['error' => 'No Data sets directory']);
  exit;
}

// Find first CSV file
$files = glob($dsDir . '/*.csv');
if(!$files){
  // try to match any file name also including spaces
  $files = glob($dsDir . '/*');
  if(!$files){ http_response_code(404); echo json_encode(['error'=>'No dataset']); exit; }
}
$csv = $files[0];

$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? intval($_GET['limit']) : 80;

$rows = [];
if(($h = fopen($csv, 'r')) !== false){
  $header = fgetcsv($h);
  if($header === false){ fclose($h); echo json_encode(['rows'=>[]]); exit; }
  // Map header to lower keys
  $map = array_map(function($v){ return strtolower(trim($v)); }, $header);
  $now = time();
  $i = 0;
  while(($data = fgetcsv($h)) !== false){
    $rec = array_combine($map, $data);
    // Interpret PM2.5 as dust proxy, PM10 as mq135 proxy
    $pm25 = isset($rec['pm2.5']) ? floatval($rec['pm2.5']) : (isset($rec['pm2.5']) ? floatval($rec['pm2.5']) : null);
    if(!$pm25 && isset($rec['pm25'])) $pm25 = floatval($rec['pm25']);
    $pm10 = isset($rec['pm10']) ? floatval($rec['pm10']) : null;

    // Scale factors chosen so values map into heat functions range
    $dust = $pm25 !== null ? ($pm25 * 50.0) : null; // PM2.5 * 50 -> 0..5000-ish
    $mq135 = $pm10 !== null ? ($pm10 * 20.0) : null; // PM10 * 20 -> 0..2000-ish

    $rows[] = [
      'mq135' => $mq135 !== null ? $mq135 : null,
      'dust' => $dust !== null ? $dust : null,
      'created_at' => $now - ($limit - $i)
    ];
    $i++;
    if($i >= $limit) break;
  }
  fclose($h);
}

// Return rows in same shape as api/sensor_data.php
echo json_encode(['rows' => $rows]);
