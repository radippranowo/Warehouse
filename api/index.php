<?php

/*
 |-----------------------------------------------------------------
 | Vercel Serverless Entry Point
 |-----------------------------------------------------------------
 | Filesystem Vercel read-only kecuali /tmp. Sebelum bootstrap
 | Laravel: redirect storage path & pastikan folder cache ada.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$tmp = '/tmp';

if (is_dir($tmp) && is_writable($tmp)) {
    $dirs = [
        $tmp.'/storage',
        $tmp.'/storage/app',
        $tmp.'/storage/app/public',
        $tmp.'/storage/framework',
        $tmp.'/storage/framework/cache',
        $tmp.'/storage/framework/cache/data',
        $tmp.'/storage/framework/sessions',
        $tmp.'/storage/framework/views',
        $tmp.'/storage/framework/testing',
        $tmp.'/storage/logs',
    ];

    foreach ($dirs as $dir) {
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    // Seed SQLite database ke /tmp (demo only – data hilang setiap cold start).
    $srcDb = __DIR__.'/../database/database.sqlite';
    $tmpDb = $tmp.'/database.sqlite';
    if (is_file($srcDb) && ! is_file($tmpDb)) {
        @copy($srcDb, $tmpDb);
    } elseif (! is_file($tmpDb)) {
        @touch($tmpDb);
    }
}

// Maintenance mode check.
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Redirect storage ke /tmp pada runtime Vercel.
if (is_dir($tmp) && is_writable($tmp)) {
    $app->useStoragePath($tmp.'/storage');
}

$app->handleRequest(Request::capture());
