<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

final readonly class ShowPublishedPageQuery
{
    public function __construct(
        public ?string $slug,
        public string $locale,
    ) {}
}
