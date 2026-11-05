<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\AdminUserRepository;
use App\Identity\CreateAdminUser\Application\NewAdminUser;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAdminUserRepository implements AdminUserRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function save(NewAdminUser $administrator): string
    {
        $entity = new AdminUser();
        $entity->setEmail($administrator->email);
        $entity->setUsername($administrator->username);
        $entity->setPassword($administrator->passwordHash);
        $entity->setRoles($administrator->roles);
        $entity->setEnabled($administrator->enabled);
        $this->entityManager->persist($entity);

        return $entity->getId()->toString();
    }
}
