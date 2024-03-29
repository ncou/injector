<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

final class StaticWithSelfObject
{
    public static bool $wasCalled = false;

    public static function foo(): string
    {
        self::$wasCalled = true;

        return 'bar';
    }
}
