<?php
require __DIR__ . '/../api/config.php';
if (!empty($pdo)) {
    echo 'CONNECTED:' . $DB_NAME . PHP_EOL;
} else {
    echo 'NO_PDO' . PHP_EOL;
}
