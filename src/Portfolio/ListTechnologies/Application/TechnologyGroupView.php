<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

final readonly class TechnologyGroupView
{
    /**
     * @param list<TechnologyItemView> $items
     */
    public function __construct(
        public ?string $name,
        public array $items,
    ) {}
}
