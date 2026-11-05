<?php

declare(strict_types=1);

namespace App\Contact\SubmitContactMessage\Infrastructure;

use App\Contact\SubmitContactMessage\Application\ContactMessageTransaction;
use App\Contact\SubmitContactMessage\Application\SubmitContactMessageResult;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineContactMessageTransaction implements ContactMessageTransaction
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function execute(\Closure $operation): SubmitContactMessageResult
    {
        return $this->entityManager->wrapInTransaction(
            static fn (): SubmitContactMessageResult => $operation(),
        );
    }
}
