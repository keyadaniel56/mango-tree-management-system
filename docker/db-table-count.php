<?php

/**
 * Prints the row count of the given table.
 *
 *   "0"       - the table is empty or does not exist yet
 *   "<n>"     - number of rows
 *   "unknown" - the database could not be reached
 *
 * docker/start.sh uses this to decide whether the database still needs to be
 * seeded and whether Passport OAuth clients already exist.
 *
 * Usage: php docker/db-table-count.php users
 */

$table = isset($argv[1]) ? $argv[1] : 'users';
$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    echo Illuminate\Support\Facades\Schema::hasTable($table)
        ? (string) Illuminate\Support\Facades\DB::table($table)->count()
        : '0';
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    echo 'unknown';
}
