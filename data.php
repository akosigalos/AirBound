<?php

require_once __DIR__ . '/api/config.php';
header('Content-Type: application/json');

try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS sensor_data (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        mq2 DOUBLE DEFAULT NULL,
        mq135 DOUBLE DEFAULT NULL,
        dust DOUBLE DEFAULT NULL,
        created_at INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    try {
        $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq2 DOUBLE DEFAULT NULL');
    } catch (Exception $e) {
    }

    try {
        $pdo->exec('ALTER TABLE sensor_data ADD COLUMN mq135 DOUBLE DEFAULT NULL');
    } catch (Exception $e) {
    }

    $stmt = $pdo->query('SELECT COALESCE(mq2, mq135) AS mq2, COALESCE(mq2, mq135) AS mq135, dust, created_at FROM sensor_data ORDER BY id DESC LIMIT 1');
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

    echo json_encode($row ?: ['mq2' => null, 'mq135' => null, 'dust' => null, 'created_at' => null]);
} catch (Exception $e) {
    echo json_encode(['mq2' => null, 'mq135' => null, 'dust' => null, 'created_at' => null]);
}
?>