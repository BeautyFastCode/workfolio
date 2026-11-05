<?php

declare(strict_types=1);

namespace App\Tests\Content\ListNavigation\Application;

use App\Content\ListNavigation\Application\ListNavigationHandler;
use App\Content\ListNavigation\Application\ListNavigationQuery;
use App\Content\ListNavigation\Application\ListNavigationResult;
use App\Content\ListNavigation\Application\NavigationCandidate;
use App\Content\ListNavigation\Application\NavigationFinder;
use App\Content\ListNavigation\Application\NavigationItemView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Content\ListNavigation\Application\ListNavigationHandler
 * @covers \App\Content\ListNavigation\Application\ListNavigationQuery
 * @covers \App\Content\ListNavigation\Application\ListNavigationResult
 * @covers \App\Content\ListNavigation\Application\NavigationCandidate
 * @covers \App\Content\ListNavigation\Application\NavigationItemView
 */
final class ListNavigationHandlerTest extends TestCase
{
    public function test_it_returns_navigation_for_the_requested_locale_and_current_page(): void
    {
        $finder = $this->createMock(NavigationFinder::class);
        $finder
            ->expects(self::once())
            ->method('findPublished')
            ->with('en')
            ->willReturn([
                new NavigationCandidate('about', 'About us', 1, ['about', 'o-mnie']),
                new NavigationCandidate('contact', 'Contact', 2, ['contact', 'kontakt']),
            ])
        ;

        $result = (new ListNavigationHandler($finder))(
            new ListNavigationQuery('en', 'about', true),
        );

        self::assertEquals(new ListNavigationResult([
            new NavigationItemView('about', 'About us', true),
            new NavigationItemView('contact', 'Contact', false),
        ]), $result);
    }

    public function test_it_returns_an_empty_navigation(): void
    {
        $finder = $this->createStub(NavigationFinder::class);
        $finder->method('findPublished')->willReturn([]);

        $result = (new ListNavigationHandler($finder))(
            new ListNavigationQuery('pl', null, true),
        );

        self::assertSame([], $result->items);
    }

    public function test_it_selects_the_current_default_page_when_the_slug_is_empty(): void
    {
        $finder = $this->createStub(NavigationFinder::class);
        $finder->method('findPublished')->willReturn([
            new NavigationCandidate('first', 'First', 1, ['first']),
            new NavigationCandidate('default', 'Default', 3, ['default']),
        ]);

        $result = (new ListNavigationHandler($finder))(
            new ListNavigationQuery('en', '0', true),
        );

        self::assertFalse($result->items[0]->active);
        self::assertTrue($result->items[1]->active);
    }

    public function test_it_does_not_select_an_item_without_a_localized_slug_or_current_page(): void
    {
        $finder = $this->createStub(NavigationFinder::class);
        $finder->method('findPublished')->willReturn([
            new NavigationCandidate(null, null, 3, ['tylko-polski']),
            new NavigationCandidate('default', 'Default', 3, ['default']),
        ]);
        $handler = new ListNavigationHandler($finder);

        $missingTranslation = $handler(new ListNavigationQuery('en', 'tylko-polski', true));
        $missingRequest = $handler(new ListNavigationQuery('en', null, false));

        self::assertFalse($missingTranslation->items[0]->active);
        self::assertFalse($missingRequest->items[1]->active);
    }
}
