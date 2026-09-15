<?php

/**
 * Exits with status 0 as soon as the configured MySQL server accepts
 * connections. Used by docker/start.sh so the container can wait for the
 * Render MySQL private service to finish booting.
 *
 * Usage: php docker/db-ready.php
 */

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '3306';
$name = getenv('DB_DATABASE') ?: '';
$user = (string) getenv('DB_USERNAME');
$pass = (string) getenv('DB_PASSWORD');

if ($host === false || $host === '') {
    fwrite(STDERR, "DB_HOST is not set\n");
    exit(1);
}

try {
    new PDO(
        "mysql:host={$host};port={$port};dbname={$name}",
        $user,
        $pass,
        [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "database is up\n";
    exit(0);
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
