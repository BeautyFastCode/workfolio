<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListStatistics\Application;

use App\Portfolio\ListStatistics\Application\ListStatisticsHandler;
use App\Portfolio\ListStatistics\Application\ListStatisticsQuery;
use App\Portfolio\ListStatistics\Application\ListStatisticsResult;
use App\Portfolio\ListStatistics\Application\StatisticFinder;
use App\Portfolio\ListStatistics\Application\StatisticGroupView;
use App\Portfolio\ListStatistics\Application\StatisticItemView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListStatistics\Application\ListStatisticsHandler
 * @covers \App\Portfolio\ListStatistics\Application\ListStatisticsQuery
 * @covers \App\Portfolio\ListStatistics\Application\ListStatisticsResult
 * @covers \App\Portfolio\ListStatistics\Application\StatisticGroupView
 * @covers \App\Portfolio\ListStatistics\Application\StatisticItemView
 */
final class ListStatisticsHandlerTest extends TestCase
{
    public function test_it_returns_groups_for_the_requested_locale(): void
    {
        $groups = [new StatisticGroupView('Achievements', [
            new StatisticItemView('Projects', 'fa-solid fa-code', '300'),
        ])];
        $finder = $this->createMock(StatisticFinder::class);
        $finder
            ->expects(self::once())
            ->method('findAll')
            ->with('pl')
            ->willReturn($groups)
        ;

        $result = (new ListStatisticsHandler($finder))(
            new ListStatisticsQuery('pl'),
        );

        self::assertEquals(new ListStatisticsResult($groups), $result);
    }

    public function test_it_returns_an_empty_list(): void
    {
        $finder = $this->createStub(StatisticFinder::class);
        $finder->method('findAll')->willReturn([]);

        $result = (new ListStatisticsHandler($finder))(
            new ListStatisticsQuery('en'),
        );

        self::assertSame([], $result->groups);
    }
}
