<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Infrastructure;

use App\CallToAction\PublishRandomCta\Application\CtaPublicationChannel;
use App\CallToAction\PublishRandomCta\Application\CtaPublicationFailed;
use App\CallToAction\PublishRandomCta\Application\CtaPublisher;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

final readonly class MercureTwigCtaPublisher implements CtaPublisher
{
    private const string TEMPLATE = 'call_to_action/publish_random_cta/update.stream.html.twig';

    public function __construct(
        private HubInterface $hub,
        private Environment $twig,
    ) {}

    public function publish(string $message, string $locale): void
    {
        try {
            $data = $this->twig->render(self::TEMPLATE, [
                'message' => $message,
                'target' => CtaPublicationChannel::TARGET,
            ]);

            $this->hub->publish(new Update(CtaPublicationChannel::topic($locale), $data));
        } catch (\Throwable $error) {
            throw CtaPublicationFailed::forLocale($locale, $error);
        }
    }
}
