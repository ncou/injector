<?php

declare(strict_types=1);

namespace Chiron\Injector\Test\Container;

use Chiron\Injector\Test\Container\Exception\NotFoundException;
use Closure;
use Psr\Container\ContainerInterface;
use Throwable;

use function array_key_exists;

final class SimpleContainer implements ContainerInterface
{
    /** @var array */
    private $definitions;
    /** @var Closure|null */
    private $factory;

    /**
     * @param array        $definitions
     * @param Closure|null $factory     Should be closure that works like ContainerInterface::get(string $id): mixed
     */
    public function __construct(array $definitions = [], ?Closure $factory = null)
    {
        $this->definitions = $definitions;
        $this->factory = $factory ?? static function (string $id): void {
            throw new NotFoundException($id);
        };
    }

    public function get($id)
    {
        if (! array_key_exists($id, $this->definitions)) {
            $this->definitions[$id] = ($this->factory)($id);
        }

        return $this->definitions[$id];
    }

    public function has($id): bool
    {
        if (array_key_exists($id, $this->definitions)) {
            return true;
        }
        try {
            $this->get($id);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
