<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

final readonly class PublishRandomCtaResult
{
    private function __construct(
        public bool $messageFound,
        public ?string $publishedMessage,
    ) {}

    public static function messageNotFound(): self
    {
        return new self(false, null);
    }

    public static function published(string $message): self
    {
        return new self(true, $message);
    }
}
