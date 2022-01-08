<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

class MakeEngineCollector
{
    public array $engines;

    public function __construct(EngineInterface ...$engines)
    {
        $this->engines = $engines;
    }
}
