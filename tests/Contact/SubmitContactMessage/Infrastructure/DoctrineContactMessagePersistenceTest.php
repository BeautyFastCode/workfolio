<?php

declare(strict_types=1);

namespace App\Tests\Contact\SubmitContactMessage\Infrastructure;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use App\Contact\SubmitContactMessage\Infrastructure\DoctrineContactMessageRepository;
use App\Contact\SubmitContactMessage\Infrastructure\DoctrineContactMessageTransaction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DoctrineContactMessagePersistenceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineContactMessageRepository $repository;
    private DoctrineContactMessageTransaction $transaction;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        new SchemaTool($this->entityManager)->createSchema($metadata);

        $this->repository = new DoctrineContactMessageRepository($this->entityManager);
        $this->transaction = new DoctrineContactMessageTransaction($this->entityManager);
    }

    public function test_it_commits_a_contact_message_using_the_existing_mapping(): void
    {
        $command = self::command();

        $result = $this->transaction->execute(
            fn (): SubmitContactMessageResult => $this->repository->save($command),
        );

        $this->entityManager->clear();
        $messages = $this->entityManager->getRepository(ContactMessage::class)->findAll();

        self::assertCount(1, $messages);
        $message = $messages[0];
        self::assertSame($result->contactMessageId, $message->getId()->toString());
        self::assertSame(
            $result->submittedAt->format('Y-m-d H:i:s'),
            $message->getCreatedAt()->format('Y-m-d H:i:s'),
        );
        self::assertSame($command->fullName, $message->getFullName());
        self::assertSame($command->email, $message->getEmail());
        self::assertSame($command->subject, $message->getSubject());
        self::assertSame($command->message, $message->getMessage());
    }

    public function test_it_rolls_back_the_persisted_message_when_the_operation_fails(): void
    {
        $failure = new \RuntimeException('Abort the transaction.');

        try {
            $this->transaction->execute(function () use ($failure): never {
                $this->repository->save(self::command());

                throw $failure;
            });
            self::fail('Expected the transaction to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        $this->entityManager->clear();
        self::assertCount(0, $this->entityManager->getRepository(ContactMessage::class)->findAll());
    }

    private static function command(): SubmitContactMessageCommand
    {
        return new SubmitContactMessageCommand(
            fullName: 'Ada Lovelace',
            email: 'ada@example.com',
            subject: 'Infrastructure test',
            message: 'A contact message persisted through the new adapter.',
        );
    }
}
