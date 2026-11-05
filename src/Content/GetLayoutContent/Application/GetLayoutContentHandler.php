<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Application;

final readonly class GetLayoutContentHandler
{
    public function __construct(
        private LayoutContentFinder $finder,
    ) {}

    public function __invoke(GetLayoutContentQuery $query): ?LayoutContentView
    {
        return $this->finder->find($query->collectionName, $query->locale);
    }
}
