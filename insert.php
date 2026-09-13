<?php

require_once __DIR__ . '/api/config.php';

header('Content-Type: text/plain');

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

    $mq2Input = $_POST['mq2'] ?? $_GET['mq2'] ?? null;
    $dustInput = $_POST['dust'] ?? $_GET['dust'] ?? null;

    $mq2 = $mq2Input !== null && $mq2Input !== '' && is_numeric($mq2Input) ? floatval($mq2Input) : null;
    $dust = $dustInput !== null && $dustInput !== '' && is_numeric($dustInput) ? floatval($dustInput) : null;

    if ($mq2 === null || $dust === null) {
        echo 'No data received';
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO sensor_data (mq2, mq135, dust, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$mq2, $mq2, $dust, time()]);

    echo 'success';
} catch (Exception $e) {
    echo 'insert failed';
}
?>