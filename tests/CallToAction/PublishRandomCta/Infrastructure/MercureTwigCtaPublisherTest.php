<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\Infrastructure;

use App\CallToAction\PublishRandomCta\Application\CtaPublicationChannel;
use App\CallToAction\PublishRandomCta\Application\CtaPublicationFailed;
use App\CallToAction\PublishRandomCta\Infrastructure\MercureTwigCtaPublisher;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\Infrastructure\MercureTwigCtaPublisher
 */
final class MercureTwigCtaPublisherTest extends KernelTestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);
        $this->twig = $twig;
    }

    public function test_it_renders_the_existing_turbo_stream_and_publishes_it_to_the_existing_topic(): void
    {
        $updates = [];
        $hub = $this->createMock(HubInterface::class);
        $hub
            ->expects(self::once())
            ->method('publish')
            ->willReturnCallback(static function (Update $update) use (&$updates): string {
                $updates[] = $update;

                return 'publication-id';
            })
        ;

        new MercureTwigCtaPublisher($hub, $this->twig)->publish('<strong>CTA</strong>', 'pl');

        self::assertCount(1, $updates);
        self::assertSame([CtaPublicationChannel::topic('pl')], $updates[0]->getTopics());
        self::assertStringContainsString(
            sprintf('<turbo-stream action="update" target="%s">', CtaPublicationChannel::TARGET),
            $updates[0]->getData(),
        );
        self::assertStringContainsString(
            '<p class="fst-italic fs-2 text-accent mb-0">&lt;strong&gt;CTA&lt;/strong&gt;</p>',
            $updates[0]->getData(),
        );
    }

    public function test_it_maps_a_mercure_error_to_the_application_failure(): void
    {
        $transportError = new \RuntimeException('Mercure unavailable.');
        $hub = $this->createStub(HubInterface::class);
        $hub->method('publish')->willThrowException($transportError);

        try {
            new MercureTwigCtaPublisher($hub, $this->twig)->publish('CTA', 'en');
            self::fail('Expected publication failure was not thrown.');
        } catch (CtaPublicationFailed $error) {
            self::assertSame('CTA publication failed for locale "en".', $error->getMessage());
            self::assertSame($transportError, $error->getPrevious());
        }
    }
}
