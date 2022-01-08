<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

use Exception;

final class CallStaticWithSelfObject
{
    public static bool $wasCalled = false;

    public static function __callStatic(string $name, array $args): string
    {
        if ($name === 'foo') {
            self::$wasCalled = true;

            return 'bar';
        }

        throw new Exception('Unknown method.');
    }
}
