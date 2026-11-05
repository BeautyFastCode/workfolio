<?php

declare(strict_types=1);

namespace App\Tests\Identity\CreateAdminUser\Application;

use App\Identity\CreateAdminUser\Application\AdminUserConflict;
use App\Identity\CreateAdminUser\Application\AdminUserPersistenceFailed;
use App\Identity\CreateAdminUser\Application\AdminUserRepository;
use App\Identity\CreateAdminUser\Application\AdminUserTransaction;
use App\Identity\CreateAdminUser\Application\AdminUserUniquenessChecker;
use App\Identity\CreateAdminUser\Application\AdminUserValidator;
use App\Identity\CreateAdminUser\Application\CreateAdminUserCommand;
use App\Identity\CreateAdminUser\Application\CreateAdminUserHandler;
use App\Identity\CreateAdminUser\Application\CreateAdminUserResult;
use App\Identity\CreateAdminUser\Application\InvalidAdminUser;
use App\Identity\CreateAdminUser\Application\NewAdminUser;
use App\Identity\CreateAdminUser\Application\PasswordHasher;
use App\Shared\Observability\Application\HandlerMetrics;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Identity\CreateAdminUser\Application\AdminUserConflict
 * @covers \App\Identity\CreateAdminUser\Application\CreateAdminUserCommand
 * @covers \App\Identity\CreateAdminUser\Application\CreateAdminUserHandler
 * @covers \App\Identity\CreateAdminUser\Application\CreateAdminUserResult
 * @covers \App\Identity\CreateAdminUser\Application\NewAdminUser
 */
final class CreateAdminUserHandlerTest extends TestCase
{
    public function test_it_checks_uniqueness_hashes_the_password_and_saves_an_enabled_administrator(): void
    {
        $command = self::command();
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn([]);
        $uniquenessChecker = $this->createMock(AdminUserUniquenessChecker::class);
        $uniquenessChecker
            ->expects(self::once())
            ->method('findConflicts')
            ->with('admin@example.com', 'admin_user')
            ->willReturn([])
        ;
        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher
            ->expects(self::once())
            ->method('hash')
            ->with('Password1')
            ->willReturn('password-hash')
        ;
        $repository = $this->createMock(AdminUserRepository::class);
        $repository
            ->expects(self::once())
            ->method('save')
            ->with(new NewAdminUser(
                email: 'admin@example.com',
                username: 'admin_user',
                passwordHash: 'password-hash',
                roles: ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'],
                enabled: true,
            ))
            ->willReturn('0198bb71-a6a1-78b6-9188-218eac05b02c')
        ;
        $transaction = $this->createMock(AdminUserTransaction::class);
        $transaction
            ->expects(self::once())
            ->method('execute')
            ->willReturnCallback(static function (\Closure $operation): string {
                $result = $operation();
                self::assertIsString($result);

                return $result;
            })
        ;

        $metrics = $this->createMock(HandlerMetrics::class);
        $metrics
            ->expects(self::once())
            ->method('measure')
            ->with('identity.create_admin_user', self::isCallable())
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;
        $result = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $metrics,
        )($command);

