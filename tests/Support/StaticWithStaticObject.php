<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

final class StaticWithStaticObject
{
    public static bool $wasCalled = false;

    public static function foo(): string
    {
        static::$wasCalled = true;
        return 'bar';
    }
}
