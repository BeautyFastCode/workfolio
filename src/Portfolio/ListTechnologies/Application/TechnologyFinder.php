<?php

declare(strict_types=1);

namespace App\Portfolio\ListTechnologies\Application;

interface TechnologyFinder
{
    /**
     * @return list<TechnologyGroupView>
     */
    public function find(string $locale, bool $highlighted): array;
}
