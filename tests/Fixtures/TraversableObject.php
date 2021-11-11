<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Fixtures;

class TraversableObject implements \IteratorAggregate
{
    public function getIterator() {}
}
