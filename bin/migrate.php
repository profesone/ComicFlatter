<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($sql === false) {
    fwrite(STDERR, "Missing schema.sql\n");
    exit(1);
}

$pdo = \App\Support\Db::pdo();
foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
}

$hasDeleted = $pdo->query("SHOW COLUMNS FROM users LIKE 'deleted_at'")->fetch();
if ($hasDeleted === false) {
    $pdo->exec('ALTER TABLE users ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER name');
}

$unique = $pdo->query("SHOW INDEX FROM jobs WHERE Key_name = 'jobs_stripe_session_unique'")->fetch();
if ($unique !== false) {
    $pdo->exec('ALTER TABLE jobs DROP INDEX jobs_stripe_session_unique');
    $pdo->exec('ALTER TABLE jobs ADD INDEX jobs_stripe_session_idx (stripe_session_id)');
}

$hasLine = $pdo->query("SHOW COLUMNS FROM jobs LIKE 'line_path'")->fetch();
if ($hasLine === false) {
    $pdo->exec('ALTER TABLE jobs ADD COLUMN line_path VARCHAR(512) NULL AFTER input_path');
}
echo "Schema applied.\n";
