<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

final readonly class StatisticItemView
{
    public function __construct(
        public ?string $name,
        public ?string $icon,
        public string $value,
    ) {}
}
