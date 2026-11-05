<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

final readonly class ListTechnologiesHandler
{
    public function __construct(
        private TechnologyFinder $finder,
    ) {}

    public function __invoke(ListTechnologiesQuery $query): ListTechnologiesResult
    {
        return new ListTechnologiesResult($this->finder->find(
            $query->locale,
            $query->highlighted,
        ));
    }
}
