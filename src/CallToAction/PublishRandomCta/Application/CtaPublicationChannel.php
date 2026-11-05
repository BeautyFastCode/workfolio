<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

final class CtaPublicationChannel
{
    public const string TARGET = 'cta_message_target';
    private const string TOPIC = 'cta_message_topic';

    public static function topic(string $locale): string
    {
        return sprintf('%s_%s', self::TOPIC, $locale);
    }
}
