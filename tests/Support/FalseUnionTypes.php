<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

use StdClass;

class FalseUnionTypes
{
    private false|StdClass $value;
    private mixed $extra;

    public function __construct(false|StdClass $value, mixed $extra)
    {
        $this->value = $value;
        $this->extra = $extra;
    }

    public function getValue(): false|StdClass
    {
        return $this->value;
    }

    public function getExtra(): mixed
    {
        return $this->extra;
    }
}
