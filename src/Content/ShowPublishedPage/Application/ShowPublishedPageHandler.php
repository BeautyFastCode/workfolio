<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

final readonly class ShowPublishedPageHandler
{
    public function __construct(
        private PublishedPageFinder $pageFinder,
    ) {}

    public function __invoke(ShowPublishedPageQuery $query): ?PublishedPageView
    {
        if (empty($query->slug)) {
            return $this->pageFinder->findDefaultPublished(
                DefaultPageSelection::current(),
                $query->locale,
            );
        }

        return $this->pageFinder->findPublishedBySlug($query->slug, $query->locale);
    }
}
