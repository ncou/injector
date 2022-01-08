<?php

namespace Chiron\Injector\Test;


interface AnotherInterface
{
}

interface AgainAnotherInterface
{
}

class IntersectionClasses
{
    public function __construct(AnotherInterface&AgainAnotherInterface $class)
    {
    }
}


class IntersectionEngine implements AnotherInterface, AgainAnotherInterface
{
    public function __construct()
    {
    }
}
