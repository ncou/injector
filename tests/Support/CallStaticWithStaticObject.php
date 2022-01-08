<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

use Exception;

final class CallStaticWithStaticObject
{
    public static bool $wasCalled = false;

    public static function __callStatic(string $name, array $args): string
    {
        if ($name === 'foo') {
            static::$wasCalled = true;

            return 'bar';
        }

        throw new Exception('Unknown method.');
    }
}
