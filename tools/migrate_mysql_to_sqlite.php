<?php
/**
 * Safe, one-way copy from AIR-BOUND MySQL to a new SQLite file.
 * Usage: php tools/migrate_mysql_to_sqlite.php --run
 * The script never alters, deletes, or drops MySQL data.
 */
declare(strict_types=1);

$run = in_array('--run', $argv, true);
$root = dirname(__DIR__);
$sqlitePath = getenv('SQLITE_PATH') ?: $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'airbound.sqlite';
$schemaPath = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sqlite.sql';
$host = getenv('DB_HOST') ?: '127.0.0.1';
$name = getenv('DB_NAME') ?: 'airbound_app';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$tables = ['users', 'devices', 'readings', 'alerts', 'sensor_data', 'predictions'];

function fail(string $message): void { fwrite(STDERR, "ERROR: $message" . PHP_EOL); exit(1); }
function countRows(PDO $pdo, string $table): int { return (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn(); }

if (!extension_loaded('pdo_mysql') || !extension_loaded('pdo_sqlite')) fail('Both pdo_mysql and pdo_sqlite must be enabled.');
if (!$run) {
  echo "Dry run only: no database or file will be created. Re-run with --run after checking the MySQL backup." . PHP_EOL;
  echo "Target SQLite file: $sqlitePath" . PHP_EOL;
  exit(0);
}
if (file_exists($sqlitePath)) fail("Refusing to overwrite existing SQLite file: $sqlitePath");
if (!is_file($schemaPath)) fail("Missing SQLite schema: $schemaPath");

try {
  $mysql = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Throwable $e) {
  fail('Cannot connect to MySQL source database. No SQLite file was created. ' . $e->getMessage());
}

foreach ($tables as $table) {
  $check = $mysql->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
  $check->execute([$name, $table]);
  if ((int)$check->fetchColumn() !== 1) fail("Expected source table is missing: $table. No SQLite file was created.");
}

$directory = dirname($sqlitePath);
if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) fail("Cannot create SQLite directory: $directory");
try {
  $sqlite = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
  $sqlite->exec(file_get_contents($schemaPath));
  $sqlite->exec('PRAGMA foreign_keys = OFF');
  $sqlite->beginTransaction();
  $counts = [];
  foreach ($tables as $table) {
    $rows = $mysql->query("SELECT * FROM `$table` ORDER BY id ASC");
    $first = $rows->fetch();
    if ($first === false) { $counts[$table] = ['mysql' => 0, 'sqlite' => 0]; continue; }
    $columns = array_keys($first);
    $quoted = implode(', ', array_map(static fn(string $column): string => '"' . str_replace('"', '""', $column) . '"', $columns));
    $marks = implode(', ', array_fill(0, count($columns), '?'));
    $insert = $sqlite->prepare("INSERT INTO \"$table\" ($quoted) VALUES ($marks)");
    $insert->execute(array_values($first));
    while ($row = $rows->fetch()) $insert->execute(array_values($row));
    $counts[$table] = ['mysql' => countRows($mysql, $table), 'sqlite' => (int)$sqlite->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn()];
    if ($counts[$table]['mysql'] !== $counts[$table]['sqlite']) throw new RuntimeException("Row-count mismatch for $table");
  }
  $sqlite->commit();
  $sqlite->exec('PRAGMA foreign_keys = ON');
  $violations = $sqlite->query('PRAGMA foreign_key_check')->fetchAll();
  if ($violations) throw new RuntimeException('Foreign-key validation failed after copy.');
  $reportPath = $directory . DIRECTORY_SEPARATOR . 'sqlite-migration-report.json';
  file_put_contents($reportPath, json_encode(['migrated_at' => date(DATE_ATOM), 'source_database' => $name, 'target' => $sqlitePath, 'counts' => $counts], JSON_PRETTY_PRINT));
  echo "Migration complete. Row counts:" . PHP_EOL;
  foreach ($counts as $table => $count) echo "$table: MySQL={$count['mysql']} SQLite={$count['sqlite']}" . PHP_EOL;
  echo "Report: $reportPath" . PHP_EOL;
} catch (Throwable $e) {
  if (isset($sqlite) && $sqlite->inTransaction()) $sqlite->rollBack();
  // The only new artifact may be an incomplete SQLite file; preserve it for inspection rather than deleting it.
  fail('Migration stopped without modifying MySQL. SQLite file was preserved for inspection: ' . $e->getMessage());
}
