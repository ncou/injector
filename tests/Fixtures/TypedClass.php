<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

class TypedClass
{
    public function __construct(
        string $string,
        int $int,
        float $float,
        bool $bool,
        array $array = [],
        ?string $pong = null
    ) {
    }
}
