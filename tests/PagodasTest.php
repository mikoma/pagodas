<?php

namespace Pagodas;

use PHPUnit\Framework\TestCase;
use SR\ApcuSimpleCache\ApcuCacheStorage;

class PagodasTest extends TestCase
{
    public function testInclusion()
    {
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache", new ApcuCacheStorage());
        echo $pgd->render(
            "base.html",
            [
                'title' => 'Pagodas',
                'variable' => 'chicken'
            ]
        );
    }

    public function testInheritanceAndDefaultOverwriting()
    {
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache", new ApcuCacheStorage());
        echo $pgd->render(
            "inheritText.html",
            [
                'title' => 'Pagodas',
                'variable' => 'chicken'
            ],
            [
                'header' => 'text.html'
            ]
        );
    }


}
