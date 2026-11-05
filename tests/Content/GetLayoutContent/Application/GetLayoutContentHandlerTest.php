<?php

declare(strict_types=1);

namespace App\Tests\Content\GetLayoutContent\Application;

use App\Content\GetLayoutContent\Application\GetLayoutContentHandler;
use App\Content\GetLayoutContent\Application\GetLayoutContentQuery;
use App\Content\GetLayoutContent\Application\LayoutContentBlockView;
use App\Content\GetLayoutContent\Application\LayoutContentFinder;
use App\Content\GetLayoutContent\Application\LayoutContentView;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Content\GetLayoutContent\Application\GetLayoutContentHandler
 * @covers \App\Content\GetLayoutContent\Application\GetLayoutContentQuery
 * @covers \App\Content\GetLayoutContent\Application\LayoutContentBlockView
 * @covers \App\Content\GetLayoutContent\Application\LayoutContentView
 */
final class GetLayoutContentHandlerTest extends TestCase
{
    public function test_it_returns_layout_content_for_the_requested_collection_and_locale(): void
    {
        $expected = new LayoutContentView('header', [
            new LayoutContentBlockView('title', 'English title', null),
        ]);
        $finder = $this->createMock(LayoutContentFinder::class);
        $finder
            ->expects(self::once())
            ->method('find')
            ->with('header', 'en')
            ->willReturn($expected)
        ;

        $result = (new GetLayoutContentHandler($finder))(
            new GetLayoutContentQuery('header', 'en'),
        );

        self::assertSame($expected, $result);
    }

    public function test_it_preserves_a_missing_collection_as_null(): void
    {
        $finder = $this->createStub(LayoutContentFinder::class);
        $finder->method('find')->willReturn(null);

        self::assertNull((new GetLayoutContentHandler($finder))(
            new GetLayoutContentQuery('footer', 'pl'),
        ));
    }

    public function test_the_view_preserves_block_order_and_null_fallbacks(): void
    {
        $view = new LayoutContentView('header', [
            new LayoutContentBlockView('first', null, 'first.webp'),
            new LayoutContentBlockView('second', 'Second', null),
        ]);

        self::assertSame(['first', 'second'], array_map(
            static fn (LayoutContentBlockView $block): string => $block->name,
            $view->blocks,
        ));
        self::assertNull($view->blockContent('first'));
        self::assertSame('first.webp', $view->blockImage('first'));
        self::assertSame('Second', $view->blockContent('second'));
        self::assertNull($view->blockImage('second'));
        self::assertNull($view->blockContent('missing'));
        self::assertNull($view->blockImage('missing'));
    }
}
