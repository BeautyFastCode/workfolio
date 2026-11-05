<?php

declare(strict_types=1);

namespace App\Contact\ListContactLinks\Application;

final readonly class ContactLinkView
{
    public function __construct(
        public string $name,
        public string $href,
        public ?string $icon,
    ) {}
}
