<?php

declare(strict_types=1);

namespace App\Shared\Observability\Application;

interface HandlerMetrics
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function measure(string $handler, callable $operation): mixed;
}
