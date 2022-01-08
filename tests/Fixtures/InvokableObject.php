<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

class InvokableObject
{
    public function __invoke()
    {
    }
}
