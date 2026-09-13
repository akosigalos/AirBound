<?php
require __DIR__ . '/../api/config.php';

echo 'DB_NAME=' . $DB_NAME . PHP_EOL;
$stmt = $pdo->query('SHOW TABLES');
$tables = [];
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}
echo 'TABLES=' . implode(',', $tables) . PHP_EOL;

$stmt2 = $pdo->query('SELECT COUNT(*) AS c FROM users');
echo 'USER_COUNT=' . $stmt2->fetchColumn() . PHP_EOL;
