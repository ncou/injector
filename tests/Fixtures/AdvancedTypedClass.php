<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

class AdvancedTypedClass
{
    public function __construct(
        ?callable $callable = null,
        ?object $object = null,
        ?iterable $iterable = null
    ) {
    }
}
