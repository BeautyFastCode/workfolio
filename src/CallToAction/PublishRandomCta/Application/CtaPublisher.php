<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

interface CtaPublisher
{
    /**
     * @throws CtaPublicationFailed
     */
    public function publish(string $message, string $locale): void;
}
