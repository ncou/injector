<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Support;

/**
 * EngineMarkTwo
 */
class StaticMethod
{
    public const NAME = 'Mark Two';

    /**
     * @return string
     */
    public static function getName(): string
    {
        return static::NAME;
    }

    public function getNameNonStatic(): string
    {
        return $this::NAME;
    }
}
