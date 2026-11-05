<?php

declare(strict_types=1);

namespace App\Tests\Portfolio\ListTechnologies\Application;

use App\Portfolio\ListTechnologies\Application\ListTechnologiesHandler;
use App\Portfolio\ListTechnologies\Application\ListTechnologiesQuery;
use App\Portfolio\ListTechnologies\Application\ListTechnologiesResult;
use App\Portfolio\ListTechnologies\Application\TechnologyFinder;
use App\Portfolio\ListTechnologies\Application\TechnologyGroupView;
use App\Portfolio\ListTechnologies\Application\TechnologyItemView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Portfolio\ListTechnologies\Application\ListTechnologiesHandler
 * @covers \App\Portfolio\ListTechnologies\Application\ListTechnologiesQuery
 * @covers \App\Portfolio\ListTechnologies\Application\ListTechnologiesResult
 * @covers \App\Portfolio\ListTechnologies\Application\TechnologyGroupView
 * @covers \App\Portfolio\ListTechnologies\Application\TechnologyItemView
 */
final class ListTechnologiesHandlerTest extends TestCase
{
    public function test_it_returns_groups_for_the_requested_locale_and_filter(): void
    {
        $groups = [new TechnologyGroupView('Backend', [
            new TechnologyItemView('PHP', 'fa-brands fa-php'),
        ])];
        $finder = $this->createMock(TechnologyFinder::class);
        $finder
            ->expects(self::once())
            ->method('find')
            ->with('pl', false)
            ->willReturn($groups)
        ;

        $result = (new ListTechnologiesHandler($finder))(
            new ListTechnologiesQuery('pl', false),
        );

        self::assertEquals(new ListTechnologiesResult($groups), $result);
    }

    public function test_it_returns_an_empty_list(): void
    {
        $finder = $this->createStub(TechnologyFinder::class);
        $finder->method('find')->willReturn([]);

        $result = (new ListTechnologiesHandler($finder))(
            new ListTechnologiesQuery('en', true),
        );

        self::assertSame([], $result->groups);
    }
}
