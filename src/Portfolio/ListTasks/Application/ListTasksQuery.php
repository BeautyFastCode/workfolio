<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Application;

final readonly class ListTasksQuery
{
    public function __construct(
        public string $locale,
    ) {}
}
