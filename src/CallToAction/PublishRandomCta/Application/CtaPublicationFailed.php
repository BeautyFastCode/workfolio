<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

final class CtaPublicationFailed extends \RuntimeException
{
    public static function forLocale(string $locale, \Throwable $previous): self
    {
        return new self(
            sprintf('CTA publication failed for locale "%s".', $locale),
            previous: $previous,
        );
    }
}
