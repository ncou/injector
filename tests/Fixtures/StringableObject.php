<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

class StringableObject
{
    public function __toString(): string {
        return 'foobar';
    }
}
