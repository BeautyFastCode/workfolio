<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\UI\Scheduler;

use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaCommand;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaHandler as ApplicationHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishCtaMessageHandler
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.scheduler')]
        private LoggerInterface $logger,
        private ApplicationHandler $handler,
    ) {}

    public function __invoke(PublishCtaMessage $message): void
    {
        $id = $message->getId()->toString();

        $this->logger->info('PublishCtaMessage. Handle message.', [
            'id' => $id,
        ]);

        $result = ($this->handler)(new PublishRandomCtaCommand());

        if (!$result->messageFound) {
            $this->logger->error('Message not found.');

            return;
        }

        $this->logger->info('PublishCtaMessage. Random message published.', [
            'id' => $id,
            'ctaMessage' => $result->publishedMessage,
        ]);
    }
}
