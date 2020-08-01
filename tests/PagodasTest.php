<?php

namespace Pagodas;

use PHPUnit\Framework\TestCase;

class PagodasTest extends TestCase
{
    public function testInclusion()
    {
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache");
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
        $pgd = new Pagodas(__DIR__ . "/templates", __DIR__ . "/cache");
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
