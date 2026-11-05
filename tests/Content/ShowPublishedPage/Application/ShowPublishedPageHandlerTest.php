<?php

declare(strict_types=1);

namespace App\Tests\Content\ShowPublishedPage\Application;

use App\Content\ShowPublishedPage\Application\DefaultPageSelection;
use App\Content\ShowPublishedPage\Application\PublishedPageBlockView;
use App\Content\ShowPublishedPage\Application\PublishedPageFinder;
use App\Content\ShowPublishedPage\Application\PublishedPageView;
use App\Content\ShowPublishedPage\Application\ShowPublishedPageHandler;
use App\Content\ShowPublishedPage\Application\ShowPublishedPageQuery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Content\ShowPublishedPage\Application\DefaultPageSelection
 * @covers \App\Content\ShowPublishedPage\Application\PublishedPageBlockView
 * @covers \App\Content\ShowPublishedPage\Application\PublishedPageView
 * @covers \App\Content\ShowPublishedPage\Application\ShowPublishedPageHandler
 * @covers \App\Content\ShowPublishedPage\Application\ShowPublishedPageQuery
 */
final class ShowPublishedPageHandlerTest extends TestCase
{
    public function test_it_returns_a_published_page_found_by_slug_and_locale(): void
    {
        $page = new PublishedPageView('about', 'About', 'About us', 'default.html.twig', []);
        $finder = $this->createMock(PublishedPageFinder::class);
        $finder
            ->expects(self::once())
            ->method('findPublishedBySlug')
            ->with('about', 'en')
            ->willReturn($page)
        ;
        $finder->expects(self::never())->method('findDefaultPublished');

        $result = (new ShowPublishedPageHandler($finder))(new ShowPublishedPageQuery('about', 'en'));

        self::assertSame($page, $result);
    }

    public function test_it_uses_the_named_current_default_page_selection_when_slug_is_missing(): void
    {
        $page = new PublishedPageView('home', 'Home', 'Home', 'default.html.twig', []);
        $finder = $this->createMock(PublishedPageFinder::class);
        $finder->expects(self::never())->method('findPublishedBySlug');
        $finder
            ->expects(self::once())
            ->method('findDefaultPublished')
            ->with(self::callback(static fn (DefaultPageSelection $selection): bool => $selection->position === 3), 'pl')
            ->willReturn($page)
        ;

        $result = (new ShowPublishedPageHandler($finder))(new ShowPublishedPageQuery(null, 'pl'));

        self::assertSame($page, $result);
    }

    public function test_zero_slug_selects_the_default_page(): void
    {
        $finder = $this->createMock(PublishedPageFinder::class);
        $finder->expects(self::never())->method('findPublishedBySlug');
        $finder
            ->expects(self::once())
            ->method('findDefaultPublished')
            ->willReturn(null)
        ;

        $result = (new ShowPublishedPageHandler($finder))(new ShowPublishedPageQuery('0', 'en'));

        self::assertNull($result);
    }

    public function test_it_returns_null_when_a_published_page_cannot_be_found(): void
    {
        $finder = $this->createStub(PublishedPageFinder::class);
        $finder->method('findPublishedBySlug')->willReturn(null);

        self::assertNull(
            (new ShowPublishedPageHandler($finder))(new ShowPublishedPageQuery('missing', 'en')),
        );
    }

    public function test_the_view_exposes_localized_block_content_without_a_doctrine_collection(): void
    {
        $page = new PublishedPageView('about', 'About', 'About', 'technologies.html.twig', [
            new PublishedPageBlockView('intro', 'html', '<p>Introduction</p>'),
        ]);

        self::assertSame('<p>Introduction</p>', $page->blockContent('intro'));
        self::assertNull($page->blockContent('missing'));
    }
}
