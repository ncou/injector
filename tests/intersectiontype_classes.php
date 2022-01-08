<?php

namespace Chiron\Injector\Test;

use Chiron\Injector\Test\Support\EngineInterface;

interface AnotherInterface
{
}

class IntersectionClasses
{
    public function __construct(EngineInterface&AnotherInterface $engine)
    {
    }
}


class IntersectionEngine implements EngineInterface, AnotherInterface
{
    public function __construct(EngineInterface&AnotherInterface $engine)
    {
    }
}
