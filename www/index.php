<?php

namespace Pagodas;

use SR\ApcuSimpleCache\ApcuCacheStorage;

error_reporting(0);

require __DIR__ . "/../vendor/autoload.php";

$startTime = microtime(true);
$pagodas = new Pagodas(__DIR__ . "/../tests/templates", __DIR__ . "/../tests/cache", new ApcuCacheStorage());
$status = $pagodas->render(
    "base.html",
    [
        'title' => 'Pagodas',
        'variable' => 'chicken'
    ]
);
$time = 1000000 * (microtime(true) - $startTime);
echo "$status<br>Script runtime: " . $time . "µs";
