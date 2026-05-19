<?php

/*
 |-----------------------------------------------------------------
 | Vercel Serverless Entry Point
 |-----------------------------------------------------------------
 | Filesystem Vercel read-only kecuali /tmp. Sebelum bootstrap
 | Laravel: redirect storage path & pastikan folder cache ada.
 */

// Tampilkan error apapun ke browser saat APP_DEBUG=true,
// supaya 500 tidak silent di Vercel.
$debug = filter_var(getenv('APP_DEBUG') ?: '', FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

try {
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

        // Seed SQLite database ke /tmp (demo only).
        $srcDb = __DIR__.'/../database/database.sqlite';
        $tmpDb = $tmp.'/database.sqlite';
        if (is_file($srcDb) && ! is_file($tmpDb)) {
            @copy($srcDb, $tmpDb);
        } elseif (! is_file($tmpDb)) {
            @touch($tmpDb);
        }
    }

    $autoload = __DIR__.'/../vendor/autoload.php';
    if (! is_file($autoload)) {
        throw new RuntimeException('vendor/autoload.php tidak ditemukan – composer install gagal saat build.');
    }
    require $autoload;

    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    if (is_dir('/tmp') && is_writable('/tmp')) {
        $app->useStoragePath('/tmp/storage');
    }

    $app->handleRequest(\Illuminate\Http\Request::capture());

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');

    if ($debug) {
        echo "=== Vercel Bootstrap Error ===\n\n";
        echo get_class($e) . ': ' . $e->getMessage() . "\n";
        echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo "--- Trace ---\n" . $e->getTraceAsString() . "\n\n";
        echo "--- Env keys yang terbaca ---\n";
        foreach (['APP_KEY','APP_ENV','APP_DEBUG','APP_URL','DB_CONNECTION','DB_DATABASE','SESSION_DRIVER','CACHE_STORE','LOG_CHANNEL'] as $k) {
            $v = getenv($k);
            if ($k === 'APP_KEY' && $v) {
                $v = substr($v, 0, 12) . '...(' . strlen($v) . ' chars)';
            }
            echo str_pad($k, 18) . '= ' . ($v === false ? '(unset)' : $v) . "\n";
        }
    } else {
        echo "Server Error 500 – set APP_DEBUG=true di Vercel env untuk detail.";
    }

    error_log('[vercel-bootstrap] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
