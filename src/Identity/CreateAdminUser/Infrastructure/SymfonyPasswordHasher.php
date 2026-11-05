<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\PasswordHasher;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function hash(string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new AdminUser(), $plainPassword);
    }
}
