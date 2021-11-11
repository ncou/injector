<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

class EngineObject2
{
    private $engine;

    public function __construct(EngineMarkTwo $engine)
    {
        $this->engine = $engine;
    }

    public function getEngine(): EngineMarkTwo
    {
        return $this->engine;
    }
}
