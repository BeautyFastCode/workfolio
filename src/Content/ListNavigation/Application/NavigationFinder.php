<?php

declare(strict_types=1);

namespace App\Content\ListNavigation\Application;

interface NavigationFinder
{
    /**
     * @return list<NavigationCandidate>
     */
    public function findPublished(string $locale): array;
}
