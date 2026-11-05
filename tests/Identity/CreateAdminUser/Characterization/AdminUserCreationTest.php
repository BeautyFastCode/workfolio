<?php

declare(strict_types=1);

namespace App\Tests\Identity\CreateAdminUser\Characterization;

use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @covers \App\Identity\CreateAdminUser\UI\AdminUserCreateCommand
 * @covers \App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser
 */
final class AdminUserCreationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    /**
     * @var ObjectRepository<AdminUser>
     */
    private ObjectRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $repository = $entityManager->getRepository(AdminUser::class);
        $this->repository = $repository;
    }

    public function test_interactive_command_creates_an_enabled_admin_with_selected_roles(): void
    {
        $tester = $this->commandTester();
        $tester->setInputs([
            'admin@example.com',
            'admin_user',
            'Password1',
            '1,2',
            'yes',
        ]);

        $status = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertStringContainsString('Admin user has been successfully created.', $tester->getDisplay());
        $administrator = $this->repository->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(AdminUser::class, $administrator);
        self::assertSame('admin_user', $administrator->getUsername());
        self::assertSame(
            ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_USER'],
            array_values($administrator->getRoles()),
        );
        self::assertTrue($administrator->isEnabled());
        self::assertFalse($administrator->isDeleted());

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);
        self::assertTrue($hasher->isPasswordValid($administrator, 'Password1'));
    }

    public function test_duplicate_email_is_rejected_without_creating_another_user(): void
    {
        $this->createAdministrator('admin@example.com', 'first_admin');
        $tester = $this->commandTester();
        $tester->setInputs([
            'admin@example.com',
            'second_admin',
            'Password1',
            '0',
            'yes',
        ]);

        $status = $tester->execute([]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('email:', $tester->getDisplay());
        self::assertCount(1, $this->repository->findAll());
        self::assertNull($this->repository->findOneBy(['username' => 'second_admin']));
    }

    public function test_non_interactive_execution_is_rejected_without_persistence(): void
    {
        $tester = $this->commandTester();

        $status = $tester->execute([], ['interactive' => false]);

        self::assertSame(Command::FAILURE, $status);
        self::assertStringContainsString('This command must be run interactively.', $tester->getDisplay());
        self::assertCount(0, $this->repository->findAll());
    }

    public function test_rejected_confirmation_aborts_without_persistence(): void
    {
        $tester = $this->commandTester();
        $tester->setInputs([
            'admin@example.com',
            'admin_user',
            'Password1',
            '0',
            'no',
        ]);

        $status = $tester->execute([]);

        self::assertSame(Command::INVALID, $status);
        self::assertStringContainsString('Admin user creation has been aborted.', $tester->getDisplay());
        self::assertCount(0, $this->repository->findAll());
    }

    public function test_soft_delete_changes_unique_identifiers_and_disables_the_user(): void
    {
        $administrator = new AdminUser();
        $administrator->setEmail('admin@example.com');
        $administrator->setUsername('admin_user');

        $administrator->softDelete();

        self::assertMatchesRegularExpression(
            '/^admin@example\.com__deleted_[a-z0-9]{8}$/',
            $administrator->getEmail(),
        );
        self::assertMatchesRegularExpression(
            '/^admin_user__deleted_[a-z0-9]{8}$/',
            $administrator->getUsername(),
        );
        self::assertFalse($administrator->isEnabled());
        self::assertTrue($administrator->isDeleted());
        self::assertInstanceOf(\DateTimeImmutable::class, $administrator->getDeletedAt());
    }

    private function commandTester(): CommandTester
    {
        $kernel = self::$kernel;
        self::assertInstanceOf(KernelInterface::class, $kernel);
        $command = new Application($kernel)->find('app:admin-user:create');

        return new CommandTester($command);
    }

    private function createAdministrator(string $email, string $username): void
    {
        $administrator = new AdminUser();
        $administrator->setEmail($email);
        $administrator->setUsername($username);
        $administrator->setPassword('hashed-password');
        $administrator->setRoles(['ROLE_USER']);
        $this->entityManager->persist($administrator);
        $this->entityManager->flush();
    }
}
