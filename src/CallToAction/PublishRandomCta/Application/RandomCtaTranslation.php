<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

final readonly class RandomCtaTranslation
{
    public function __construct(
        public string $locale,
        public string $message,
    ) {}
}
