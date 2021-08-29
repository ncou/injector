<?php

declare(strict_types=1);

namespace Chiron\Injector;

interface InvokerInterface
{
    /*
     * @param callable|array|string $callable
     * @param array  $arguments
     *
     * @return mixed
     */
    public function invoke($callable, array $arguments = []);
}
