<?php

declare(strict_types=1);

namespace App\Tests\Contact\ListContactLinks\Application;

use App\Contact\ListContactLinks\Application\ContactLinkFinder;
use App\Contact\ListContactLinks\Application\ContactLinkView;
use App\Contact\ListContactLinks\Application\ListContactLinksHandler;
use App\Contact\ListContactLinks\Application\ListContactLinksQuery;
use App\Contact\ListContactLinks\Application\ListContactLinksResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Contact\ListContactLinks\Application\ContactLinkView
 * @covers \App\Contact\ListContactLinks\Application\ListContactLinksHandler
 * @covers \App\Contact\ListContactLinks\Application\ListContactLinksQuery
 * @covers \App\Contact\ListContactLinks\Application\ListContactLinksResult
 */
final class ListContactLinksHandlerTest extends TestCase
{
    public function test_it_returns_enabled_links_from_the_finder(): void
    {
        $links = [new ContactLinkView(
            'GitHub',
            'https://github.com/example',
            'fa-brands fa-github',
        )];
        $finder = $this->createMock(ContactLinkFinder::class);
        $finder
            ->expects(self::once())
            ->method('findEnabled')
            ->willReturn($links)
        ;

        $result = (new ListContactLinksHandler($finder))(new ListContactLinksQuery());

        self::assertEquals(new ListContactLinksResult($links), $result);
    }

    public function test_it_returns_an_empty_list(): void
    {
        $finder = $this->createStub(ContactLinkFinder::class);
        $finder->method('findEnabled')->willReturn([]);

        $result = (new ListContactLinksHandler($finder))(new ListContactLinksQuery());

        self::assertSame([], $result->links);
    }
}
