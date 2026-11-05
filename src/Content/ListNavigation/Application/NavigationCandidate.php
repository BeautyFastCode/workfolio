<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

final readonly class NavigationCandidate
{
    /**
     * @param list<string> $routeSlugs
     */
    public function __construct(
        public ?string $slug,
        public ?string $menuTitle,
        public int $position,
        public array $routeSlugs,
    ) {}
}
