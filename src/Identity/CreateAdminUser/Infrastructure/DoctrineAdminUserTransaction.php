<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\AdminUserTransaction;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAdminUserTransaction implements AdminUserTransaction
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function execute(\Closure $operation): string
    {
        return $this->entityManager->wrapInTransaction(
            static fn (): string => $operation(),
        );
    }
}
