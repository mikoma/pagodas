<?php

namespace Pagodas;

use PHPUnit\Framework\TestCase;
use SR\ApcuSimpleCache\ApcuCacheStorage;

class PagodasTest extends TestCase
{
    public function testInclusion()
    {
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache", new ApcuCacheStorage());
        $filename = $pgd->render(
            "base.html",
            [
                'title' => 'Pagodas',
                'variable' => 'chicken'
            ]
        );
        $this->assertFileEquals(__DIR__ . "/mockData/inclusionTemplate.php", $filename);
    }

    public function testInheritanceAndDefaultOverwriting()
    {
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache", new ApcuCacheStorage());
        $filename = $pgd->render(
            "inheritText.html",
            [
                'title' => 'Pagodas',
                'variable' => 'chicken'
            ],
            [
                'header' => 'text.html'
            ]
        );
        $this->assertFileEquals(__DIR__ . "/mockData/inheritanceTemplate.php", $filename);
    }


}
