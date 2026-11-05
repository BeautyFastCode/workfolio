<?php

declare(strict_types=1);

namespace App\Tests\Identity\AuthenticateAdmin\Infrastructure;

use App\Identity\AuthenticateAdmin\Infrastructure\DoctrineAdminUserProvider;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @internal
 *
 * @covers \App\Identity\AuthenticateAdmin\Infrastructure\DoctrineAdminUserProvider
 */
final class DoctrineAdminUserProviderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineAdminUserProvider $provider;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $provider = self::getContainer()->get(DoctrineAdminUserProvider::class);
        self::assertInstanceOf(DoctrineAdminUserProvider::class, $provider);
        $this->provider = $provider;

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        new SchemaTool($this->entityManager)->createSchema($metadata);
    }

    public function test_loads_administrator_by_normalized_email_or_username(): void
    {
        $administrator = $this->createAdministrator();

        self::assertSame(
            $administrator,
            $this->provider->loadUserByIdentifier(' ADMIN@EXAMPLE.COM '),
        );
        self::assertSame(
            $administrator,
            $this->provider->loadUserByIdentifier(' ADMIN '),
        );
    }

    public function test_missing_administrator_is_reported_with_normalized_identifier(): void
    {
        try {
            $this->provider->loadUserByIdentifier(' MISSING ');
            self::fail('Expected the provider to reject an unknown administrator.');
        } catch (UserNotFoundException $exception) {
            self::assertSame('missing', $exception->getUserIdentifier());
        }
    }

    public function test_refreshes_supported_administrator_by_primary_key(): void
    {
        $administrator = $this->createAdministrator();
        $this->entityManager->clear();

        $refreshedAdministrator = $this->provider->refreshUser($administrator);

        self::assertNotSame($administrator, $refreshedAdministrator);
        self::assertSame(
            $administrator->getId()->toRfc4122(),
            $refreshedAdministrator->getId()->toRfc4122(),
        );
        self::assertTrue($this->provider->supportsClass(AdminUser::class));
        self::assertFalse($this->provider->supportsClass(UnsupportedSecurityUser::class));
    }

    public function test_rejects_refreshing_an_unsupported_user(): void
    {
        $this->expectException(UnsupportedUserException::class);

        $this->provider->refreshUser(new UnsupportedSecurityUser());
    }

    public function test_reports_administrator_removed_before_refresh(): void
    {
        $administrator = $this->createAdministrator();
        $this->entityManager->remove($administrator);
        $this->entityManager->flush();

        $this->expectException(UserNotFoundException::class);

        $this->provider->refreshUser($administrator);
    }

    private function createAdministrator(): AdminUser
    {
        $administrator = new AdminUser();
        $administrator->setEmail('admin@example.com');
        $administrator->setUsername('admin');
        $administrator->setRoles(['ROLE_ADMIN']);
        $administrator->setEnabled(true);
        $administrator->setPassword('password-hash');

        $this->entityManager->persist($administrator);
        $this->entityManager->flush();

        return $administrator;
    }
}

final class UnsupportedSecurityUser implements UserInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function eraseCredentials(): void {}

    public function getUserIdentifier(): string
    {
        return 'unsupported';
    }
}
