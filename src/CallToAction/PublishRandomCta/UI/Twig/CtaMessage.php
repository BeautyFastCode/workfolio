<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\UI\Twig;

use App\CallToAction\PublishRandomCta\Application\CtaPublicationChannel;
use App\Shared\Domain\LocaleEnum;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'CtaMessage', template: 'call_to_action/publish_random_cta/components/CtaMessage.html.twig')]
final class CtaMessage
{
    public string $target = CtaPublicationChannel::TARGET;
    public LocaleEnum $locale = LocaleEnum::EN;

    public function getTopic(): string
    {
        return CtaPublicationChannel::topic($this->locale->value);
    }
}
