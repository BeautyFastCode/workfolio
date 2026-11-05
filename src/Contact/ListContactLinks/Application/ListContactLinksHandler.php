<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\Application;

final readonly class ListContactLinksHandler
{
    public function __construct(
        private ContactLinkFinder $finder,
    ) {}

    public function __invoke(ListContactLinksQuery $query): ListContactLinksResult
    {
        return new ListContactLinksResult($this->finder->findEnabled());
    }
}
