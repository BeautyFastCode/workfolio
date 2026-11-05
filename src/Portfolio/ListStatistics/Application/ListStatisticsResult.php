<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

final readonly class ListStatisticsResult
{
    /**
     * @param list<StatisticGroupView> $groups
     */
    public function __construct(
        public array $groups,
    ) {}
}
