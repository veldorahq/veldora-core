<?php

declare(strict_types=1);

namespace Veldora\Framework\Foundation;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Bind a transient dependency (new instance generated on every resolution).
     */
    public function set(string $id, mixed $concrete = null): void;

    /**
     * Bind a singleton dependency (cached instance returned on subsequent resolutions).
     */
    public function singleton(string $id, mixed $concrete = null): void;

    /**
     * Resolve a class using reflection autowiring.
     *
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function resolve(string $class): mixed;
}
