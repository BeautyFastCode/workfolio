<?php

declare(strict_types=1);

namespace App\Identity\AuthenticateAdmin\Infrastructure;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<AdminUser>
 */
final readonly class DoctrineAdminUserProvider implements UserProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $identifier = mb_strtolower(trim($identifier));

        $user = $this->entityManager->createQueryBuilder()
            ->select('adminUser')
            ->from(AdminUser::class, 'adminUser')
            ->andWhere('LOWER(adminUser.email) = :identifier OR LOWER(adminUser.username) = :identifier')
            ->setParameter('identifier', $identifier)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$user instanceof AdminUser) {
            $exception = new UserNotFoundException('Admin user not found.');
            $exception->setUserIdentifier($identifier);

            throw $exception;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                get_debug_type($user),
            ));
        }

        $refreshedUser = $this->entityManager->find(AdminUser::class, $user->getId());

        if (!$refreshedUser instanceof AdminUser) {
            $exception = new UserNotFoundException('Admin user not found.');
            $exception->setUserIdentifier($user->getUserIdentifier());

            throw $exception;
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return $class === AdminUser::class || is_subclass_of($class, AdminUser::class);
    }
}
