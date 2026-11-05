<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

final readonly class ListTechnologiesQuery
{
    public function __construct(
        public string $locale,
        public bool $highlighted,
    ) {}
}