        self::assertEquals(
            CreateAdminUserResult::created('0198bb71-a6a1-78b6-9188-218eac05b02c'),
            $result,
        );
        self::assertTrue($result->wasCreated());
    }

    public function test_it_returns_all_conflicts_without_hashing_or_saving(): void
    {
        $conflicts = [AdminUserConflict::EMAIL, AdminUserConflict::USERNAME];
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn([]);
        $uniquenessChecker = $this->createStub(AdminUserUniquenessChecker::class);
        $uniquenessChecker->method('findConflicts')->willReturn($conflicts);
        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher->expects(self::never())->method('hash');
        $repository = $this->createMock(AdminUserRepository::class);
        $repository->expects(self::never())->method('save');
        $transaction = $this->createMock(AdminUserTransaction::class);
        $transaction->expects(self::never())->method('execute');

        $result = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $this->passThroughMetrics(),
        )(self::command());

        self::assertEquals(CreateAdminUserResult::conflicted($conflicts), $result);
        self::assertFalse($result->wasCreated());
    }

    public function test_it_does_not_save_when_password_hashing_fails(): void
    {
        $hashingFailure = new \RuntimeException('Hashing failed.');
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn([]);
        $uniquenessChecker = $this->createStub(AdminUserUniquenessChecker::class);
        $uniquenessChecker->method('findConflicts')->willReturn([]);
        $passwordHasher = $this->createStub(PasswordHasher::class);
        $passwordHasher->method('hash')->willThrowException($hashingFailure);
        $repository = $this->createMock(AdminUserRepository::class);
        $repository->expects(self::never())->method('save');
        $transaction = $this->createMock(AdminUserTransaction::class);
        $transaction->expects(self::never())->method('execute');
        $handler = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $this->passThroughMetrics(),
        );

        $this->expectExceptionObject($hashingFailure);

        $handler(self::command());
    }

    public function test_it_wraps_a_persistence_failure(): void
    {
        $persistenceFailure = new \RuntimeException('Persistence failed.');
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn([]);
        $uniquenessChecker = $this->createStub(AdminUserUniquenessChecker::class);
        $uniquenessChecker->method('findConflicts')->willReturn([]);
        $passwordHasher = $this->createStub(PasswordHasher::class);
        $passwordHasher->method('hash')->willReturn('password-hash');
        $repository = $this->createStub(AdminUserRepository::class);
        $repository->method('save')->willThrowException($persistenceFailure);
        $transaction = $this->createStub(AdminUserTransaction::class);
        $transaction
            ->method('execute')
            ->willReturnCallback(static function (\Closure $operation): string {
                $result = $operation();
                self::assertIsString($result);

                return $result;
            })
        ;
        $handler = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $this->passThroughMetrics(),
        );

        try {
            $handler(self::command());
            self::fail('Expected a persistence failure.');
        } catch (AdminUserPersistenceFailed $exception) {
            self::assertSame($persistenceFailure, $exception->getPrevious());
        }
    }

    public function test_it_wraps_a_transaction_commit_failure(): void
    {
        $transactionFailure = new \RuntimeException('Commit failed.');
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn([]);
        $uniquenessChecker = $this->createStub(AdminUserUniquenessChecker::class);
        $uniquenessChecker->method('findConflicts')->willReturn([]);
        $passwordHasher = $this->createStub(PasswordHasher::class);
        $passwordHasher->method('hash')->willReturn('password-hash');
        $repository = $this->createMock(AdminUserRepository::class);
        $repository->expects(self::once())->method('save')->willReturn('administrator-id');
        $transaction = $this->createStub(AdminUserTransaction::class);
        $transaction
            ->method('execute')
            ->willReturnCallback(static function (\Closure $operation) use ($transactionFailure): never {
                $operation();

                throw $transactionFailure;
            })
        ;
        $handler = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $this->passThroughMetrics(),
        );

        try {
            $handler(self::command());
            self::fail('Expected a transaction failure.');
        } catch (AdminUserPersistenceFailed $exception) {
            self::assertSame($transactionFailure, $exception->getPrevious());
        }
    }

    public function test_it_rejects_invalid_data_before_checking_uniqueness(): void
    {
        $violations = [
            'username' => ['This value is too short.'],
            'roles' => ['Select at least one role.'],
        ];
        $validator = $this->createStub(AdminUserValidator::class);
        $validator->method('validate')->willReturn($violations);
        $uniquenessChecker = $this->createMock(AdminUserUniquenessChecker::class);
        $uniquenessChecker->expects(self::never())->method('findConflicts');
        $passwordHasher = $this->createMock(PasswordHasher::class);
        $passwordHasher->expects(self::never())->method('hash');
        $repository = $this->createMock(AdminUserRepository::class);
        $repository->expects(self::never())->method('save');
        $transaction = $this->createMock(AdminUserTransaction::class);
        $transaction->expects(self::never())->method('execute');
        $handler = new CreateAdminUserHandler(
            $validator,
            $uniquenessChecker,
            $passwordHasher,
            $repository,
            $transaction,
            $this->passThroughMetrics(),
        );

        try {
            $handler(self::command());
            self::fail('Expected invalid administrator data.');
        } catch (InvalidAdminUser $exception) {
            self::assertSame($violations, $exception->violations);
        }
    }

    private static function command(): CreateAdminUserCommand
    {
        return new CreateAdminUserCommand(
            email: 'admin@example.com',
            username: 'admin_user',
            plainPassword: 'Password1',
            roles: ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'],
        );
    }

    private function passThroughMetrics(): HandlerMetrics
    {
        $metrics = $this->createStub(HandlerMetrics::class);
        $metrics
            ->method('measure')
            ->willReturnCallback(
                static fn (string $_handler, callable $operation): mixed => $operation(),
            )
        ;

        return $metrics;
    }
}
