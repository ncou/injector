<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

class EngineObject
{
    private $engine;

    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    public function getEngine(): EngineInterface
    {
        return $this->engine;
    }
}
