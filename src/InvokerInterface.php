<?php

declare(strict_types=1);

namespace Chiron\Injector;

interface InvokerInterface
{
    /**
     * @param mixed $callable
     * @param array $parameters
     *
     * @return mixed
     */
    public function invoke(mixed $callable, array $parameters = []): mixed;
}
