<?php

declare(strict_types=1);

namespace App\Identity\CreateAdminUser\Application;

use App\Shared\Observability\Application\HandlerMetrics;

final readonly class CreateAdminUserHandler
{
    private const string METRIC_NAME = 'identity.create_admin_user';

    public function __construct(
        private AdminUserValidator $validator,
        private AdminUserUniquenessChecker $uniquenessChecker,
        private PasswordHasher $passwordHasher,
        private AdminUserRepository $repository,
        private AdminUserTransaction $transaction,
        private HandlerMetrics $metrics,
    ) {}

    public function __invoke(CreateAdminUserCommand $command): CreateAdminUserResult
    {
        return $this->metrics->measure(
            self::METRIC_NAME,
            fn (): CreateAdminUserResult => $this->handle($command),
        );
    }

    private function handle(CreateAdminUserCommand $command): CreateAdminUserResult
    {
        $violations = $this->validator->validate($command);

        if ($violations !== []) {
            throw new InvalidAdminUser($violations);
        }

        $conflicts = $this->uniquenessChecker->findConflicts(
            $command->email,
            $command->username,
        );

        if ($conflicts !== []) {
            return CreateAdminUserResult::conflicted($conflicts);
        }

        $passwordHash = $this->passwordHasher->hash($command->plainPassword);
        $administrator = new NewAdminUser(
            email: $command->email,
            username: $command->username,
            passwordHash: $passwordHash,
            roles: $command->roles,
            enabled: true,
        );

        try {
            $administratorId = $this->transaction->execute(
                fn (): string => $this->repository->save($administrator),
            );
        } catch (\Throwable $exception) {
            throw new AdminUserPersistenceFailed($exception);
        }

        return CreateAdminUserResult::created($administratorId);
    }
}
