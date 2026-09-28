<?php

// Salinan ~/public_html/wos.whisnusantika.com/index.php di server (cPanel).
// File ini TIDAK ikut auto-deploy: edit manual di server, lalu perbarui salinan ini.
// Cadangan lama di server: index.php.bak (sebelum usePublicPath, 2026-09-28).

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../../wsm-office/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../../wsm-office/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__ . '/../../wsm-office/bootstrap/app.php';

// WAJIB: arahkan public path ke docroot ini supaya @vite membaca ./build/manifest.json
// (yang di-update GitHub Actions), bukan ~/wsm-office/public/build. Lihat README 5.8 no. 6.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());