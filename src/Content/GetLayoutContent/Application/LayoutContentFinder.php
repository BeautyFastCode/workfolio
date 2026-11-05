<?php

declare(strict_types=1);

namespace App\Content\GetLayoutContent\Application;

interface LayoutContentFinder
{
    public function find(string $collectionName, string $locale): ?LayoutContentView;
}
