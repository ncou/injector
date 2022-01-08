<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

use IteratorAggregate;

class TraversableObject implements IteratorAggregate
{
    public function getIterator()
    {
    }
}
