<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\Application;

interface ContactLinkFinder
{
    /**
     * @return list<ContactLinkView>
     */
    public function findEnabled(): array;
}
