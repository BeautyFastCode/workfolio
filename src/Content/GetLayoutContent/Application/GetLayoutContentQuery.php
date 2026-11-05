<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Application;

final readonly class GetLayoutContentQuery
{
    public function __construct(
        public string $collectionName,
        public string $locale,
    ) {}
}
