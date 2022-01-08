<?php

namespace Chiron\Injector\Test;

interface AnotherInterface
{
}

class IntersectionClasses
{
    public function __construct(EngineInterface&AnotherInterface $engine)
    {
    }
}
