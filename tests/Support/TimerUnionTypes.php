<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

use DateTimeInterface;

class TimerUnionTypes
{
    private string|DateTimeInterface $time;
    public function __construct(string|DateTimeInterface $time)
    {
        $this->time = $time;
    }
    public function getTime(): string|DateTimeInterface
    {
        return $this->time;
    }
}
