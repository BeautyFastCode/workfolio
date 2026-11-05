<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\AdminUserConflict;
use App\Identity\CreateAdminUser\Application\AdminUserUniquenessChecker;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAdminUserUniquenessChecker implements AdminUserUniquenessChecker
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function findConflicts(string $email, string $username): array
    {
        /** @var list<array{email: string, username: string}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('administrator.email AS email')
            ->addSelect('administrator.username AS username')
            ->from(AdminUser::class, 'administrator')
            ->where('administrator.email = :email')
            ->orWhere('administrator.username = :username')
            ->setParameter('email', $email)
            ->setParameter('username', $username)
            ->getQuery()
            ->getArrayResult()
        ;

        $conflicts = [];

        foreach ($rows as $row) {
            if ($row['email'] === $email && !in_array(AdminUserConflict::EMAIL, $conflicts, true)) {
                $conflicts[] = AdminUserConflict::EMAIL;
            }

            if ($row['username'] === $username && !in_array(AdminUserConflict::USERNAME, $conflicts, true)) {
                $conflicts[] = AdminUserConflict::USERNAME;
            }
        }

        return array_values($conflicts);
    }
}
