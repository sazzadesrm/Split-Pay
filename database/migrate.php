<?php
declare(strict_types=1);

/**
 * CLI migration runner: php database/migrate.php
 * Applies each database/migrations/*.sql file in order, tracked in a
 * `migrations` table so re-runs are safe.
 */

require __DIR__ . '/../vendor_autoload.php';

use App\Core\Env;

Env::load(__DIR__ . '/../.env');
$config = require __DIR__ . '/../config/database.php';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    run_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$applied = $pdo->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        echo "Skipping $name (already applied)\n";
        continue;
    }
    echo "Applying $name...\n";
    $sql = file_get_contents($file);
    $pdo->exec($sql);
    $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (?)');
    $stmt->execute([$name]);
}

echo "Migrations complete.\n";

$seed = $argv[1] ?? null;
if ($seed === '--seed') {
    echo "Applying demo seed data...\n";
    $pdo->exec(file_get_contents(__DIR__ . '/seeds/seed_demo_data.sql'));
    echo "Seed complete.\n";
}
