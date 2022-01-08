<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

use Exception;

final class CallStaticObject
{
    public static function __callStatic(string $name, array $args): string
    {
        if ($name === 'foo') {
            return 'bar';
        }

        throw new Exception('Unknown method.');
    }
}
