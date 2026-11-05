<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

final readonly class StatisticGroupView
{
    /**
     * @param list<StatisticItemView> $items
     */
    public function __construct(
        public ?string $name,
        public array $items,
    ) {}
}
