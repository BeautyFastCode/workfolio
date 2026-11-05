<?php

declare(strict_types=1);

namespace App\Tests\Identity\Shared\Infrastructure\Fixtures;

use App\Identity\Shared\Infrastructure\Fixtures\AdminUserFixtures;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\Bundle\FixturesBundle\Loader\FixturesProvider;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdminUserFixturesTest extends KernelTestCase
{
    public function test_fixture_remains_registered_with_the_same_group_hashing_and_data(): void
    {
        self::bootKernel();

        $fixturesProvider = self::getContainer()->get('doctrine.fixtures.loader');
        self::assertInstanceOf(FixturesProvider::class, $fixturesProvider);

        $fixtures = $fixturesProvider->getFixtures(['admin_user']);
        self::assertCount(1, $fixtures);
        self::assertInstanceOf(AdminUserFixtures::class, $fixtures[0]);
        self::assertSame(['admin_user'], AdminUserFixtures::getGroups());

        $hashedInputs = [];
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher
            ->expects($this->exactly(5))
            ->method('hashPassword')
            ->willReturnCallback(static function (AdminUser $user, string $plainPassword) use (&$hashedInputs): string {
                $hashedInputs[] = [$user->getUsername(), $plainPassword];

                return 'hashed-'.$plainPassword;
            })
        ;

        $users = [];
        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects($this->exactly(5))
            ->method('persist')
            ->willReturnCallback(static function (AdminUser $user) use (&$users): void {
                $users[] = $user;
            })
        ;
        $manager->expects($this->once())->method('flush');

        new AdminUserFixtures($hasher)->load($manager);

        self::assertSame([
            ['super', 'super123!'],
            ['admin', 'admin123!'],
            ['user', 'user123!'],
            ['moderator', 'moderator123!'],
            ['support', 'support123!'],
        ], $hashedInputs);
        self::assertSame([
            ['super@example.com', 'super', ['ROLE_SUPER_ADMIN', 'ROLE_USER'], true, 'hashed-super123!'],
            ['admin@example.com', 'admin', ['ROLE_ADMIN', 'ROLE_USER'], true, 'hashed-admin123!'],
            ['user@example.com', 'user', ['ROLE_USER'], true, 'hashed-user123!'],
            ['moderator@example.com', 'moderator', ['ROLE_USER', 'ROLE_ADMIN'], false, 'hashed-moderator123!'],
            ['support@example.com', 'support', ['ROLE_USER'], true, 'hashed-support123!'],
        ], array_map(
            static fn (AdminUser $user): array => [
                $user->getEmail(),
                $user->getUsername(),
                array_values($user->getRoles()),
                $user->isEnabled(),
                $user->getPassword(),
            ],
            $users,
        ));
        self::assertSame([null, null, null, null, null], array_map(
            static fn (AdminUser $user): ?string => $user->getPlainPassword(),
            $users,
        ));
    }
}
