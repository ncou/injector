<?php

declare(strict_types=1);

namespace Chiron\Injector;

interface FactoryInterface
{
    /**
     * @param string $class
     * @param array<mixed>  $parameters
     *
     * @return object
     */
    public function build(string $class, array $parameters = []): object;
}
