<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

/**
 * Interface ColorInterface defines car color
 */
interface ColorInterface
{
    /**
     * @return string
     */
    public function getColor(): string;
}
