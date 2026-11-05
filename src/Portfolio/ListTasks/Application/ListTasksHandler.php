<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Application;

final readonly class ListTasksHandler
{
    public function __construct(
        private TaskFinder $finder,
    ) {}

    public function __invoke(ListTasksQuery $query): ListTasksResult
    {
        return new ListTasksResult($this->finder->findAll($query->locale));
    }
}
