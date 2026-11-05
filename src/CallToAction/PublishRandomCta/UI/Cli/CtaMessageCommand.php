<?php

declare(strict_types=1);

namespace App\CallToAction\PublishRandomCta\UI\Cli;

use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaCommand;
use App\CallToAction\PublishRandomCta\Application\PublishRandomCtaHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:publish:cta-message',
    description: 'Randomly select a message and publish it on the home page.',
)]
final class CtaMessageCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly PublishRandomCtaHandler $handler,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->getName()) {
            $this->io->title($this->getName());
        }

        $result = ($this->handler)(new PublishRandomCtaCommand());

        if (!$result->messageFound) {
            $this->io->error('Message not found.');

            return Command::FAILURE;
        }

        $this->io->text('Random message published: '.$result->publishedMessage);
        $this->io->success('DONE');

        return Command::SUCCESS;
    }
}
