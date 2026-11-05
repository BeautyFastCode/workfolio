<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Application;

final readonly class ListTasksResult
{
    /**
     * @param list<TaskView> $items
     */
    public function __construct(
        public array $items,
    ) {}
}
