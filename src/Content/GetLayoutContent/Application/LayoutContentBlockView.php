<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Application;

final readonly class LayoutContentBlockView
{
    public function __construct(
        public string $name,
        public ?string $content,
        public ?string $image,
    ) {}
}
