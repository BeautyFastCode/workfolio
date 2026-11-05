<?php

declare(strict_types=1);

namespace App\Tests\CallToAction\PublishRandomCta\UI;

use App\CallToAction\PublishRandomCta\Application\CtaPublisher;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaHandler as ApplicationHandler;
use App\CallToAction\PublishRandomCta\Application\RandomCta;
use App\CallToAction\PublishRandomCta\Application\RandomCtaFinder;
use App\CallToAction\PublishRandomCta\Application\RandomCtaTranslation;
use App\CallToAction\PublishRandomCta\UI\Cli\CtaMessageCommand;
use App\CallToAction\PublishRandomCta\UI\Scheduler\PublishCtaMessage;
use App\CallToAction\PublishRandomCta\UI\Scheduler\PublishCtaMessageHandler;
use App\Shared\Observability\Application\HandlerMetrics;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\UuidV7;

/**
 * @internal
 *
 * @covers \App\CallToAction\PublishRandomCta\UI\Cli\CtaMessageCommand
 * @covers \App\CallToAction\PublishRandomCta\UI\Scheduler\PublishCtaMessage
 * @covers \App\CallToAction\PublishRandomCta\UI\Scheduler\PublishCtaMessageHandler
 */
final class PublishRandomCtaInputAdaptersTest extends TestCase
{
    public function test_cli_and_scheduler_publish_the_same_translations_through_the_application_handler(): void
    {
        $cliPublications = [];
        $schedulerPublications = [];

        $tester = new CommandTester(new CtaMessageCommand($this->applicationHandler(
            $this->cta(),
            $cliPublications,
        )));
        $statusCode = $tester->execute([]);

        $schedulerHandler = new PublishCtaMessageHandler(
            $this->createStub(LoggerInterface::class),
            $this->applicationHandler($this->cta(), $schedulerPublications),
        );
        $schedulerHandler(new PublishCtaMessage());

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertSame([
            ['en', 'Characteristic CTA in English.'],
            ['pl', 'Charakterystyczne CTA po polsku.'],
        ], $cliPublications);
        self::assertSame($cliPublications, $schedulerPublications);
    }

    public function test_cli_maps_the_application_results_to_the_existing_output_and_exit_codes(): void
    {
        $publications = [];
        $successTester = new CommandTester(new CtaMessageCommand($this->applicationHandler(
            $this->cta(),
            $publications,
        )));

        self::assertSame(Command::SUCCESS, $successTester->execute([]));
        self::assertStringContainsString('app:publish:cta-message', $successTester->getDisplay());
        self::assertStringContainsString(
            'Random message published: Characteristic CTA in English.',
            $successTester->getDisplay(),
        );
        self::assertStringContainsString('[OK] DONE', $successTester->getDisplay());

        $missingTester = new CommandTester(new CtaMessageCommand($this->applicationHandler(null, $publications)));

        self::assertSame(Command::FAILURE, $missingTester->execute([]));
        self::assertStringContainsString('app:publish:cta-message', $missingTester->getDisplay());
        self::assertStringContainsString('[ERROR] Message not found.', $missingTester->getDisplay());
    }

    public function test_messenger_handler_keeps_message_ids_and_existing_logs_in_the_ui_adapter(): void
    {
        $publications = [];
        $infoLogs = [];
        $message = new PublishCtaMessage();
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $logMessage, array $context) use (&$infoLogs): void {
                $infoLogs[] = [$logMessage, $context];
            })
        ;
        $logger->expects(self::never())->method('error');

        (new PublishCtaMessageHandler($logger, $this->applicationHandler($this->cta(), $publications)))($message);

        self::assertSame([
            ['PublishCtaMessage. Handle message.', ['id' => $message->getId()->toString()]],
            [
                'PublishCtaMessage. Random message published.',
                [
                    'id' => $message->getId()->toString(),
                    'ctaMessage' => 'Characteristic CTA in English.',
                ],
            ],
        ], $infoLogs);
        self::assertInstanceOf(UuidV7::class, $message->getId());
    }

    public function test_messenger_handler_logs_the_existing_error_for_the_explicit_missing_result(): void
    {
        $publications = [];
        $message = new PublishCtaMessage();
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('info')
            ->with('PublishCtaMessage. Handle message.', ['id' => $message->getId()->toString()])
        ;
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('Message not found.')
        ;

        (new PublishCtaMessageHandler($logger, $this->applicationHandler(null, $publications)))($message);

        self::assertSame([], $publications);
    }

    /**
     * @param list<array{string, string}> $publications
     */
    private function applicationHandler(?RandomCta $cta, array &$publications): ApplicationHandler
    {
        $finder = $this->createStub(RandomCtaFinder::class);
        $finder->method('findRandom')->willReturn($cta);
        $publisher = $this->createStub(CtaPublisher::class);
        $publisher
            ->method('publish')
            ->willReturnCallback(static function (string $message, string $locale) use (&$publications): void {
                $publications[] = [$locale, $message];
            })
        ;

        $metrics = $this->createStub(HandlerMetrics::class);
        $metrics
            ->method('measure')
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;

        return new ApplicationHandler($finder, $publisher, $metrics);
    }

    private function cta(): RandomCta
    {
        return new RandomCta('Characteristic CTA in English.', [
            new RandomCtaTranslation('en', 'Characteristic CTA in English.'),
            new RandomCtaTranslation('pl', 'Charakterystyczne CTA po polsku.'),
        ]);
    }
}
