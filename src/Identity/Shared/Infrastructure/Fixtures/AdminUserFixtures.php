<?php

declare(strict_types=1);

namespace App\Identity\Shared\Infrastructure\Fixtures;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminUserFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $data = [
            [
                'email' => 'super@example.com',
                'username' => 'super',
                'roles' => ['ROLE_SUPER_ADMIN'],
                'plainPassword' => 'super123!',
                'enabled' => true,
            ],
            [
                'email' => 'admin@example.com',
                'username' => 'admin',
                'roles' => ['ROLE_ADMIN'],
                'plainPassword' => 'admin123!',
                'enabled' => true,
            ],
            [
                'email' => 'user@example.com',
                'username' => 'user',
                'roles' => ['ROLE_USER'],
                'plainPassword' => 'user123!',
                'enabled' => true,
            ],
            [
                'email' => 'moderator@example.com',
                'username' => 'moderator',
                'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
                'plainPassword' => 'moderator123!',
                'enabled' => false,
            ],
            [
                'email' => 'support@example.com',
                'username' => 'support',
                'roles' => ['ROLE_USER'],
                'plainPassword' => 'support123!',
                'enabled' => true,
            ],
        ];

        foreach ($data as $datum) {
            $adminUser = new AdminUser();
            $adminUser->setEmail($datum['email']);
            $adminUser->setUsername($datum['username']);
            $adminUser->setRoles($datum['roles']);
            $adminUser->setEnabled($datum['enabled']);

            $manager->persist($adminUser);

            $hashedPassword = $this->userPasswordHasher->hashPassword(
                $adminUser,
                $datum['plainPassword']
            );

            $adminUser->setPassword($hashedPassword);
            $adminUser->eraseCredentials();
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['admin_user'];
    }
}
