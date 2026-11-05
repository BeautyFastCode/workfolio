<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

final readonly class NavigationItemView
{
    public function __construct(
        public ?string $slug,
        public ?string $menuTitle,
        public bool $active,
    ) {}
}
