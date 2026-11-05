<?php

declare(strict_types=1);

namespace App\Tests\Identity\CreateAdminUser\Infrastructure;

use App\Identity\CreateAdminUser\Application\AdminUserConflict;
use App\Identity\CreateAdminUser\Application\CreateAdminUserCommand;
use App\Identity\CreateAdminUser\Application\CreateAdminUserHandler;
use App\Identity\CreateAdminUser\Application\NewAdminUser;
use App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserRepository;
use App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserTransaction;
use App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserUniquenessChecker;
use App\Identity\CreateAdminUser\Infrastructure\SymfonyAdminUserValidator;
use App\Identity\CreateAdminUser\Infrastructure\SymfonyPasswordHasher;
use App\Identity\Shared\Infrastructure\Persistence\Doctrine\Entity\AdminUser;
use App\Shared\Observability\Application\HandlerMetrics;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserRepository
 * @covers \App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserTransaction
 * @covers \App\Identity\CreateAdminUser\Infrastructure\DoctrineAdminUserUniquenessChecker
 * @covers \App\Identity\CreateAdminUser\Infrastructure\SymfonyAdminUserValidator
 * @covers \App\Identity\CreateAdminUser\Infrastructure\SymfonyPasswordHasher
 */
final class CreateAdminUserAdaptersTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DoctrineAdminUserRepository $repository;
    private DoctrineAdminUserTransaction $transaction;
    private DoctrineAdminUserUniquenessChecker $uniquenessChecker;
    private SymfonyAdminUserValidator $validator;
    private SymfonyPasswordHasher $passwordHasher;
    private UserPasswordHasherInterface $symfonyPasswordHasher;

    protected function setUp(): void
    {
        self::bootKernel(['debug' => true]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());

        $symfonyPasswordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $symfonyPasswordHasher);
        $this->symfonyPasswordHasher = $symfonyPasswordHasher;
        $this->repository = new DoctrineAdminUserRepository($entityManager);
        $this->transaction = new DoctrineAdminUserTransaction($entityManager);
        $this->uniquenessChecker = new DoctrineAdminUserUniquenessChecker($entityManager);
        $this->passwordHasher = new SymfonyPasswordHasher($symfonyPasswordHasher);
        $validator = self::getContainer()->get(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);
        $this->validator = new SymfonyAdminUserValidator($validator);
    }

    public function test_real_adapters_create_and_commit_an_enabled_administrator_with_a_valid_hash(): void
    {
        $metrics = $this->createStub(HandlerMetrics::class);
        $metrics
            ->method('measure')
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;
        $handler = new CreateAdminUserHandler(
            $this->validator,
            $this->uniquenessChecker,
            $this->passwordHasher,
            $this->repository,
            $this->transaction,
            $metrics,
        );

        $result = $handler(new CreateAdminUserCommand(
            email: 'admin@example.com',
            username: 'admin_user',
            plainPassword: 'Password1',
            roles: ['ROLE_ADMIN'],
        ));

        self::assertTrue($result->wasCreated());
        self::assertNotNull($result->administratorId);
        $this->entityManager->clear();
        $administrator = $this->entityManager->find(AdminUser::class, $result->administratorId);
        self::assertInstanceOf(AdminUser::class, $administrator);
        self::assertSame('admin@example.com', $administrator->getEmail());
        self::assertSame('admin_user', $administrator->getUsername());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], array_values($administrator->getRoles()));
        self::assertTrue($administrator->isEnabled());
        self::assertFalse($administrator->isDeleted());
        self::assertTrue($this->symfonyPasswordHasher->isPasswordValid($administrator, 'Password1'));
    }

    public function test_validator_checks_identity_and_roles_without_checking_password_strength(): void
    {
        self::assertSame([], $this->validator->validate(new CreateAdminUserCommand(
            email: 'admin@example.com',
            username: 'admin_user',
            plainPassword: 'weak',
            roles: ['ROLE_ADMIN'],
        )));

        $violations = $this->validator->validate(new CreateAdminUserCommand(
            email: 'x',
            username: 'invalid username',
            plainPassword: 'weak',
            roles: [],
        ));

        self::assertArrayHasKey('email', $violations);
        self::assertArrayHasKey('username', $violations);
        self::assertArrayHasKey('roles', $violations);
    }

    public function test_uniqueness_checker_returns_all_exact_conflicts_in_one_query(): void
    {
        $this->persistAdministrator('used@example.com', 'first_admin');
        $this->persistAdministrator('other@example.com', 'used_username');
        $debugDataHolder = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $debugDataHolder);
        $debugDataHolder->reset();

        $conflicts = $this->uniquenessChecker->findConflicts(
            'used@example.com',
            'used_username',
        );

        self::assertSame([AdminUserConflict::EMAIL, AdminUserConflict::USERNAME], $conflicts);

        /** @var array<string, list<array{sql: string}>> $queryData */
        $queryData = $debugDataHolder->getData();
        $queries = [];

        foreach ($queryData as $connectionQueries) {
            array_push($queries, ...$connectionQueries);
        }

        self::assertCount(1, $queries);
        self::assertStringContainsString('FROM app_admin_user ', $queries[0]['sql']);
        self::assertStringContainsString('email', $queries[0]['sql']);
        self::assertStringContainsString('username', $queries[0]['sql']);
    }

    public function test_transaction_rolls_back_a_pending_administrator_when_the_operation_fails(): void
    {
        $failure = new \RuntimeException('Operation failed.');

        try {
            $this->transaction->execute(function () use ($failure): never {
                $this->repository->save(new NewAdminUser(
                    email: 'rollback@example.com',
                    username: 'rollback_admin',
                    passwordHash: 'password-hash',
                    roles: ['ROLE_USER'],
                    enabled: true,
                ));

                throw $failure;
            });
            self::fail('Expected the transaction operation to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        $count = $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM app_admin_user WHERE email = :email',
            ['email' => 'rollback@example.com'],
        );
        self::assertTrue(is_int($count) || is_string($count));
        self::assertSame(0, (int) $count);
    }

    private function persistAdministrator(string $email, string $username): void
    {
        $administrator = new AdminUser();
        $administrator->setEmail($email);
        $administrator->setUsername($username);
        $administrator->setPassword('password-hash');
        $administrator->setRoles(['ROLE_USER']);
        $this->entityManager->persist($administrator);
        $this->entityManager->flush();
    }
}
