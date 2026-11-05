<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

final readonly class ListNavigationHandler
{
    private const int CURRENT_DEFAULT_PAGE_POSITION = 3;

    public function __construct(
        private NavigationFinder $navigationFinder,
    ) {}

    public function __invoke(ListNavigationQuery $query): ListNavigationResult
    {
        $items = [];

        foreach ($this->navigationFinder->findPublished($query->locale) as $candidate) {
            $items[] = new NavigationItemView(
                $candidate->slug,
                $candidate->menuTitle,
                $this->isActive($candidate, $query),
            );
        }

        return new ListNavigationResult($items);
    }

    private function isActive(NavigationCandidate $candidate, ListNavigationQuery $query): bool
    {
        if (!$query->currentPageAvailable || $candidate->slug === null) {
            return false;
        }

        if (empty($query->currentSlug)) {
            return $candidate->position === self::CURRENT_DEFAULT_PAGE_POSITION;
        }

        return in_array($query->currentSlug, $candidate->routeSlugs, true);
    }
}
