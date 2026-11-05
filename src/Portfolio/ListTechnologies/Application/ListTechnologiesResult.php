<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

final readonly class ListTechnologiesResult
{
    /**
     * @param list<TechnologyGroupView> $groups
     */
    public function __construct(
        public array $groups,
    ) {}
}
