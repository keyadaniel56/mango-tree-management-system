<?php

/**
 * Laravel 5.5 reads vendor/composer/installed.json in the Composer 1 format
 * (a plain list of packages). Composer 2 wraps that list in a "packages" key,
 * which makes `php artisan package:discover` fail with "Undefined index: extra"
 * and therefore breaks every request.
 *
 * The Dockerfile runs this script right after `composer install`, and
 * docker/start.sh runs it again so that a vendor/ directory mounted from a
 * volume or restored from a build cache is fixed as well. The script is
 * idempotent: running it twice has no effect.
 *
 * Usage: php docker/patch-package-manifest.php [/path/to/app]
 *        (the path defaults to the project root this file lives in)
 */

$root = isset($argv[1]) ? rtrim($argv[1], '/') : dirname(__DIR__);
$file = $root.'/vendor/laravel/framework/src/Illuminate/Foundation/PackageManifest.php';

if (! file_exists($file)) {
    fwrite(STDERR, "PackageManifest.php not found - run composer install first.\n");
    exit(1);
}

$contents = file_get_contents($file);

if (strpos($contents, 'isset($packages["packages"])') !== false) {
    echo "PackageManifest.php is already patched.\n";
    exit(0);
}

$original = '            $packages = json_decode($this->files->get($path), true);';

if (strpos($contents, $original) === false) {
    fwrite(STDERR, "PackageManifest.php does not match the expected Laravel 5.5 source.\n");
    exit(1);
}

$patched = $original."\n\n".
    '            if (isset($packages["packages"])) { $packages = $packages["packages"]; }';

file_put_contents($file, str_replace($original, $patched, $contents));

echo "Patched PackageManifest.php for the Composer 2 installed.json format.\n";
