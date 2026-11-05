<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

final readonly class RandomCta
{
    /**
     * @param list<RandomCtaTranslation> $translations
     */
    public function __construct(
        public string $displayName,
        public array $translations,
    ) {}
}
