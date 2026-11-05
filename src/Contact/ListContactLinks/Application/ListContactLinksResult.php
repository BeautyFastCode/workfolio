<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\Application;

final readonly class ListContactLinksResult
{
    /**
     * @param list<ContactLinkView> $links
     */
    public function __construct(
        public array $links,
    ) {}
}
