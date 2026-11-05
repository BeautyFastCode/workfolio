<?php

declare(strict_types=1);

namespace App\Portfolio\ListStatistics\Application;

interface StatisticFinder
{
    /**
     * @return list<StatisticGroupView>
     */
    public function findAll(string $locale): array;
}
