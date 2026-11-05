<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Infrastructure;

use App\Contact\Shared\Infrastructure\Persistence\Doctrine\Entity\ContactMessage;
use App\Contact\SubmitContactMessage\Application\ContactMessageRepository;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageCommand;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineContactMessageRepository implements ContactMessageRepository
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function save(SubmitContactMessageCommand $command): SubmitContactMessageResult
    {
        $contactMessage = new ContactMessage();
        $contactMessage->setFullName($command->fullName);
        $contactMessage->setEmail($command->email);
        $contactMessage->setSubject($command->subject);
        $contactMessage->setMessage($command->message);

        $this->entityManager->persist($contactMessage);

        return new SubmitContactMessageResult(
            $contactMessage->getId()->toString(),
            $contactMessage->getCreatedAt(),
        );
    }
}
