<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

final readonly class ListNavigationQuery
{
    public function __construct(
        public string $locale,
        public ?string $currentSlug,
        public bool $currentPageAvailable,
    ) {}
}
