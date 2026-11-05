<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

final readonly class DefaultPageSelection
{
    private const int CURRENT_POSITION = 3;

    private function __construct(
        public int $position,
    ) {}

    public static function current(): self
    {
        return new self(self::CURRENT_POSITION);
    }
}
