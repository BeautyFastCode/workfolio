<?php

declare(strict_types=1);

namespace App\Content\ShowPublishedPage\Application;

final readonly class PublishedPageBlockView
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $content,
    ) {}
}
