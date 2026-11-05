<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

final readonly class ListStatisticsQuery
{
    public function __construct(
        public string $locale,
    ) {}
}
