<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

interface PublishedPageFinder
{
    public function findPublishedBySlug(string $slug, string $locale): ?PublishedPageView;

    public function findDefaultPublished(
        DefaultPageSelection $selection,
        string $locale,
    ): ?PublishedPageView;
}
