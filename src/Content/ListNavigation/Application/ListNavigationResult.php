<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

final readonly class ListNavigationResult
{
    /**
     * @param list<NavigationItemView> $items
     */
    public function __construct(
        public array $items,
    ) {}
}
