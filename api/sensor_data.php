<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

try {
  if (!database_is_sqlite()) {
  $pdo->exec('CREATE TABLE IF NOT EXISTS sensor_data (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mq2 DOUBLE DEFAULT NULL,
    mq135 DOUBLE DEFAULT NULL,
    dust DOUBLE DEFAULT NULL,
    created_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX (created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

  try { $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq2 DOUBLE DEFAULT NULL'); } catch (Exception $e) {}
  try { $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq135 DOUBLE DEFAULT NULL'); } catch (Exception $e) {}
  }

  $start = isset($_GET['start']) && is_numeric($_GET['start']) ? intval($_GET['start']) : null;
  $end = isset($_GET['end']) && is_numeric($_GET['end']) ? intval($_GET['end']) : null;
  $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? max(1, min(5000, intval($_GET['limit']))) : 1000;

  $sql = 'SELECT id, COALESCE(mq2, mq135) AS mq2, COALESCE(mq2, mq135) AS mq135, dust, created_at FROM sensor_data ORDER BY id DESC LIMIT ' . intval($limit);
  $stmt = $pdo->query($sql);
  $rows = [];

  if($stmt){
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
      $ts = null;
      if(isset($r['created_at'])){
        if(is_numeric($r['created_at'])){
          $ts = intval($r['created_at']);
        }else{
          $parsed = strtotime($r['created_at']);
          if($parsed !== false) $ts = $parsed;
        }
      }

      if($ts === null) continue;
      if($start !== null && $ts < $start) continue;
      if($end !== null && $ts > $end) continue;

      $rows[] = [
        'id' => isset($r['id']) ? intval($r['id']) : null,
        'mq2' => isset($r['mq2']) ? floatval($r['mq2']) : null,
        'mq135' => isset($r['mq135']) ? floatval($r['mq135']) : null,
        'dust' => isset($r['dust']) ? floatval($r['dust']) : null,
        'created_at' => $ts,
      ];
    }
  }

  $rows = array_reverse($rows);
  echo json_encode(['rows' => $rows]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB failed']);
}
