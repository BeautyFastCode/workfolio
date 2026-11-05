<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\Application;

use App\CallToAction\PublishRandomCta\Application\CtaPublicationFailed;
use App\CallToAction\PublishRandomCta\Application\CtaPublisher;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaCommand;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaHandler;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaResult;
use App\CallToAction\PublishRandomCta\Application\RandomCta;
use App\CallToAction\PublishRandomCta\Application\RandomCtaFinder;
use App\CallToAction\PublishRandomCta\Application\RandomCtaTranslation;
use App\Shared\Observability\Application\HandlerMetrics;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\Application\CtaPublicationFailed
 * @covers \App\CallToAction\PublishRandomCta\Application\PublishRandomCtaCommand
 * @covers \App\CallToAction\PublishRandomCta\Application\PublishRandomCtaHandler
 * @covers \App\CallToAction\PublishRandomCta\Application\PublishRandomCtaResult
 * @covers \App\CallToAction\PublishRandomCta\Application\RandomCta
 * @covers \App\CallToAction\PublishRandomCta\Application\RandomCtaTranslation
 */
final class PublishRandomCtaHandlerTest extends TestCase
{
    public function test_it_publishes_all_translations_in_the_order_returned_by_the_finder(): void
    {
        $cta = new RandomCta('English CTA', [
            new RandomCtaTranslation('en', 'English CTA'),
            new RandomCtaTranslation('pl', 'Polskie CTA'),
            new RandomCtaTranslation('de', 'Deutscher CTA'),
        ]);
        $finder = $this->createStub(RandomCtaFinder::class);
        $finder->method('findRandom')->willReturn($cta);
        $published = [];
        $publisher = $this->createMock(CtaPublisher::class);
        $publisher
            ->expects(self::exactly(3))
            ->method('publish')
            ->willReturnCallback(static function (string $message, string $locale) use (&$published): void {
                $published[] = [$locale, $message];
            })
        ;

        $metrics = $this->createMock(HandlerMetrics::class);
        $metrics
            ->expects(self::once())
            ->method('measure')
            ->with('call_to_action.publish_random_cta', self::isCallable())
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;
        $result = (new PublishRandomCtaHandler($finder, $publisher, $metrics))(
            new PublishRandomCtaCommand(),
        );

        self::assertEquals(PublishRandomCtaResult::published('English CTA'), $result);
        self::assertSame([
            ['en', 'English CTA'],
            ['pl', 'Polskie CTA'],
            ['de', 'Deutscher CTA'],
        ], $published);
    }

    public function test_it_returns_an_explicit_result_when_no_message_exists(): void
    {
        $finder = $this->createStub(RandomCtaFinder::class);
        $finder->method('findRandom')->willReturn(null);
        $publisher = $this->createMock(CtaPublisher::class);
        $publisher->expects(self::never())->method('publish');

        $result = (new PublishRandomCtaHandler($finder, $publisher, $this->passThroughMetrics()))(
            new PublishRandomCtaCommand(),
        );

        self::assertEquals(PublishRandomCtaResult::messageNotFound(), $result);
        self::assertFalse($result->messageFound);
        self::assertNull($result->publishedMessage);
    }

    public function test_it_propagates_an_explicit_publication_failure_and_stops_later_publications(): void
    {
        $failure = CtaPublicationFailed::forLocale('en', new \RuntimeException('Hub unavailable.'));
        $finder = $this->createStub(RandomCtaFinder::class);
        $finder->method('findRandom')->willReturn(new RandomCta('English CTA', [
            new RandomCtaTranslation('en', 'English CTA'),
            new RandomCtaTranslation('pl', 'Polskie CTA'),
        ]));
        $publisher = $this->createMock(CtaPublisher::class);
        $publisher
            ->expects(self::once())
            ->method('publish')
            ->with('English CTA', 'en')
            ->willThrowException($failure)
        ;

        try {
            (new PublishRandomCtaHandler($finder, $publisher, $this->passThroughMetrics()))(
                new PublishRandomCtaCommand(),
            );
            self::fail('Expected publication failure was not thrown.');
        } catch (CtaPublicationFailed $error) {
            self::assertSame($failure, $error);
            self::assertSame('CTA publication failed for locale "en".', $error->getMessage());
            self::assertSame('Hub unavailable.', $error->getPrevious()?->getMessage());
        }
    }

    private function passThroughMetrics(): HandlerMetrics
    {
        $metrics = $this->createStub(HandlerMetrics::class);
        $metrics
            ->method('measure')
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;

        return $metrics;
    }
}
