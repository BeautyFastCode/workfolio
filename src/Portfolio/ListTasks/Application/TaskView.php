<?php

declare(strict_types=1);

namespace App\Portfolio\ListTasks\Application;

final readonly class TaskView
{
    public function __construct(
        public ?string $thumbnail,
        public ?string $name,
        public ?string $description,
        public ?string $tools,
        public ?string $impact,
    ) {}
}
