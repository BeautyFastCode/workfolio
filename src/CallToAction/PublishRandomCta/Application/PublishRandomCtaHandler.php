<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\Application;

use App\Shared\Observability\Application\HandlerMetrics;

final readonly class PublishRandomCtaHandler
{
    private const string METRIC_NAME = 'call_to_action.publish_random_cta';

    public function __construct(
        private RandomCtaFinder $ctaFinder,
        private CtaPublisher $ctaPublisher,
        private HandlerMetrics $metrics,
    ) {}

    public function __invoke(PublishRandomCtaCommand $command): PublishRandomCtaResult
    {
        return $this->metrics->measure(
            self::METRIC_NAME,
            fn (): PublishRandomCtaResult => $this->handle($command),
        );
    }

    private function handle(PublishRandomCtaCommand $command): PublishRandomCtaResult
    {
        $cta = $this->ctaFinder->findRandom();

        if (!$cta instanceof RandomCta) {
            return PublishRandomCtaResult::messageNotFound();
        }

        foreach ($cta->translations as $translation) {
            $this->ctaPublisher->publish($translation->message, $translation->locale);
        }

        return PublishRandomCtaResult::published($cta->displayName);
    }
}
