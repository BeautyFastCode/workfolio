<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

final readonly class ListStatisticsHandler
{
    public function __construct(
        private StatisticFinder $finder,
    ) {}

    public function __invoke(ListStatisticsQuery $query): ListStatisticsResult
    {
        return new ListStatisticsResult($this->finder->findAll($query->locale));
    }
}
