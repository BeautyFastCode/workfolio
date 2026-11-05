<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

final readonly class TechnologyItemView
{
    public function __construct(
        public string $name,
        public ?string $icon,
    ) {}
}
